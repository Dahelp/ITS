import 'dart:convert';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:its_avito/api.dart';
import 'package:its_avito/models.dart';

void main() {
  test(
      'Bearer is in header, mutations are POST and never automatically retried',
      () async {
    var count = 0;
    final api = ChatApi(client: MockClient((r) async {
      count++;
      expect(r.url.host, 'its-center.ru');
      expect(r.url.scheme, 'https');
      expect(r.url.query.contains('secret'), false);
      expect(r.headers['Authorization'], 'Bearer secret');
      expect(r.method, 'POST');
      expect(r.followRedirects, false);
      expect(jsonDecode(r.body), {'chat': 'a', 'text': 'Hello'});
      return http.Response('{"error":{"message":"Unavailable"}}', 502);
    }))
      ..token = 'secret';
    await expectLater(api.call('send', body: {'chat': 'a', 'text': 'Hello'}),
        throwsA(isA<ApiFailure>()));
    expect(count, 1);
  });
  test('Revoked access erases token and user', () async {
    final store = MemoryTokenStore();
    await store.write('a' * 64);
    final api = ChatApi(
        client: MockClient((r) async =>
            http.Response('{"error":{"message":"Disabled"}}', 403)));
    final session = ChatSession(api, store);
    await session.restore();
    expect(api.token, null);
    expect(await store.read(), null);
    expect(session.user, null);
  });
  test('Offline restore retains device token to allow retry', () async {
    final store = MemoryTokenStore();
    await store.write('a' * 64);
    final session = ChatSession(
        ChatApi(
            client:
                MockClient((r) async => throw http.ClientException('offline'))),
        store);
    await session.restore();
    expect(await store.read(), 'a' * 64);
    expect(session.error, isNotNull);
  });
  test('Rejected logout does not pretend to revoke device', () async {
    final api = ChatApi(
        client: MockClient(
            (r) async => http.Response('{"error":{"message":"Offline"}}', 502)))
      ..token = 'a' * 64;
    final store = MemoryTokenStore();
    await store.write(api.token!);
    final session = ChatSession(api, store)..user = {'id': 1};
    await expectLater(session.logout(), throwsA(isA<ApiFailure>()));
    expect(session.user, isNotNull);
    expect(await store.read(), isNotNull);
  });
  test('Links reject executable and plain HTTP URLs', () {
    for (final url in [
      'javascript:alert(1)',
      'http://example.test',
      'https://user:password@example.test',
      'file:///tmp/a'
    ]) {
      expect(safeUrl(url), null);
    }
    expect(
        safeUrl('https://its-center.ru/avito-document/download?token=x')?.host,
        'its-center.ru');
  });
  test('Moscow dates and overlapping history pages', () {
    final seconds =
        DateTime.parse('2026-10-16T21:05:00Z').millisecondsSinceEpoch ~/ 1000;
    expect(dateLabel(seconds, now: DateTime.parse('2026-10-16T22:00:00Z')),
        '00:05');
    expect(dateLabel(seconds, now: DateTime.parse('2026-10-18T00:00:00Z')),
        '17.10.2026');
    final merged = mergeMessages([
      {'id': '1', 'created': 1},
      {'id': '2', 'created': 2}
    ], [
      {'id': '2', 'created': 2, 'read': true},
      {'id': '3', 'created': 3}
    ]);
    expect(merged.length, 3);
    expect(merged[1]['read'], true);
  });
}
