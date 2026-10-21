import 'dart:async';
import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:file_picker/file_picker.dart';
import 'package:just_audio/just_audio.dart';
import 'package:url_launcher/url_launcher.dart';
import 'api.dart';
import 'models.dart';
import 'push.dart';
import 'brand.dart';

void showError(BuildContext context, Object error) {
  ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('$error'), duration: const Duration(seconds: 6)));
}

Future<bool> confirm(BuildContext context, String title, String body) async =>
    await showDialog<bool>(
        context: context,
        builder: (context) =>
            AlertDialog(title: Text(title), content: Text(body), actions: [
              TextButton(
                  onPressed: () => Navigator.pop(context, false),
                  child: const Text('Отмена')),
              FilledButton(
                  onPressed: () => Navigator.pop(context, true),
                  child: const Text('Подтвердить'))
            ])) ??
    false;

/// No overlapping polls; suspend network activity when backgrounded or another route is on top.
mixin ForegroundPolling<T extends StatefulWidget>
    on State<T>, WidgetsBindingObserver {
  Timer? _timer;
  bool foreground = true;
  int _failures = 0;
  int? revision;
  DateTime _lastRefresh = DateTime.fromMillisecondsSinceEpoch(0);
  ChatApi get api;
  Future<void> refresh();
  void startPolling() {
    WidgetsBinding.instance.addObserver(this);
    _schedule();
  }

  void _schedule() {
    _timer?.cancel();
    _timer = Timer(
        Duration(seconds: math.min(60, 5 * (1 << math.min(_failures, 4)))),
        _poll);
  }

  Future<void> _poll() async {
    if (!mounted) return;
    if (foreground && (ModalRoute.of(context)?.isCurrent ?? false)) {
      try {
        final status = await api.call('events');
        final next = int.tryParse('${status['revision']}') ?? 0;
        if (revision != next ||
            DateTime.now().difference(_lastRefresh).inSeconds >= 15) {
          await refresh();
          revision = next;
          _lastRefresh = DateTime.now();
        }
        _failures = 0;
      } catch (_) {
        _failures++;
        if (DateTime.now().difference(_lastRefresh).inSeconds >= 15) {
          try {
            await refresh();
            _lastRefresh = DateTime.now();
          } catch (_) {/* refresh exposes its own error banner */}
        }
      }
    }
    if (mounted) _schedule();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    foreground = state == AppLifecycleState.resumed;
    if (foreground) {
      revision = null;
      _schedule();
    }
  }

  void stopPolling() {
    _timer?.cancel();
    WidgetsBinding.instance.removeObserver(this);
  }
}

class LoginScreen extends StatefulWidget {
  final ChatSession session;
  const LoginScreen({super.key, required this.session});
  @override
  State<LoginScreen> createState() => _LoginState();
}

