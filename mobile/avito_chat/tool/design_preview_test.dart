// Visual QA: flutter test tool/design_preview_test.dart --update-goldens
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:its_avito/api.dart';
import 'package:its_avito/demo.dart';
import 'package:its_avito/main.dart';
import 'package:its_avito/push.dart';

void main() {
  testWidgets('Render branded screens with synthetic customer data',
      (tester) async {
    tester.view.physicalSize = const Size(390, 844);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    final font = File('C:/Windows/Fonts/arial.ttf');
    if (font.existsSync()) {
      final loader = FontLoader('Roboto')
        ..addFont(Future.value(ByteData.sublistView(font.readAsBytesSync())));
      await loader.load();
    }
    final icons = FontLoader('MaterialIcons')
      ..addFont(Future.value(ByteData.sublistView(File(
              'C:/src/flutter/bin/cache/artifacts/material_fonts/MaterialIcons-Regular.otf')
          .readAsBytesSync())));
    await icons.load();
    final api = DemoApi();
    final session = ChatSession(api, MemoryTokenStore())
      ..user = {'id': 1, 'name': 'Юрий Бабачков', 'role': 'admin'}
      ..restoring = false;
    final push = PushController(session);
    await tester.pumpWidget(AvitoApp(session: session, push: push));
    await tester.pumpAndSettle();
    await tester.runAsync(() => precacheImage(
        const AssetImage('assets/its-center.jpg'),
        tester.element(find.byType(MaterialApp))));
    await tester.pumpAndSettle();
    await expectLater(find.byType(MaterialApp),
        matchesGoldenFile('../../../outputs/mobile/its-0.4.0-chats.png'));
    await tester.tap(find.text('Шины 18x7-8 NHS EKKA'));
    await tester.pumpAndSettle();
    await expectLater(
        find.byType(MaterialApp),
        matchesGoldenFile(
            '../../../outputs/mobile/its-0.4.0-conversation.png'));
    await tester.pageBack();
    await tester.pumpAndSettle();
    await tester.tap(find.text('Кабинет').last);
    await tester.pumpAndSettle();
    await expectLater(find.byType(MaterialApp),
        matchesGoldenFile('../../../outputs/mobile/its-0.4.0-account.png'));
    await tester.pumpWidget(const SizedBox());
    await tester.pumpAndSettle();
    push.dispose();
    session.dispose();
    api.close();
  });
}
