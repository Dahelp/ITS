import 'package:flutter_test/flutter_test.dart';
import 'package:its_avito/api.dart';
import 'package:its_avito/push.dart';

void main() {
  test('Push deep link only accepts an Avito chat identifier', () {
    expect(
        pushChat({'type': 'avito_message', 'chat_id': 'chat-123'}), 'chat-123');
    for (final data in <Json>[
      {'type': 'other', 'chat_id': 'chat-123'},
      {'type': 'avito_message', 'chat_id': ''},
      {'type': 'avito_message', 'chat_id': 'bad chat'},
      {'type': 'avito_message', 'chat_id': 'x' * 257},
      {'type': 'avito_message', 'chat_id': 123},
    ]) {
      expect(pushChat(data), null);
    }
  });
  test('Missing Firebase project leaves push disabled without invoking plugins',
      () async {
    expect(PushConfiguration.options, null);
    final api = ChatApi();
    final session = ChatSession(api, MemoryTokenStore());
    final push = PushController(session);
    await push.initialize();
    expect(push.configured, false);
    expect(push.registered, false);
    expect(push.status, contains('Firebase'));
    push.dispose();
    session.dispose();
    api.close();
  });
  test('Cold-start notification waits for a signed-in user', () async {
    final api = ChatApi();
    final session = ChatSession(api, MemoryTokenStore());
    final push = PushController(session);
    final opened = <String>[];
    push.openChat = (chat) async {
      opened.add(chat);
    };
    push.receiveTap({'type': 'avito_message', 'chat_id': 'chat-123'});
    await push.deliverPending();
    expect(opened, isEmpty);
    session.restoring = false;
    session.user = {'id': 1};
    await push.deliverPending();
    expect(opened, ['chat-123']);
    await push.deliverPending();
    expect(opened.length, 1);
    push.dispose();
    session.dispose();
    api.close();
  });
}