class _LoginState extends State<LoginScreen> {
  final email = TextEditingController(), password = TextEditingController();
  bool busy = false, hidden = true;
  String? error;
  Future<void> login() async {
    if (busy) return;
    setState(() => busy = true);
    try {
      await widget.session.login(email.text, password.text);
      password.clear();
    } catch (e) {
      if (mounted) setState(() => error = '$e');
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  @override
  void dispose() {
    email.dispose();
    password.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
      body: SafeArea(
          child: Center(
              child: SingleChildScrollView(
                  padding: const EdgeInsets.all(28),
                  child: ConstrainedBox(
                      constraints: const BoxConstraints(maxWidth: 420),
                      child: AutofillGroup(
                          child: Column(
                              crossAxisAlignment: CrossAxisAlignment.stretch,
                              children: [
                            const Align(
                                alignment: Alignment.centerLeft,
                                child: BrandMark(size: 72)),
                            const SizedBox(height: 28),
                            const Text('ИТС-Центр',
                                style: TextStyle(
                                    fontSize: 16, fontWeight: FontWeight.w600)),
                            const SizedBox(height: 8),
                            const Text('Чат Авито',
                                style: TextStyle(
                                    fontSize: 34, fontWeight: FontWeight.bold)),
                            const SizedBox(height: 12),
                            const Text(
                                'Отвечайте покупателям с телефона. Войдите под своей учётной записью сотрудника.',
                                style: TextStyle(
                                    color: Colors.black54, height: 1.5)),
                            const SizedBox(height: 28),
                            TextField(
                                controller: email,
                                enabled: !busy,
                                keyboardType: TextInputType.emailAddress,
                                autofillHints: const [AutofillHints.username],
                                textInputAction: TextInputAction.next,
                                decoration: const InputDecoration(
                                    labelText: 'Email сотрудника')),
                            const SizedBox(height: 16),
                            TextField(
                                controller: password,
                                enabled: !busy,
                                obscureText: hidden,
                                autofillHints: const [AutofillHints.password],
                                onSubmitted: (_) => login(),
                                decoration: InputDecoration(
                                    labelText: 'Пароль',
                                    suffixIcon: IconButton(
                                        tooltip: 'Показать пароль',
                                        onPressed: () =>
                                            setState(() => hidden = !hidden),
                                        icon: Icon(hidden
                                            ? Icons.visibility_outlined
                                            : Icons.visibility_off_outlined)))),
                            if (error ?? widget.session.error
                                case final String error)
                              Padding(
                                  padding: const EdgeInsets.only(top: 16),
                                  child: Text(error,
                                      style: TextStyle(
                                          color: Theme.of(context)
                                              .colorScheme
                                              .error))),
                            const SizedBox(height: 24),
                            FilledButton(
                                onPressed: busy ? null : login,
                                child: Padding(
                                    padding: const EdgeInsets.all(14),
                                    child: busy
                                        ? const SizedBox(
                                            width: 20,
                                            height: 20,
                                            child: CircularProgressIndicator(
                                                strokeWidth: 2))
                                        : const Text('Войти'))),
                            const SizedBox(height: 20),
                            if (widget.session.api.token != null)
                              TextButton(
                                  onPressed: busy
                                      ? null
                                      : () => widget.session.restore(),
                                  child: const Text(
                                      'Повторить подключение с сохранённой сессией')),
                            const Text(
                                'Доступ включает администратор в разделе «Клиенты и доступы».',
                                style: TextStyle(
                                    fontSize: 12,
                                    color: Colors.black54,
                                    height: 1.5)),
                            if (const bool.fromEnvironment('DEMO'))
                              const Padding(
                                  padding: EdgeInsets.only(top: 16),
                                  child: Text(
                                      'ДЕМО · Любые email и пароль. Реальные сообщения не отправляются.',
                                      style: TextStyle(color: Colors.orange))),
                          ])))))));
}

class ChatListScreen extends StatefulWidget {
  final ChatSession session;
  final PushController? push;
  const ChatListScreen({super.key, required this.session, this.push});
  @override
  State<ChatListScreen> createState() => _ChatListState();
}

class _ChatListState extends State<ChatListScreen>
    with WidgetsBindingObserver, ForegroundPolling<ChatListScreen> {
  @override
  ChatApi get api => widget.session.api;
  List<Json> chats = [];
  bool busy = false, unread = false, more = false;
  bool filterReady = false;
  int offset = 0;
  String search = '';
  String assignee = 'all';
  List<Json> managers = [];
  Map<String, String> get ownerChoices => {
        'all': 'Все чаты',
        'mine': 'Мои чаты',
        'unassigned': 'Без ответственного',
        for (final manager in managers)
          '${manager['id']}': '${manager['name']}',
      };
  String? error;
  @override
  void initState() {
    super.initState();
    widget.session.addListener(readChanged);
    restoreFilter();
    startPolling();
  }

  Future<void> restoreFilter() async {
    try {
      final saved = await widget.session.store
          .readFilter('${widget.session.user?['id']}');
      if (mounted &&
          saved != null &&
          RegExp(r'^(all|mine|unassigned|[1-9][0-9]*)$').hasMatch(saved)) {
        setState(() => assignee = saved);
      }
    } catch (_) {}
    if (mounted) {
      setState(() => filterReady = true);
      refresh().catchError((_) {});
    }
  }

  void changeAssignee(String value) {
    setState(() {
      assignee = value;
      chats = [];
      offset = 0;
    });
    widget.session.store
        .writeFilter('${widget.session.user?['id']}', value)
        .catchError((_) {});
    refresh().catchError((_) {});
  }

  @override
  void dispose() {
    widget.session.removeListener(readChanged);
    stopPolling();
    super.dispose();
  }

  Future<void> load({bool next = false}) async {
    if (busy || !filterReady) return;
    var retryWithAll = false;
    final filter = unread;
    final owner = assignee;
    setState(() => busy = true);
    try {
      final result = await api.call('chats', query: {
        'offset': next ? offset : 0,
        'assignee': owner,
        if (filter) 'unread': 1
      });
      if (!mounted || filter != unread || owner != assignee) return;
      final page = rows(result['chats']);
      setState(() {
        final combined = next
            ? [...chats, ...page]
            : filter || owner != 'all'
                ? page
                : [...page, ...chats];
        final unique = <String, Json>{};
        for (final row in combined) {
          unique.putIfAbsent('${row['id']}', () => row);
        }
        chats = unique.values.toList();
        offset = int.tryParse('${result['next_offset']}') ??
            (next ? offset : 0) + page.length;
        more = result['has_more'] == true ||
            (result['has_more'] == null && page.length == 50);
        managers = rows(result['managers']);
        if (!['all', 'mine', 'unassigned', ...managers.map((m) => '${m['id']}')]
            .contains(assignee)) {
          assignee = 'all';
          retryWithAll = true;
        }
        error = null;
      });
    } catch (e) {
      if (mounted) setState(() => error = '$e');
      rethrow;
    } finally {
      if (mounted) setState(() => busy = false);
    }
    if (mounted && retryWithAll) await load();
  }

  void readChanged() {
    if (mounted) setState(() {});
  }

  bool isUnread(Json chat) =>
      chat['is_unread'] == true &&
      widget.session.readMessages['${chat['id']}'] !=
          '${object(chat['last_message'])['id']}';

  @override
  Future<void> refresh() => load();
  Future<void> open(Json chat) async {
    await Navigator.push(
        context,
        MaterialPageRoute(
            builder: (_) =>
                ConversationScreen(session: widget.session, chat: chat)));
    if (mounted) refresh().catchError((_) {});
  }

  Future<void> account() async {
    await Navigator.push(
        context,
        MaterialPageRoute(
            builder: (_) =>
                AccountScreen(session: widget.session, push: widget.push)));
  }

  @override
  Widget build(BuildContext context) {
    final visible = chats
        .where((c) => !unread || isUnread(c))
        .where((c) =>
            ('${chatTitle(c)} ${preview(object(c['last_message']))} ${c['assigned_to'] ?? ''}')
                .toLowerCase()
                .contains(search.toLowerCase()))
        .toList();
    return Scaffold(
        appBar: AppBar(
            title: const Row(children: [
              BrandMark(size: 36),
              SizedBox(width: 10),
              Flexible(
                  child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                    Text('Сообщения',
                        style: TextStyle(fontWeight: FontWeight.bold)),
                    Text('ИТС-Центр · Авито',
                        style: TextStyle(fontSize: 12, color: Colors.black54))
                  ]))
            ]),
            actions: [
              IconButton(
                  tooltip: 'Обновить',
                  onPressed: busy ? null : () => refresh().catchError((_) {}),
                  icon: const Icon(Icons.refresh_rounded)),
              IconButton(
                  tooltip: 'Профиль',
                  onPressed: account,
                  icon: const Icon(Icons.account_circle_outlined))
            ]),
        bottomNavigationBar: NavigationBar(
            selectedIndex: 0,
            onDestinationSelected: (index) {
              if (index == 1) account();
            },
            destinations: const [
              NavigationDestination(
                  icon: Icon(Icons.chat_bubble_outline_rounded),
                  selectedIcon: Icon(Icons.chat_bubble_rounded),
                  label: 'Сообщения'),
              NavigationDestination(
                  icon: Icon(Icons.person_outline_rounded), label: 'Кабинет')
            ]),
        body: Column(children: [
          Padding(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
              child: TextField(
                  onChanged: (v) => setState(() => search = v),
                  decoration: const InputDecoration(
                      hintText: 'Поиск в загруженных чатах',
                      prefixIcon: Icon(Icons.search),
                      fillColor: Color(0xfff1f2f3),
                      border: OutlineInputBorder(
                          borderRadius: BorderRadius.all(Radius.circular(18)),
                          borderSide: BorderSide.none),
                      enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.all(Radius.circular(18)),
                          borderSide: BorderSide.none),
                      isDense: true))),
          Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
              child: Align(
                  alignment: Alignment.centerLeft,
                  child: SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: Row(children: [
                        PopupMenuButton<String>(
                          tooltip: 'Ответственный за чат',
                          enabled: !busy,
                          initialValue: assignee,
                          onSelected: changeAssignee,
                          shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16)),
                          itemBuilder: (_) => ownerChoices.entries
                              .map((entry) => PopupMenuItem<String>(
                                    value: entry.key,
                                    child: Row(children: [
                                      SizedBox(
                                          width: 28,
                                          child: assignee == entry.key
                                              ? const Icon(Icons.check_rounded,
                                                  size: 18, color: itsNavy)
                                              : null),
                                      Flexible(child: Text(entry.value))
                                    ]),
                                  ))
                              .toList(),
                          child: Container(
                            constraints: const BoxConstraints(minHeight: 48),
                            padding: const EdgeInsets.symmetric(horizontal: 14),
                            decoration: BoxDecoration(
                                color: assignee == 'all'
                                    ? const Color(0xfff1f2f3)
                                    : const Color(0xffdcecf2),
                                borderRadius: BorderRadius.circular(14)),
                            child:
                                Row(mainAxisSize: MainAxisSize.min, children: [
                              ConstrainedBox(
                                  constraints:
                                      const BoxConstraints(maxWidth: 155),
                                  child: Text(
                                      ownerChoices[assignee] ?? 'Сотрудник…',
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: const TextStyle(
                                          fontWeight: FontWeight.w600))),
                              const SizedBox(width: 4),
                              const Icon(Icons.keyboard_arrow_down_rounded,
                                  size: 20),
                            ]),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Semantics(
                            toggled: unread,
                            button: true,
                            child: InkWell(
                                borderRadius: BorderRadius.circular(14),
                                onTap: busy
                                    ? null
                                    : () {
                                        setState(() {
                                          unread = !unread;
                                          chats = [];
                                          offset = 0;
                                        });
                                        refresh().catchError((_) {});
                                      },
                                child: Container(
                                  constraints:
                                      const BoxConstraints(minHeight: 48),
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 14),
                                  decoration: BoxDecoration(
                                      color: unread
                                          ? const Color(0xffdcecf2)
                                          : const Color(0xfff1f2f3),
                                      borderRadius: BorderRadius.circular(14)),
                                  child: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        if (unread) ...[
                                          const Icon(Icons.check_rounded,
                                              size: 18, color: itsNavy),
                                          const SizedBox(width: 4)
                                        ],
                                        const Text('Непрочитанные',
                                            style: TextStyle(
                                                fontWeight: FontWeight.w600))
                                      ]),
                                ))),
                      ])))),
          if (const bool.fromEnvironment('DEMO'))
            const Text('ДЕМО · Тестовые диалоги',
                style: TextStyle(color: Colors.orange)),
          if (error != null)
            MaterialBanner(content: Text(error!), actions: [
              TextButton(
                  onPressed: () => refresh().catchError((_) {}),
                  child: const Text('Повторить'))
            ]),
          if (busy) const LinearProgressIndicator(minHeight: 2),
          Expanded(
              child: RefreshIndicator(
                  onRefresh: () => refresh().catchError((_) {}),
                  child: ListView.builder(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.all(12),
                      itemCount: visible.length + 1,
                      itemBuilder: (context, i) {
                        if (i == visible.length) {
                          return Padding(
                              padding: const EdgeInsets.all(24),
                              child: more
                                  ? OutlinedButton(
                                      onPressed: busy
                                          ? null
                                          : () => load(next: true)
                                              .catchError((_) {}),
                                      child: const Text('Загрузить ещё'))
                                  : Text(
                                      visible.isEmpty && !busy
                                          ? 'Диалогов не найдено'
                                          : '',
                                      textAlign: TextAlign.center));
                        }
                        final chat = visible[i],
                            message = object(chat['last_message']);
                        final fresh = isUnread(chat);
                        return Card(
                            elevation: 0,
                            color:
                                fresh ? const Color(0xfff0f6f8) : Colors.white,
                            shape: const RoundedRectangleBorder(
                                borderRadius: BorderRadius.zero,
                                side: BorderSide(
                                    color: Color(0xffedf0f2), width: .5)),
                            margin: EdgeInsets.zero,
                            child: InkWell(
                                borderRadius: BorderRadius.circular(12),
                                onTap: () => open(chat),
                                child: Padding(
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 4, vertical: 16),
                                    child: Row(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          ProductAvatar(chat: chat),
                                          const SizedBox(width: 12),
                                          Expanded(
                                              child: Column(
                                                  crossAxisAlignment:
                                                      CrossAxisAlignment.start,
                                                  children: [
                                                Text(chatTitle(chat),
                                                    maxLines: 2,
                                                    overflow:
                                                        TextOverflow.ellipsis,
                                                    style: TextStyle(
                                                        fontWeight: fresh
                                                            ? FontWeight.w800
                                                            : FontWeight.w600)),
                                                const SizedBox(height: 7),
                                                Text(preview(message),
                                                    maxLines: 2,
                                                    overflow:
                                                        TextOverflow.ellipsis,
                                                    style: TextStyle(
                                                        fontSize: 13,
                                                        color: fresh
                                                            ? Colors.black87
                                                            : Colors.black54)),
                                                if ('${chat['assigned_to'] ?? ''}'
                                                    .isNotEmpty)
                                                  Padding(
                                                      padding:
                                                          const EdgeInsets.only(
                                                              top: 8),
                                                      child: Text(
                                                          'Ответственный: ${chat['assigned_to']}',
                                                          style: const TextStyle(
                                                              fontSize: 11,
                                                              color: Colors
                                                                  .black54)))
                                              ])),
                                          const SizedBox(width: 8),
                                          Column(
                                              crossAxisAlignment:
                                                  CrossAxisAlignment.end,
                                              children: [
                                                Text(
                                                    dateLabel(
                                                        message['created']),
                                                    style: const TextStyle(
                                                        fontSize: 11,
                                                        color: Colors.black54)),
                                                const SizedBox(height: 8),
                                                if (fresh)
                                                  const Badge(
                                                      backgroundColor: itsRed,
                                                      label: Text('Новое'))
                                                else if (message['direction'] ==
                                                    'out')
                                                  Icon(
                                                      message['read'] != null &&
                                                              message['read'] !=
                                                                  false
                                                          ? Icons.done_all
                                                          : Icons.done,
                                                      size: 17,
                                                      color: itsNavy)
                                              ]),
                                        ]))));
                      }))),
        ]));
  }
}

