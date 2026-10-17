import 'package:flutter_test/flutter_test.dart';
import 'package:its_avito/brand.dart';

void main() {
  test('Conversation dates use Moscow day boundaries and retain older years',
      () {
    int stamp(String date) =>
        DateTime.parse(date).millisecondsSinceEpoch ~/ 1000;
    final now = DateTime.parse('2026-10-17T21:30:00Z');
    expect(dayHeading(stamp('2026-10-17T21:00:00Z'), now: now), 'Сегодня');
    expect(dayHeading(stamp('2026-10-17T20:59:00Z'), now: now), 'Вчера');
    expect(dayHeading(stamp('2026-10-15T12:00:00Z'), now: now), '15 октября');
    expect(
        dayHeading(stamp('2025-10-15T12:00:00Z'), now: now), '15 октября 2025');
    expect(clockLabel(stamp('2026-10-17T21:05:00Z')), '00:05');
    expect(dayHeading(null, now: now), '');
  });
}
