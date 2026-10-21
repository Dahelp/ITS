import 'api.dart';
import 'dart:typed_data';

/// Local UI fixture, enabled only with --dart-define=DEMO=true. No Avito calls.
class DemoApi extends ChatApi {
  @override
  Future<Json> upload(
          String operation, String chat, String name, Uint8List bytes) async =>
      throw const ApiFailure(400, 'Отправка файлов в демо отключена.');
  int revision = 1;
  final List<Json> messages = [];
  final List<Json> notes = [];
  final List<Json> templates = [
    {
      'id': 1,
      'title': 'Самовывоз',
      'body':
          'Можно забрать со склада в Подольске. Работаем с понедельника по пятницу, с 9:00 до 17:00.',
      'can_edit': true
    }
  ];
  int assignee = 0;
  DemoApi() {
    final now = DateTime.now().millisecondsSinceEpoch ~/ 1000;
    messages.addAll([
      {
        'id': '1',
        'created': now - 7200,
        'direction': 'in',
        'type': 'text',
        'content': {'text': 'Здравствуйте! Шины 18x7-8 есть в наличии?'}
      },
      {
        'id': '2',
        'created': now - 6800,
        'direction': 'out',
        'read': true,
        'type': 'text',
        'content': {
          'text': 'Добрый день! Да, есть на складе. Сколько штук вам нужно?'
        }
      },
      {
        'id': '3',
        'created': now - 300,
        'direction': 'in',
        'type': 'text',
        'content': {
          'text': 'Нужно 4 штуки. Можно получить счёт на организацию?'
        }
      },
    ]);
  }
  @override
  Future<Json> call(String operation,
      {Json query = const {}, Json? body}) async {
    await Future<void>.delayed(const Duration(milliseconds: 150));
    switch (operation) {
      case 'login':
        return {
          'access_token': 'd' * 64,
          'user': {'id': 1, 'name': 'Юрий · Демо', 'role': 'admin'}
        };
      case 'me':
        return {'id': 1, 'name': 'Юрий · Демо', 'role': 'admin'};
      case 'events':
        return {'revision': revision};
      case 'chats':
        final data = [
          {
            'id': 'demo-1',
            'title': 'Шины 18x7-8 NHS EKKA',
            'context': {
              'value': {'id': '2443938038', 'title': 'Шины 18x7-8 NHS EKKA'}
            },
            'is_unread': true,
            'assigned_to': assignee == 1 ? 'Юрий' : '',
            'assignee_id': assignee,
            'last_message': messages.last
          },
          {
            'id': 'demo-2',
            'title': 'Шина 21x8-9 C601 · Шинокомплект',
            'is_unread': false,
            'last_message': {
              'created': DateTime.now().millisecondsSinceEpoch ~/ 1000 - 3600,
              'direction': 'out',
              'read': true,
              'type': 'text',
              'content': {'text': 'Здравствуйте, в наличии 14 шт.'}
            }
          },
          {
            'id': 'demo-3',
            'title': 'Шины 29x9-14 / 29x11-14 EKKA',
            'is_unread': false,
            'last_message': {
              'created': DateTime.now().millisecondsSinceEpoch ~/ 1000 - 86400,
              'direction': 'out',
              'type': 'text',
              'content': {
                'text':
                    'Комплект будет стоить 42 400 ₽. Отправим транспортной компанией.'
              }
            }
          },
        ];
        final owner = '${query['assignee'] ?? 'all'}';
        return {
          'chats': (query['unread'] == 1 ? [data.first] : data)
              .where((c) =>
                  owner == 'all' ||
                  (c['assignee_id'] ?? 0) ==
                      (owner == 'mine' ? 1 : int.tryParse(owner) ?? 0))
              .toList(),
          'next_offset': 3,
          'has_more': false,
          'actor_id': 1,
          'managers': [
            {'id': 1, 'name': 'Юрий'}
          ]
        };
      case 'messages':
        return {'messages': messages.reversed.toList()};
      case 'listing':
        return {
          'id': 198,
          'title': 'Шины 18x7-8 NHS EKKA',
          'description': 'Новые шины для квадроцикла. Цена указана за штуку.',
          'price': 10600,
          'stock': 14,
          'reserve': 4,
          'available': 10,
          'images': const [],
          'edit_url': 'https://its-center.ru/admin/avito/edit?id=198',
          'product_url': 'https://its-center.ru/admin/product/edit?id=9538'
        };
      case 'read':
        return {'ok': true};
      case 'send':
        messages.add({
          'id': '${messages.length + 1}',
          'created': DateTime.now().millisecondsSinceEpoch ~/ 1000,
          'direction': 'out',
          'type': 'text',
          'content': {'text': body!['text']}
        });
        revision++;
        return {'ok': true};
      case 'workspace':
        return {
          'managers': [
            {'id': 1, 'name': 'Юрий'}
          ],
          'assignee': assignee,
          'notes': notes,
          'templates': templates
        };
      case 'assign':
        assignee = int.parse('${body!['manager']}');
        return {'ok': true};
      case 'note':
        notes.add({
          'body': body!['body'],
          'author': 'Юрий',
          'created_at': DateTime.now().millisecondsSinceEpoch ~/ 1000
        });
        return {'ok': true};
      case 'template-save':
        final id = int.parse('${body!['id']}');
        templates.removeWhere((t) => t['id'] == id);
        templates.add({
          ...body,
          'id': id == 0 ? templates.length + 1 : id,
          'can_edit': true
        });
        return {'ok': true};
      case 'template-delete':
        templates.removeWhere((t) => t['id'] == body!['id']);
        return {'ok': true};
      case 'sessions':
        return {
          'sessions': [
            {
              'device_name': 'Демонстрация',
              'current': true,
              'created_at': DateTime.now().millisecondsSinceEpoch ~/ 1000
            }
          ]
        };
      case 'logout':
      case 'logout-all':
        return {'ok': true};
      case 'capabilities':
        throw const ApiFailure(400, 'Отправка файлов в демо отключена.');
      default:
        throw const ApiFailure(400, 'Операция недоступна в демо.');
    }
  }
}