class ConversationScreen extends StatefulWidget {
  final ChatSession session;
  final Json chat;
  const ConversationScreen(
      {super.key, required this.session, required this.chat});
  @override
  State<ConversationScreen> createState() => _ConversationState();
}

class _ConversationState extends State<ConversationScreen>
    with WidgetsBindingObserver, ForegroundPolling<ConversationScreen> {
  @override
  ChatApi get api => widget.session.api;
  String get chat => '${widget.chat['id']}';
  final text = TextEditingController();
  List<Json> messages = [];
  bool loading = false, sending = false, more = false, uncertain = false;
  bool listingLoading = false;
  int offset = 0;
  String? error;
  @override
  void initState() {
    super.initState();
    widget.session.addListener(checkSession);
    refresh().catchError((_) {});
    startPolling();
  }

  void checkSession() {
    if (widget.session.user == null && mounted) {
      Navigator.of(context).popUntil((r) => r.isFirst);
    }
  }

  @override
  void dispose() {
    widget.session.removeListener(checkSession);
    stopPolling();
    text.dispose();
    super.dispose();
  }

  Future<void> load({bool older = false}) async {
    if (loading) return;
    setState(() => loading = true);
    try {
      final result = await api.call('messages',
          query: {'chat': chat, 'offset': older ? offset : 0});
      final page = rows(result['messages']);
      if (!mounted) return;
      setState(() {
        messages = mergeMessages(messages, page);
        if (older) {
          offset += page.length;
        } else if (offset == 0) {
          offset = page.length;
        }
        more = older || offset <= 100 ? page.length == 100 : more;
        error = null;
      });
      if (foreground && (ModalRoute.of(context)?.isCurrent ?? false)) {
        await api.call('read', body: {'chat': chat});
        if (mounted && !older) {
          widget.session.chatRead(chat,
              '${page.isEmpty ? object(widget.chat['last_message'])['id'] : mergeMessages([], page).last['id']}');
        }
      }
    } catch (e) {
      if (mounted) setState(() => error = '$e');
      rethrow;
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  @override
  Future<void> refresh() => load();
  Future<void> send() async {
    final value = text.text.trim();
    if (value.isEmpty || sending || uncertain) return;
    setState(() => sending = true);
    try {
      await api.call('send', body: {'chat': chat, 'text': value});
      text.clear();
      await refresh();
    } catch (e) {
      if (mounted) {
        if (e is ApiFailure && (e.status == 0 || e.status >= 500)) {
          setState(() => uncertain = true);
        }
        showError(context, e);
      }
    } finally {
      if (mounted) setState(() => sending = false);
    }
  }

  Future<void> attach(bool document) async {
    if (sending || uncertain) return;
    setState(() => sending = true);
    try {
      final caps = await api.call('capabilities');
      final pick = await FilePicker.platform.pickFiles(
          type: FileType.custom,
          allowedExtensions:
              document ? ['pdf', 'docx'] : ['jpg', 'jpeg', 'png', 'gif', 'bmp'],
          withReadStream: true);
      if (pick == null || !mounted) return;
      final file = pick.files.single,
          limit = int.tryParse(
                  '${caps[document ? 'max_document_bytes' : 'max_image_bytes']}') ??
              0;
      if (file.size <= 0 || file.size > limit) {
        throw ApiFailure(400,
            'Файл превышает лимит ${(limit / 1048576).toStringAsFixed(1)} МБ или пуст.');
      }
      final approved = await confirm(
          context,
          document ? 'Отправить документ?' : 'Отправить фотографию?',
          '${file.name}\n${(file.size / 1024).ceil()} КБ\n${document ? 'Покупатель получит ссылку на 7 дней.' : 'Фотография будет отправлена покупателю.'}');
      if (!approved || !mounted) return;
      final bytes = file.bytes ?? await file.xFile.readAsBytes();
      if (document) {
        final prepared =
            await api.upload('upload-document', chat, file.name, bytes);
        await api.call('send-document',
            body: {'chat': chat, 'token': prepared['token']});
      } else {
        await api.upload('send-image', chat, file.name, bytes);
      }
      await refresh();
    } catch (e) {
      if (mounted) {
        if (e is ApiFailure && (e.status == 0 || e.status >= 500)) {
          setState(() => uncertain = true);
        }
        showError(context, e);
      }
    } finally {
      if (mounted) setState(() => sending = false);
    }
  }

  Future<void> tools() async {
    final result = await Navigator.push<String>(
        context,
        MaterialPageRoute(
            builder: (_) =>
                WorkspaceScreen(session: widget.session, chat: chat)));
    if (result != null && mounted) text.text = result;
  }

  Future<void> showListing() async {
    final itemId = chatItemId(widget.chat);
    if (itemId.isEmpty) {
      showError(context, 'Avito не передал ID объявления для этого диалога.');
      return;
    }
    setState(() => listingLoading = true);
    try {
      final listing = await api.call('listing', query: {'item_id': itemId});
      if (!mounted) return;
      await showModalBottomSheet<void>(
          context: context,
          isScrollControlled: true,
          useSafeArea: true,
          builder: (_) => ListingDetailsSheet(listing: listing));
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => listingLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
      appBar: AppBar(
          titleSpacing: 0,
          title: Row(children: [
            ProductAvatar(chat: widget.chat, size: 40),
            const SizedBox(width: 10),
            Expanded(
                child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                  Text(chatTitle(widget.chat),
                      style: const TextStyle(
                          fontSize: 15, fontWeight: FontWeight.w700),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis),
                  const Text('ИТС-Центр · Авито',
                      style: TextStyle(fontSize: 11, color: Colors.black54)),
                ]))
          ]),
          actions: [
            IconButton(
                tooltip: 'Объявление и наличие',
                onPressed: listingLoading ? null : showListing,
                icon: listingLoading
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(strokeWidth: 2))
                    : const Icon(Icons.inventory_2_outlined)),
            IconButton(
                tooltip: 'Заметки и ответственный',
                onPressed: tools,
                icon: const Icon(Icons.assignment_ind_outlined))
          ]),
      body: SafeArea(
          top: false,
          child: Column(children: [
            if (error != null)
              MaterialBanner(content: Text(error!), actions: [
                TextButton(
                    onPressed: () => refresh().catchError((_) {}),
                    child: const Text('Обновить'))
              ]),
            if (loading) const LinearProgressIndicator(minHeight: 2),
            Expanded(
                child: RefreshIndicator(
                    onRefresh: () => refresh().catchError((_) {}),
                    child: ListView.builder(
                        reverse: true,
                        physics: const AlwaysScrollableScrollPhysics(),
                        padding: const EdgeInsets.symmetric(
                            horizontal: 16, vertical: 12),
                        itemCount: messages.length + 1,
                        itemBuilder: (context, i) {
                          if (i == messages.length) {
                            return Padding(
                                padding: const EdgeInsets.all(12),
                                child: more
                                    ? TextButton(
                                        onPressed: loading
                                            ? null
                                            : () => load(older: true)
                                                .catchError((_) {}),
                                        child:
                                            const Text('Предыдущие сообщения'))
                                    : const Text('Начало переписки',
                                        textAlign: TextAlign.center,
                                        style: TextStyle(
                                            fontSize: 12,
                                            color: Colors.black45)));
                          }
                          final index = messages.length - 1 - i;
                          final message = messages[index];
                          final heading = dayHeading(message['created']);
                          final newDay = index == 0 ||
                              heading !=
                                  dayHeading(messages[index - 1]['created']);
                          return Column(children: [
                            if (newDay && heading.isNotEmpty)
                              Padding(
                                  padding: const EdgeInsets.only(
                                      top: 20, bottom: 20),
                                  child: Text(heading,
                                      style: const TextStyle(
                                          fontWeight: FontWeight.w700,
                                          fontSize: 13,
                                          color: itsNavy))),
                            MessageBubble(
                                chat: chat,
                                key: ValueKey(
                                    messages[messages.length - 1 - i]['id']),
                                api: api,
                                message: message)
                          ]);
                        }))),
            if (uncertain)
              MaterialBanner(
                  content: const Text(
                      'Отправка могла выполниться. Проверьте последние сообщения перед повтором.'),
                  actions: [
                    TextButton(
                        onPressed: () => refresh().catchError((_) {}),
                        child: const Text('Обновить')),
                    TextButton(
                        onPressed: () => setState(() => uncertain = false),
                        child: const Text('Проверил'))
                  ]),
            if (sending) const LinearProgressIndicator(minHeight: 2),
            Container(
                color: Colors.white,
                padding: const EdgeInsets.fromLTRB(8, 8, 8, 10),
                child:
                    Row(crossAxisAlignment: CrossAxisAlignment.end, children: [
                  PopupMenuButton<bool>(
                      enabled: !sending && !uncertain,
                      tooltip: 'Прикрепить',
                      icon: const Icon(Icons.add_circle_outline),
                      onSelected: attach,
                      itemBuilder: (_) => const [
                            PopupMenuItem(
                                value: false, child: Text('Фотография')),
                            PopupMenuItem(
                                value: true, child: Text('PDF / DOCX'))
                          ]),
                  Expanded(
                      child: TextField(
                          controller: text,
                          enabled: !sending,
                          minLines: 1,
                          maxLines: 5,
                          maxLength: 1000,
                          decoration: const InputDecoration(
                              hintText: 'Сообщение покупателю',
                              counterText: '',
                              isDense: true))),
                  const SizedBox(width: 6),
                  IconButton.filled(
                      tooltip: 'Отправить',
                      onPressed: sending || uncertain ? null : send,
                      icon: const Icon(Icons.send_rounded)),
                ])),
          ])));
}

