import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:its_avito/api.dart';
import 'package:its_avito/demo.dart';
import 'package:its_avito/main.dart';
import 'package:its_avito/push.dart';

void main() {
  testWidgets(
      'Responsible filter combines unread, resets and remembers per user',
      (tester) async {
    final api = DemoApi()..assignee = 1;
    final store = MemoryTokenStore();
    final session = ChatSession(api, store)
      ..user = {'id': 1}
      ..restoring = false;
    await tester.pumpWidget(AvitoApp(session: session));
    await tester.pumpAndSettle();
    Future<void> select(String title) async {
      await tester.tap(find.byType(DropdownButton<String>));
      await tester.pumpAndSettle();
      await tester.tap(find.text(title).last);
      await tester.pumpAndSettle();
    }

    await select('Мои чаты');
    expect(find.text('Шины 18x7-8 NHS EKKA'), findsOneWidget);
    expect(find.text('Шина 21x8-9 C601 · Шинокомплект'), findsNothing);
    expect(await store.readFilter('1'), 'mine');
    expect(await store.readFilter('2'), null);
    await tester.tap(find.text('Непрочитанные'));
    await tester.pumpAndSettle();
    expect(find.text('Шины 18x7-8 NHS EKKA'), findsOneWidget);
    await tester.tap(find.text('Непрочитанные'));
    await tester.pumpAndSettle();
    await select('Без ответственного');
    expect(find.text('Шины 18x7-8 NHS EKKA'), findsNothing);
    await select('Все чаты');
    expect(find.text('Шина 21x8-9 C601 · Шинокомплект'), findsOneWidget);
    await select('Мои чаты');
    await tester.pumpWidget(const SizedBox());
    await tester.pumpAndSettle();
    await tester.pumpWidget(AvitoApp(session: session));
    await tester.pumpAndSettle();
    expect(find.text('Мои чаты'), findsOneWidget);
    expect(find.text('Шина 21x8-9 C601 · Шинокомплект'), findsNothing);
    await tester.pumpWidget(const SizedBox());
    await tester.pumpAndSettle();
    session.dispose();
    api.close();
  });
  testWidgets('Push opens product title and clears unread before polling',
      (tester) async {
    final api = DemoApi();
    final session = ChatSession(api, MemoryTokenStore())
      ..user = {'id': 1}
      ..restoring = false;
    final push = PushController(session);
    await tester.pumpWidget(AvitoApp(session: session, push: push));
    await tester.pumpAndSettle();
    push.receiveTap({'type': 'avito_message', 'chat_id': 'demo-1'});
    await tester.pump(const Duration(seconds: 1));
    await tester.pump(const Duration(seconds: 1));
    await tester.pumpAndSettle();
    expect(
        find.descendant(
            of: find.byType(AppBar),
            matching: find.text('Шины 18x7-8 NHS EKKA')),
        findsOneWidget);
    expect(session.readMessages['demo-1'], '3');
    await tester.pageBack();
    await tester.pumpAndSettle();
    await tester.tap(find.text('Непрочитанные'));
    await tester.pumpAndSettle();
    expect(find.text('Шины 18x7-8 NHS EKKA'), findsNothing);
    api.messages.add({
      'id': '4',
      'created': 9999999999,
      'type': 'text',
      'content': {'text': 'Новое'}
    });
    await tester.tap(find.text('Непрочитанные'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Непрочитанные'));
    await tester.pumpAndSettle();
    expect(find.text('Шины 18x7-8 NHS EKKA'), findsOneWidget);
    await tester.pumpWidget(const SizedBox());
    await tester.pumpAndSettle();
    push.dispose();
    session.dispose();
    api.close();
  });
  testWidgets('Profile explains missing Firebase and cannot enable fake push',
      (tester) async {
    final api = DemoApi();
    final session = ChatSession(api, MemoryTokenStore())
      ..user = {'id': 1}
      ..restoring = false;
    final push = PushController(session);
    await push.initialize();
    await tester.pumpWidget(AvitoApp(session: session, push: push));
    await tester.pumpAndSettle();
    await tester.tap(find.byTooltip('Профиль'));
    await tester.pumpAndSettle();
    await tester.scrollUntilVisible(find.text('Включить уведомления'), 200,
        scrollable: find
            .descendant(
                of: find.byType(ListView).last,
                matching: find.byType(Scrollable))
            .first);
    expect(
        find.textContaining('Требуется подключение Firebase'), findsOneWidget);
    expect(
        tester
            .widget<OutlinedButton>(
                find.widgetWithText(OutlinedButton, 'Включить уведомления'))
            .onPressed,
        null);
    await tester.pumpWidget(const SizedBox());
    await tester.pumpAndSettle();
    push.dispose();
    session.dispose();
    api.close();
  });
  testWidgets('Chat list and conversation fit a small phone', (tester) async {
    tester.view.physicalSize = const Size(320, 700);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    final api = DemoApi();
    final session = ChatSession(api, MemoryTokenStore())
      ..user = {'id': 1}
      ..restoring = false;
    await tester.pumpWidget(AvitoApp(session: session));
    await tester.pumpAndSettle();
    expect(tester.takeException(), null);
    await tester.tap(find.text('Шины 18x7-8 NHS EKKA'));
    await tester.pumpAndSettle();
    expect(tester.takeException(), null);
    await tester.pumpWidget(const SizedBox());
    await tester.pumpAndSettle();
    session.dispose();
    api.close();
  });
  testWidgets('Login, dialog, sending, internal note and template insertion',
      (tester) async {
    final api = DemoApi(), store = MemoryTokenStore();
    final session = ChatSession(api, store);
    await tester.pumpWidget(AvitoApp(session: session));
    await session.restore();
    await tester.pumpAndSettle();
    await tester.enterText(find.byType(TextField).first, 'test@example.test');
    await tester.enterText(find.byType(TextField).last, 'secret');
    await tester.tap(find.text('Войти'));
    await tester.pumpAndSettle();
    expect(find.text('Непрочитанные'), findsOneWidget);
    await tester.tap(find.text('Шины 18x7-8 NHS EKKA'));
    await tester.pumpAndSettle();
    await tester.enterText(find.byType(TextField), 'Подготовим счёт.');
    await tester.tap(find.byTooltip('Отправить'));
    await tester.pumpAndSettle();
    expect(api.messages.last['content']['text'], 'Подготовим счёт.');
    await tester.tap(find.byTooltip('Заметки и ответственный'));
    await tester.pumpAndSettle();
    final workspaceScroll = find
        .descendant(
            of: find.byType(ListView).last, matching: find.byType(Scrollable))
        .first;
    await tester.scrollUntilVisible(find.text('Добавить заметку'), 250,
        scrollable: workspaceScroll);
    await tester.enterText(find.byType(TextField).last, 'Запросить реквизиты');
    await tester.tap(find.text('Добавить заметку'));
    await tester.pumpAndSettle();
    expect(api.notes.single['body'], 'Запросить реквизиты');
    expect(api.messages.length, 4);
    await tester.scrollUntilVisible(find.text('Самовывоз'), -300,
        scrollable: workspaceScroll);
    await tester.tap(find.text('Самовывоз'));
    await tester.pumpAndSettle();
    expect(find.textContaining('Можно забрать со склада'), findsOneWidget);
    expect(api.messages.length, 4);
    await tester.pumpWidget(const SizedBox());
    await tester.pumpAndSettle();
    session.dispose();
    api.close();
  });
}
