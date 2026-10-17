import 'api.dart';

String chatTitle(Json chat) =>
    '${object(object(chat['context'])['value'])['title'] ?? chat['title'] ?? 'Диалог Авито'}';
String preview(Json message) {
  final content = object(message['content']);
  return switch (message['type']) {
    'text' => '${content['text'] ?? ''}',
    'image' => 'Фотография',
    'voice' => 'Голосовое сообщение',
    'link' => '${object(content['link'])['text'] ?? 'Ссылка'}',
    'location' => 'Местоположение',
    _ => 'Сообщение этого типа доступно в Авито',
  };
}

DateTime? messageTime(dynamic value) {
  final seconds = num.tryParse('$value');
  if (seconds == null || seconds <= 0) return null;
  return DateTime.fromMillisecondsSinceEpoch((seconds * 1000).round(),
          isUtc: true)
      .add(const Duration(hours: 3));
}

String dateLabel(dynamic value, {bool full = false, DateTime? now}) {
  final date = messageTime(value);
  if (date == null) return '';
  final today = (now ?? DateTime.now()).toUtc().add(const Duration(hours: 3));
  String two(int n) => '$n'.padLeft(2, '0');
  final day = '${two(date.day)}.${two(date.month)}.${date.year}';
  final time = '${two(date.hour)}:${two(date.minute)}';
  if (full) return '$day $time МСК';
  return date.year == today.year &&
          date.month == today.month &&
          date.day == today.day
      ? time
      : day;
}

Uri? safeUrl(dynamic value) {
  final uri = Uri.tryParse('$value');
  return uri != null &&
          uri.scheme == 'https' &&
          uri.host.isNotEmpty &&
          uri.userInfo.isEmpty
      ? uri
      : null;
}

String? imageUrl(Json message) {
  final sizes = object(object(object(message['content'])['image'])['sizes']);
  final candidates = sizes.entries.toList()
    ..sort((a, b) => (int.tryParse(b.key.split('x').first) ?? 0)
        .compareTo(int.tryParse(a.key.split('x').first) ?? 0));
  for (final entry in candidates) {
    final uri = safeUrl(entry.value);
    if (uri != null) return '$uri';
  }
  return null;
}

List<Json> mergeMessages(List<Json> current, List<Json> incoming) {
  final map = <String, Json>{};
  for (final message in [...current, ...incoming]) {
    final id = '${message['id']}';
    if (id != 'null') map[id] = message;
  }
  return map.values.toList()
    ..sort((a, b) => (num.tryParse('${a['created']}') ?? 0)
        .compareTo(num.tryParse('${b['created']}') ?? 0));
}

String? documentToken(Json message) {
  if (message['direction'] != 'out' || message['type'] != 'text') return null;
  for (final match in RegExp(r'https://[^\s]+').allMatches(preview(message))) {
    final uri = safeUrl(match.group(0));
    if (uri?.host == 'its-center.ru' &&
        uri?.path == '/avito-document/download') {
      final token = uri?.queryParameters['token'];
      if (token != null && RegExp(r'^[a-f0-9]{64}$').hasMatch(token)) {
        return token;
      }
    }
  }
  return null;
}