class ListingDetailsSheet extends StatelessWidget {
  final Json listing;
  const ListingDetailsSheet({super.key, required this.listing});

  @override
  Widget build(BuildContext context) {
    final images = (listing['images'] is List ? listing['images'] as List : [])
        .map(safeUrl)
        .whereType<Uri>()
        .toList();
    final price = num.tryParse('${listing['price']}') ?? 0;
    final available = int.tryParse('${listing['available']}') ?? 0;
    final stock = int.tryParse('${listing['stock']}') ?? 0;
    final reserve = int.tryParse('${listing['reserve']}') ?? 0;
    final editUrl = '${listing['edit_url'] ?? ''}';
    final productUrl = '${listing['product_url'] ?? ''}';
    return DraggableScrollableSheet(
        expand: false,
        initialChildSize: .88,
        minChildSize: .55,
        maxChildSize: .96,
        builder: (context, controller) => ListView(
                controller: controller,
                padding: const EdgeInsets.fromLTRB(20, 12, 20, 28),
                children: [
                  Center(
                      child: Container(
                          width: 42,
                          height: 4,
                          margin: const EdgeInsets.only(bottom: 16),
                          decoration: BoxDecoration(
                              color: Colors.black26,
                              borderRadius: BorderRadius.circular(4)))),
                  if (images.isNotEmpty)
                    SizedBox(
                        height: 280,
                        child: PageView(
                            children: images
                                .map((url) => InteractiveViewer(
                                    child: Image.network('$url',
                                        fit: BoxFit.contain,
                                        errorBuilder: (_, __, ___) =>
                                            const Icon(
                                                Icons.broken_image_outlined,
                                                size: 64))))
                                .toList())),
                  const SizedBox(height: 14),
                  Text('${listing['title'] ?? 'Объявление Avito'}',
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.w700, color: itsNavy)),
                  const SizedBox(height: 12),
                  Wrap(spacing: 10, runSpacing: 8, children: [
                    Chip(label: Text('${price.round()} ₽')),
                    Chip(
                        avatar: Icon(
                            available > 0
                                ? Icons.check_circle_outline
                                : Icons.remove_circle_outline,
                            color: available > 0 ? Colors.green : Colors.red),
                        label: Text('Свободно: $available шт.')),
                  ]),
                  Text('На складе: $stock · в резерве: $reserve',
                      style: const TextStyle(color: Colors.black54)),
                  const Divider(height: 28),
                  SelectableText(
                      '${listing['description'] ?? 'Описание не заполнено.'}',
                      style: const TextStyle(height: 1.45)),
                  const SizedBox(height: 20),
                  FilledButton.icon(
                      onPressed: safeUrl(editUrl) == null
                          ? null
                          : () => openLink(context, editUrl),
                      icon: const Icon(Icons.edit_outlined),
                      label: const Text('Открыть и редактировать объявление')),
                  const SizedBox(height: 10),
                  OutlinedButton.icon(
                      onPressed: safeUrl(productUrl) == null
                          ? null
                          : () => openLink(context, productUrl),
                      icon: const Icon(Icons.inventory_2_outlined),
                      label: const Text('Открыть товар')),
                ]));
  }
}

