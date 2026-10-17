import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

typedef Json = Map<String, dynamic>;
Json object(dynamic value) =>
    value is Map ? Map<String, dynamic>.from(value) : {};
List<Json> rows(dynamic value) =>
    value is List ? value.map(object).toList() : [];

class ApiFailure implements Exception {
  final int status;
  final String message;
  const ApiFailure(this.status, this.message);
  @override
  String toString() => message;
}

abstract class TokenStore {
  Future<String?> read();
  Future<void> write(String token);
  Future<void> clear();
}

class DeviceTokenStore implements TokenStore {
  final _storage = const FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
    iOptions:
        IOSOptions(accessibility: KeychainAccessibility.unlocked_this_device),
  );
  static const _key = 'its.avito.session.v1';
  @override
  Future<String?> read() => _storage.read(key: _key);
  @override
  Future<void> write(String token) => _storage.write(key: _key, value: token);
  @override
  Future<void> clear() => _storage.delete(key: _key);
}

class MemoryTokenStore implements TokenStore {
  String? token;
  @override
  Future<String?> read() async => token;
  @override
  Future<void> write(String value) async {
    token = value;
  }

  @override
  Future<void> clear() async {
    token = null;
  }
}

class ChatApi {
  final http.Client transport;
  static final base = Uri.parse('https://its-center.ru/api/mobile/v1/avito/');
  String? token;
  Future<void> Function()? onDenied;
  ChatApi({http.Client? client}) : transport = client ?? http.Client();

  Future<Json> call(String operation,
      {Json query = const {}, Json? body}) async {
    final request = http.Request(
        body == null ? 'GET' : 'POST',
        base.resolve(operation).replace(
            queryParameters:
                query.isEmpty ? null : query.map((k, v) => MapEntry(k, '$v'))));
    request.followRedirects =
        false; // Never forward credentials to a redirect host.
    request.headers['Accept'] = 'application/json';
    if (token != null) request.headers['Authorization'] = 'Bearer $token';
    if (body != null) {
      request.headers['Content-Type'] = 'application/json';
      request.body = jsonEncode(body);
    }
    return _send(request);
  }

  Future<Json> upload(
      String operation, String chat, String name, Uint8List bytes) async {
    final request = http.MultipartRequest('POST', base.resolve(operation));
    request.followRedirects = false;
    if (token != null) request.headers['Authorization'] = 'Bearer $token';
    request.fields['chat'] = chat;
    request.files.add(http.MultipartFile.fromBytes(
        operation == 'send-image' ? 'image' : 'document', bytes,
        filename: name));
    return _send(request);
  }

  Future<Json> _send(http.BaseRequest request) async {
    try {
      final response = await (() async =>
              http.Response.fromStream(await transport.send(request)))()
          .timeout(const Duration(seconds: 35));
      Json payload;
      try {
        payload = object(jsonDecode(response.body));
      } catch (_) {
        throw const ApiFailure(502, 'Не удалось получить ответ сервера.');
      }
      if (response.statusCode < 200 || response.statusCode >= 300) {
        if ((response.statusCode == 401 || response.statusCode == 403) &&
            token != null) await onDenied?.call();
        throw ApiFailure(response.statusCode,
            '${object(payload['error'])['message'] ?? 'Сервис временно недоступен.'}');
      }
      return object(payload['data']);
    } on TimeoutException {
      throw const ApiFailure(
          0, 'Ответ не получен. Обновите историю перед повторной отправкой.');
    } on http.ClientException {
      throw const ApiFailure(
          0, 'Нет соединения. Проверьте сеть и обновите историю.');
    }
  }

  void close() => transport.close();
}

class ChatSession extends ChangeNotifier {
  final ChatApi api;
  final TokenStore store;
  Json? user;
  bool restoring = true;
  String? error;
  ChatSession(this.api, this.store) {
    api.onDenied = forget;
  }

  Future<void> restore() async {
    try {
      api.token = await store.read();
      if (api.token != null) user = await api.call('me');
    } catch (e) {
      error = '$e';
    }
    restoring = false;
    notifyListeners();
  }

  Future<void> login(String email, String password) async {
    error = null;
    // Production browser login is deliberately disabled. Web target is a demo only.
    if (kIsWeb && !const bool.fromEnvironment('DEMO')) {
      throw const ApiFailure(
          400, 'Для входа используйте приложение Android или iOS.');
    }
    final result = await api.call('login', body: {
      'email': email.trim(),
      'password': password,
      'device_name': defaultTargetPlatform == TargetPlatform.iOS
          ? 'iPhone / iPad'
          : 'Android'
    });
    final token = result['access_token'];
    if (token is! String || !RegExp(r'^[a-f0-9]{64}$').hasMatch(token)) {
      throw const ApiFailure(502, 'Сервер не выдал сессию.');
    }
    try {
      await store.write(token);
    } catch (_) {
      api.token = token;
      try {
        await api.call('logout', body: {});
      } catch (_) {/* No local token is retained. */}
      api.token = null;
      throw const ApiFailure(
          0, 'Не удалось сохранить сессию в защищённом хранилище.');
    }
    api.token = token;
    user = object(result['user']);
    notifyListeners();
  }

  Future<void> forget() async {
    api.token = null;
    user = null;
    try {
      await store.clear();
    } finally {
      notifyListeners();
    }
  }

  Future<void> logout({bool all = false}) async {
    // Do not claim remote revocation if the server could not confirm it.
    await api.call(all ? 'logout-all' : 'logout', body: {});
    await forget();
  }
}
