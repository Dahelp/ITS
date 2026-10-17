import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'api.dart';
import 'screens.dart';
import 'demo.dart';
import 'push.dart';
import 'brand.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  const demo = bool.fromEnvironment('DEMO');
  final session = ChatSession(demo ? DemoApi() : ChatApi(),
      demo ? MemoryTokenStore() : DeviceTokenStore());
  final push = PushController(session);
  runApp(AvitoApp(session: session, push: push));
  push.initialize();
  session.restore();
}

class AvitoApp extends StatefulWidget {
  final ChatSession session;
  final PushController? push;
  const AvitoApp({super.key, required this.session, this.push});
  @override
  State<AvitoApp> createState() => _AvitoAppState();
}

class _AvitoAppState extends State<AvitoApp> with WidgetsBindingObserver {
  ChatSession get session => widget.session;
  final navigator = GlobalKey<NavigatorState>();
  final messenger = GlobalKey<ScaffoldMessengerState>();
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    widget.push?.openChat = _openPushChat;
    widget.push?.foregroundMessage = (chat) => messenger.currentState
        ?.showSnackBar(SnackBar(
            content: const Text('Новое сообщение Авито'),
            action: SnackBarAction(
                label: 'Открыть', onPressed: () => _openPushChat(chat))));
    WidgetsBinding.instance
        .addPostFrameCallback((_) => widget.push?.deliverPending());
  }

  Future<void> _openPushChat(String chat) async {
    if (session.user == null || navigator.currentState == null) return;
    final token = session.api.token;
    try {
      await session.api.call('me');
      final details = await session.resolveChat(chat);
      if (!mounted || token != session.api.token || session.user == null) {
        return;
      }
      navigator.currentState!.popUntil((r) => r.isFirst);
      await navigator.currentState!.push(MaterialPageRoute(
          builder: (_) => ConversationScreen(session: session, chat: details)));
    } catch (_) {
      messenger.currentState?.showSnackBar(const SnackBar(
          content: Text(
              'Не удалось открыть диалог. Проверьте подключение и доступ.')));
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) widget.push?.synchronize();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    widget.push?.openChat = null;
    widget.push?.foregroundMessage = null;
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => MaterialApp(
        navigatorKey: navigator,
        scaffoldMessengerKey: messenger,
        title: 'ИТС · Чат Авито',
        builder: (context, child) => kIsWeb &&
                const bool.fromEnvironment('DEMO')
            ? Center(
                child: SizedBox(
                    width: 430,
                    child: MediaQuery(
                        data: MediaQuery.of(context).copyWith(
                            size: Size(430, MediaQuery.sizeOf(context).height)),
                        child: child!)))
            : child!,
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
            useMaterial3: true,
            colorScheme: ColorScheme.fromSeed(seedColor: itsNavy)
                .copyWith(primary: itsNavy, secondary: itsRed),
            scaffoldBackgroundColor: Colors.white,
            appBarTheme: const AppBarTheme(
                backgroundColor: Colors.white,
                surfaceTintColor: Colors.transparent),
            inputDecorationTheme: InputDecorationTheme(
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(14),
                    borderSide: const BorderSide(color: Color(0xffdce4e0))))),
        home: AnimatedBuilder(
            animation: session,
            builder: (context, _) {
              if (session.restoring) {
                return const Scaffold(
                    body: Center(child: CircularProgressIndicator()));
              }
              return session.user == null
                  ? LoginScreen(session: session)
                  : ChatListScreen(
                      key: ValueKey(session.user!['id']),
                      session: session,
                      push: widget.push);
            }),
      );
}