class MessageBubble extends StatelessWidget {
  final ChatApi api;
  final Json message;
  final String chat;
  const MessageBubble(
      {super.key,
      required this.api,
      required this.message,
      required this.chat});
  @override
  Widget build(BuildContext context) {
    final outgoing = message['direction'] == 'out';
    final image = imageUrl(message);
    final content = object(message['content']);
    return Align(
        alignment: outgoing ? Alignment.centerRight : Alignment.centerLeft,
        child: Container(
            constraints: BoxConstraints(
                maxWidth:
                    math.min(540, MediaQuery.sizeOf(context).width * .85)),
            margin: const EdgeInsets.only(bottom: 10),
            padding: const EdgeInsets.fromLTRB(14, 10, 14, 8),
            decoration: BoxDecoration(
                color: outgoing
                    ? const Color(0xffdcecf2)
                    : const Color(0xfff1f2f3),
                borderRadius: BorderRadius.only(
                    topLeft: const Radius.circular(18),
                    topRight: const Radius.circular(18),
                    bottomLeft: Radius.circular(outgoing ? 18 : 5),
                    bottomRight: Radius.circular(outgoing ? 5 : 18))),
            child:
                Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              if (image != null)
                InkWell(
                    onTap: () => showDialog(
                        context: context,
                        builder: (_) => Dialog(
                                child: Stack(children: [
                              InteractiveViewer(
                                  child: Image.network(image,
                                      errorBuilder: (_, __, ___) =>
                                          const Text('Фото недоступно'))),
                              Positioned(
                                  right: 0,
                                  child: IconButton(
                                      tooltip: 'Закрыть',
                                      onPressed: () => Navigator.pop(context),
                                      icon: const Icon(Icons.close)))
                            ]))),
                    child: Image.network(image,
                        width: 260,
                        height: 190,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) =>
                            const Text('Не удалось загрузить фото')))
              else if (message['type'] == 'voice')
                VoicePlayer(
                    api: api,
                    voiceId:
                        '${object(content['voice'])['voice_id'] ?? content['voice_id'] ?? ''}')
              else if (message['type'] == 'link' &&
                  safeUrl(object(content['link'])['url']) != null)
                TextButton(
                    onPressed: () =>
                        openLink(context, '${object(content['link'])['url']}'),
                    child: Text(preview(message)))
              else if (message['type'] == 'text')
                MessageText(text: preview(message))
              else
                SelectableText(preview(message)),
              if (documentToken(message) case final String token)
                TextButton.icon(
                    icon: const Icon(Icons.link_off, size: 16),
                    label: const Text('Отключить ссылку'),
                    onPressed: () async {
                      if (!await confirm(context, 'Отключить документ?',
                          'Покупатель больше не сможет скачать файл по этой ссылке.')) {
                        return;
                      }
                      try {
                        await api.call('revoke-document',
                            body: {'chat': chat, 'token': token});
                        if (context.mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                  content: Text('Ссылка отключена.')));
                        }
                      } catch (e) {
                        if (context.mounted) showError(context, e);
                      }
                    }),
              const SizedBox(height: 6),
              Row(mainAxisSize: MainAxisSize.min, children: [
                Text(clockLabel(message['created']),
                    style:
                        const TextStyle(fontSize: 10, color: Colors.black45)),
                if (outgoing)
                  Padding(
                      padding: const EdgeInsets.only(left: 6),
                      child: Icon(
                          message['read'] != null && message['read'] != false
                              ? Icons.done_all
                              : Icons.done,
                          size: 14,
                          color: itsNavy))
              ]),
            ])));
  }
}

