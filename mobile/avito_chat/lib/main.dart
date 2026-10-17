import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'api.dart';
import 'screens.dart';
import 'demo.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  const demo = bool.fromEnvironment('DEMO');
  final session = ChatSession(demo ? DemoApi() : ChatApi(),
      demo ? MemoryTokenStore() : DeviceTokenStore());
  runApp(AvitoApp(session: session));
  session.restore();
}

class AvitoApp extends StatelessWidget {
  final ChatSession session;
  const AvitoApp({super.key, required this.session});
  @override
  Widget build(BuildContext context) => MaterialApp(
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
            colorScheme:
                ColorScheme.fromSeed(seedColor: const Color(0xff146b60)),
            scaffoldBackgroundColor: const Color(0xfff3f6f5),
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
                      key: ValueKey(session.user!['id']), session: session);
            }),
      );
}
