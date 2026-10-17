import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'api.dart';

class PushConfiguration {
  static const enabled = bool.fromEnvironment('PUSH_ENABLED');
  static const projectId = String.fromEnvironment('FIREBASE_PROJECT_ID');
  static const senderId = String.fromEnvironment('FIREBASE_SENDER_ID');
  static FirebaseOptions? get options {
    if (!enabled || kIsWeb || const bool.fromEnvironment('DEMO')) return null;
    final ios = defaultTargetPlatform == TargetPlatform.iOS;
    const androidId = String.fromEnvironment('FIREBASE_ANDROID_APP_ID');
    const iosId = String.fromEnvironment('FIREBASE_IOS_APP_ID');
    const androidKey = String.fromEnvironment('FIREBASE_ANDROID_API_KEY');
    const iosKey = String.fromEnvironment('FIREBASE_IOS_API_KEY');
    final appId = ios ? iosId : androidId, apiKey = ios ? iosKey : androidKey;
    if (projectId.isEmpty ||
        senderId.isEmpty ||
        appId.isEmpty ||
        apiKey.isEmpty) return null;
    return FirebaseOptions(
        apiKey: apiKey,
        appId: appId,
        messagingSenderId: senderId,
        projectId: projectId,
        iosBundleId: ios ? 'ru.itscenter.itsAvito' : null);
  }
}

@pragma('vm:entry-point')
Future<void> avitoBackgroundMessage(RemoteMessage message) async {
  // OS displays notification payload. Do not authenticate, fetch customer data or mark read in background.
}

String? pushChat(Json data) {
  final chat = data['chat_id'];
  return data['type'] == 'avito_message' &&
          chat is String &&
          chat.isNotEmpty &&
          chat.length <= 256 &&
          !RegExp(r'[\x00-\x20\x7f]').hasMatch(chat)
      ? chat
      : null;
}

/// Permission is requested only from an explicit tap in Profile. Firebase defaults to auto-init off.
class PushController extends ChangeNotifier {
  final ChatSession session;
  FirebaseMessaging? _messaging;
  StreamSubscription<String>? _refresh;
  StreamSubscription<RemoteMessage>? _opened, _foreground;
  Future<void> Function(String chat)? openChat;
  void Function(String chat)? foregroundMessage;
  String? _pending;
  bool _busy = false;
  bool _resync = false;
  bool registered = false;
  String status = 'Push не настроен. Требуется подключение Firebase.';
  bool get configured => _messaging != null;
  PushController(this.session);

  Future<void> initialize() async {
    final options = PushConfiguration.options;
    if (options == null) return;
    try {
      await Firebase.initializeApp(options: options);
      _messaging = FirebaseMessaging.instance;
      FirebaseMessaging.onBackgroundMessage(avitoBackgroundMessage);
      await _messaging!.setForegroundNotificationPresentationOptions(
          alert: false, badge: false, sound: false);
      _refresh = _messaging!.onTokenRefresh.listen((_) {
        if (_busy) {
          _resync = true;
        } else {
          unawaited(synchronize());
        }
      });
      _opened = FirebaseMessaging.onMessageOpenedApp.listen(_receiveTap);
      _foreground = FirebaseMessaging.onMessage.listen((message) {
        final chat = pushChat(message.data);
        if (chat != null && session.user != null) foregroundMessage?.call(chat);
      });
      final initial = await _messaging!.getInitialMessage();
      if (initial != null) _receiveTap(initial);
      session.addListener(_sessionChanged);
      status = 'Нажмите «Включить уведомления».';
      await synchronize();
    } catch (_) {
      status =
          'Не удалось подключить уведомления. Проверьте конфигурацию Firebase.';
    }
    notifyListeners();
  }

  void _receiveTap(RemoteMessage message) {
    receiveTap(message.data);
  }

  void receiveTap(Json data) {
    final chat = pushChat(data);
    if (chat == null) return;
    _pending = chat;
    unawaited(deliverPending());
  }

  Future<void> deliverPending() async {
    if (session.restoring ||
        session.user == null ||
        openChat == null ||
        _pending == null) return;
    final chat = _pending!;
    _pending = null;
    // App callback validates current session with /me before navigating.
    await openChat!(chat);
  }

  void _sessionChanged() {
    if (session.user == null) {
      registered = false;
      notifyListeners();
      return;
    }
    if (_busy) {
      _resync = true;
    } else {
      unawaited(synchronize());
    }
    unawaited(deliverPending());
  }

  Future<void> enable() async {
    if (_messaging == null) return;
    try {
      final result = await _messaging!.requestPermission(
          alert: true, badge: true, sound: true, provisional: false);
      if (result.authorizationStatus != AuthorizationStatus.authorized &&
          result.authorizationStatus != AuthorizationStatus.provisional) {
        status = 'Уведомления запрещены в настройках телефона.';
        registered = false;
        notifyListeners();
        return;
      }
      await _messaging!.setAutoInitEnabled(true);
      await synchronize();
    } catch (_) {
      status = 'Не удалось включить уведомления. Повторите позже.';
      notifyListeners();
    }
  }

  Future<void> synchronize() async {
    if (_messaging == null ||
        _busy ||
        session.user == null ||
        session.api.token == null) return;
    _busy = true;
    final owner = session.api.token;
    try {
      final server = await session.api.call('push-status');
      if (server['enabled'] != true) {
        status = 'Сервер push ещё не подключён к Firebase.';
        registered = false;
        return;
      }
      if (!_messaging!.isAutoInitEnabled) {
        registered = false;
        status = 'Нажмите «Включить уведомления».';
        return;
      }
      final settings = await _messaging!.getNotificationSettings();
      if (settings.authorizationStatus != AuthorizationStatus.authorized &&
          settings.authorizationStatus != AuthorizationStatus.provisional) {
        await session.api.call('push-unregister', body: {});
        registered = false;
        status = 'Нажмите «Включить уведомления».';
        return;
      }
      if (defaultTargetPlatform == TargetPlatform.iOS &&
          await _messaging!.getAPNSToken() == null) {
        status = 'Ожидается регистрация Apple Push. Проверьте подпись и APNs.';
        return;
      }
      final token = await _messaging!.getToken();
      if (token == null) {
        status = 'Не удалось получить токен уведомлений.';
        return;
      }
      if (owner != session.api.token || session.user == null) return;
      await session.api.call('push-register', body: {
        'push_token': token,
        'platform':
            defaultTargetPlatform == TargetPlatform.iOS ? 'ios' : 'android'
      });
      if (owner == session.api.token) {
        registered = true;
        status = 'Телефон зарегистрирован для уведомлений.';
      }
    } catch (_) {
      registered = false;
      status = 'Не удалось зарегистрировать телефон для уведомлений.';
    } finally {
      _busy = false;
      notifyListeners();
      if (_resync) {
        _resync = false;
        unawaited(synchronize());
      }
    }
  }

  Future<void> disable() async {
    if (_messaging == null) return;
    try {
      await session.api.call('push-unregister', body: {});
      await _messaging!.setAutoInitEnabled(false);
      await _messaging!.deleteToken();
      registered = false;
      _pending = null;
      status = 'Уведомления на этом телефоне отключены.';
    } catch (_) {
      status =
          'Не удалось отключить уведомления. Повторите при подключении к сети.';
    }
    notifyListeners();
  }

  @override
  void dispose() {
    session.removeListener(_sessionChanged);
    _refresh?.cancel();
    _opened?.cancel();
    _foreground?.cancel();
    super.dispose();
  }
}