Future<void> openLink(BuildContext context, String url) async {
  final uri = safeUrl(url);
  if (uri == null) return;
  if (!await confirm(context, 'Открыть ссылку?', '${uri.host}\n$url')) return;
  try {
    if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
      throw const ApiFailure(0, 'Не удалось открыть ссылку.');
    }
  } catch (e) {
    if (context.mounted) showError(context, e);
  }
}

class MessageText extends StatelessWidget {
  final String text;
  const MessageText({super.key, required this.text});
  @override
  Widget build(BuildContext context) {
    final urls = RegExp(r'https://[^\s]+')
        .allMatches(text)
        .map((m) => m.group(0)!)
        .where((s) => safeUrl(s) != null)
        .toList();
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      SelectableText(text, style: const TextStyle(height: 1.4)),
      for (final url in urls)
        TextButton(
            onPressed: () => openLink(context, url),
            child: Text('Открыть: ${safeUrl(url)!.host}',
                maxLines: 1, overflow: TextOverflow.ellipsis))
    ]);
  }
}

class VoicePlayer extends StatefulWidget {
  final ChatApi api;
  final String voiceId;
  const VoicePlayer({super.key, required this.api, required this.voiceId});
  @override
  State<VoicePlayer> createState() => _VoiceState();
}

class _VoiceState extends State<VoicePlayer> with WidgetsBindingObserver {
  final player = AudioPlayer();
  bool busy = false;
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) player.pause();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    player.dispose();
    super.dispose();
  }

  Future<void> toggle() async {
    if (busy) return;
    if (player.playing) {
      await player.pause();
      return;
    }
    setState(() => busy = true);
    try {
      final result =
          await widget.api.call('voice', query: {'voice_id': widget.voiceId});
      final uri = safeUrl(result['url']);
      if (uri == null) throw const ApiFailure(502, 'Голосовое недоступно.');
      await player.setUrl('$uri');
      if (mounted) {
        unawaited(player.play().catchError((Object e) {
          if (mounted) {
            showError(context, 'Не удалось воспроизвести голосовое.');
          }
        }));
      }
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => StreamBuilder<PlayerState>(
      stream: player.playerStateStream,
      builder: (context, snapshot) =>
          Row(mainAxisSize: MainAxisSize.min, children: [
            IconButton(
                tooltip: 'Воспроизвести / пауза',
                onPressed: busy ? null : toggle,
                icon: Icon(snapshot.data?.playing == true &&
                        snapshot.data?.processingState !=
                            ProcessingState.completed
                    ? Icons.pause_circle
                    : Icons.play_circle)),
            const Text('Голосовое'),
            if (busy)
              const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2))
          ]));
}

class WorkspaceScreen extends StatefulWidget {
  final ChatSession session;
  final String chat;
  const WorkspaceScreen({super.key, required this.session, required this.chat});
  @override
  State<WorkspaceScreen> createState() => _WorkspaceState();
}

class _WorkspaceState extends State<WorkspaceScreen> {
  Json? data;
  bool busy = false;
  String? error;
  final note = TextEditingController();
  ChatApi get api => widget.session.api;
  @override
  void initState() {
    super.initState();
    load();
    widget.session.addListener(checkSession);
  }

  void checkSession() {
    if (widget.session.user == null && mounted) {
      Navigator.of(context).popUntil((r) => r.isFirst);
    }
  }

  @override
  void dispose() {
    widget.session.removeListener(checkSession);
    note.dispose();
    super.dispose();
  }

  Future<void> load() async {
    try {
      final result = await api.call('workspace', query: {'chat': widget.chat});
      if (mounted) setState(() => data = result);
    } catch (e) {
      if (mounted) setState(() => error = '$e');
    }
  }

  Future<void> mutate(String operation, Json body) async {
    if (busy) return;
    setState(() => busy = true);
    try {
      await api.call(operation, body: body);
      if (operation == 'note') note.clear();
      await load();
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  Future<void> editTemplate([Json? template]) async {
    final title = TextEditingController(text: template?['title']),
        body = TextEditingController(text: template?['body']);
    final result = await showDialog<Json>(
        context: context,
        builder: (context) => AlertDialog(
                title:
                    Text(template == null ? 'Новый шаблон' : 'Изменить шаблон'),
                content: SingleChildScrollView(
                    child: Column(mainAxisSize: MainAxisSize.min, children: [
                  TextField(
                      controller: title,
                      maxLength: 120,
                      decoration: const InputDecoration(labelText: 'Название')),
                  const SizedBox(height: 12),
                  TextField(
                      controller: body,
                      maxLength: 1000,
                      minLines: 3,
                      maxLines: 6,
                      decoration:
                          const InputDecoration(labelText: 'Текст ответа'))
                ])),
                actions: [
                  TextButton(
                      onPressed: () => Navigator.pop(context),
                      child: const Text('Отмена')),
                  FilledButton(
                      onPressed: () => Navigator.pop(context, {
                            'id': template?['id'] ?? 0,
                            'title': title.text,
                            'body': body.text
                          }),
                      child: const Text('Сохранить'))
                ]));
    // Controllers remain alive through the dialog exit animation.
    await Future<void>.delayed(const Duration(milliseconds: 250));
    title.dispose();
    body.dispose();
    if (result != null && mounted) await mutate('template-save', result);
  }

  @override
  Widget build(BuildContext context) => Scaffold(
      appBar: AppBar(title: const Text('Работа с диалогом')),
      body: data == null
          ? Center(
              child: error == null
                  ? const CircularProgressIndicator()
                  : TextButton(
                      onPressed: load, child: Text('$error · Повторить')))
          : ListView(padding: const EdgeInsets.all(20), children: [
              const Text('Ответственный',
                  style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
              const SizedBox(height: 12),
              DropdownButtonFormField<int>(
                  value: rows(data!['managers']).any((m) =>
                          int.tryParse('${m['id']}') ==
                          int.tryParse('${data!['assignee']}'))
                      ? int.tryParse('${data!['assignee']}')
                      : 0,
                  isExpanded: true,
                  items: [
                    const DropdownMenuItem(
                        value: 0, child: Text('Не назначен')),
                    for (final manager in rows(data!['managers']))
                      DropdownMenuItem(
                          value: int.parse('${manager['id']}'),
                          child: Text('${manager['name']}'))
                  ],
                  onChanged: busy
                      ? null
                      : (id) => mutate(
                          'assign', {'chat': widget.chat, 'manager': id ?? 0})),
              const SizedBox(height: 28),
              const Text('Шаблоны ответа',
                  style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
              const Text(
                  'Нажмите, чтобы вставить в сообщение. Покупателю пока ничего не отправится.',
                  style: TextStyle(color: Colors.black54, fontSize: 12)),
              for (final template in rows(data!['templates']))
                Card(
                    child: ListTile(
                        title: Text('${template['title']}'),
                        subtitle: Text('${template['body']}',
                            maxLines: 2, overflow: TextOverflow.ellipsis),
                        onTap: () =>
                            Navigator.pop(context, '${template['body']}'),
                        trailing: template['can_edit'] == true
                            ? PopupMenuButton<String>(
                                onSelected: (value) async {
                                  if (value == 'edit') {
                                    editTemplate(template);
                                  } else if (await confirm(
                                          context,
                                          'Скрыть шаблон?',
                                          '${template['title']}') &&
                                      mounted) {
                                    mutate('template-delete',
                                        {'id': template['id']});
                                  }
                                },
                                itemBuilder: (_) => const [
                                      PopupMenuItem(
                                          value: 'edit',
                                          child: Text('Изменить')),
                                      PopupMenuItem(
                                          value: 'delete',
                                          child: Text('Скрыть'))
                                    ])
                            : null)),
              TextButton.icon(
                  onPressed: busy ? null : () => editTemplate(),
                  icon: const Icon(Icons.add),
                  label: const Text('Добавить шаблон')),
              const SizedBox(height: 24),
              const Text('Внутренние заметки',
                  style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
              const Text('Видны сотрудникам. Покупатель их не получает.',
                  style: TextStyle(color: Colors.black54, fontSize: 12)),
              const SizedBox(height: 12),
              for (final n in rows(data!['notes']))
                Card(
                    child: Padding(
                        padding: const EdgeInsets.all(14),
                        child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('${n['body']}'),
                              const SizedBox(height: 8),
                              Text(
                                  '${n['author'] ?? 'Сотрудник'} · ${dateLabel(n['created_at'], full: true)}',
                                  style: const TextStyle(
                                      fontSize: 11, color: Colors.black54))
                            ]))),
              TextField(
                  controller: note,
                  maxLength: 5000,
                  minLines: 2,
                  maxLines: 5,
                  decoration:
                      const InputDecoration(labelText: 'Новая заметка')),
              const SizedBox(height: 10),
              FilledButton(
                  onPressed: busy
                      ? null
                      : () => mutate(
                          'note', {'chat': widget.chat, 'body': note.text}),
                  child: const Text('Добавить заметку')),
            ]));
}

class AccountScreen extends StatefulWidget {
  final ChatSession session;
  final PushController? push;
  const AccountScreen({super.key, required this.session, this.push});
  @override
  State<AccountScreen> createState() => _AccountState();
}

class _AccountState extends State<AccountScreen> {
  List<Json>? sessions;
  bool busy = false;
  String? error;
  @override
  void initState() {
    super.initState();
    load();
    widget.session.addListener(checkSession);
  }

  void checkSession() {
    if (widget.session.user == null && mounted) {
      Navigator.of(context).popUntil((r) => r.isFirst);
    }
  }

  @override
  void dispose() {
    widget.session.removeListener(checkSession);
    super.dispose();
  }

  Future<void> load() async {
    try {
      final data = await widget.session.api.call('sessions');
      if (mounted) setState(() => sessions = rows(data['sessions']));
    } catch (e) {
      if (mounted) setState(() => error = '$e');
    }
  }

  Future<void> logout(bool all) async {
    if (!await confirm(
            context,
            'Выйти? ',
            all
                ? 'Будут отключены все ваши телефоны.'
                : 'Сессия этого телефона будет отключена.') ||
        !mounted) return;
    setState(() => busy = true);
    try {
      await widget.session.logout(all: all);
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
      appBar: AppBar(title: const Text('Кабинет')),
      body: ListView(padding: const EdgeInsets.all(24), children: [
        const Row(children: [
          BrandMark(size: 64),
          SizedBox(width: 16),
          Expanded(
              child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                Text('ИТС-Центр',
                    style: TextStyle(
                        fontSize: 25,
                        color: itsNavy,
                        fontWeight: FontWeight.w800)),
                Text('АВИТО · КАБИНЕТ СОТРУДНИКА',
                    style: TextStyle(
                        fontSize: 10,
                        letterSpacing: 1,
                        color: itsRed,
                        fontWeight: FontWeight.w700)),
              ]))
        ]),
        const SizedBox(height: 24),
        const Divider(),
        const SizedBox(height: 20),
        Text('${widget.session.user?['name'] ?? ''}',
            style: const TextStyle(fontSize: 26, fontWeight: FontWeight.bold)),
        const SizedBox(height: 6),
        Text(widget.session.user?['role'] == 'admin'
            ? 'Администратор'
            : 'Менеджер'),
        const SizedBox(height: 30),
        const Text('Активные устройства',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
        if (error != null)
          TextButton(onPressed: load, child: Text('$error · Повторить')),
        if (sessions == null && error == null) const LinearProgressIndicator(),
        for (final s in sessions ?? <Json>[])
          ListTile(
              contentPadding: EdgeInsets.zero,
              leading: const Icon(Icons.smartphone),
              title: Text(
                  '${s['device_name']}${s['current'] == true ? ' · Это устройство' : ''}'),
              subtitle:
                  Text('Вход: ${dateLabel(s['created_at'], full: true)}')),
        const SizedBox(height: 24),
        FilledButton(
            onPressed: busy ? null : () => logout(false),
            child: const Text('Выйти на этом телефоне')),
        const SizedBox(height: 12),
        OutlinedButton(
            onPressed: busy ? null : () => logout(true),
            child: const Text('Выйти на всех телефонах')),
        const SizedBox(height: 24),
        if (widget.push case final PushController push)
          AnimatedBuilder(
              animation: push,
              builder: (context, _) => Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const Text('Уведомления',
                            style: TextStyle(
                                fontSize: 18, fontWeight: FontWeight.bold)),
                        const SizedBox(height: 8),
                        Text(push.status),
                        const SizedBox(height: 12),
                        OutlinedButton(
                            onPressed: push.configured && !push.busy
                                ? (push.registered ? push.disable : push.enable)
                                : null,
                            child: Text(push.registered
                                ? 'Отключить на этом телефоне'
                                : 'Включить уведомления')),
                      ])),
      ]));
}
