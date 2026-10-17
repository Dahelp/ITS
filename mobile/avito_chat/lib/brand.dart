import 'package:flutter/material.dart';
import 'api.dart';
import 'models.dart';

const itsNavy = Color(0xff003b4d);
const itsRed = Color(0xffff3525);

class BrandMark extends StatelessWidget {
  final double size;
  const BrandMark({super.key, this.size = 40});
  @override
  Widget build(BuildContext context) => ClipOval(
      child: Image.asset('assets/its-center.jpg',
          width: size, height: size, fit: BoxFit.cover));
}

class ProductAvatar extends StatelessWidget {
  final Json chat;
  final double size;
  const ProductAvatar({super.key, required this.chat, this.size = 52});
  @override
  Widget build(BuildContext context) {
    final value = object(object(chat['context'])['value']);
    final images = object(value['images']);
    final main = object(images['main']);
    final url =
        safeUrl(main['140x105'] ?? main['300x225'] ?? value['image_url']);
    return ClipRRect(
        borderRadius: BorderRadius.circular(16),
        child: Container(
            width: size,
            height: size,
            color: const Color(0xffedf2f4),
            child: url == null
                ? const Icon(Icons.inventory_2_outlined, color: itsNavy)
                : Image.network('$url',
                    fit: BoxFit.cover,
                    errorBuilder: (_, __, ___) => const Icon(
                        Icons.inventory_2_outlined,
                        color: itsNavy))));
  }
}

String dayHeading(dynamic timestamp, {DateTime? now}) {
  final day = messageTime(timestamp);
  if (day == null) return '';
  final today = (now ?? DateTime.now()).toUtc().add(const Duration(hours: 3));
  final date = DateTime.utc(day.year, day.month, day.day);
  final difference =
      DateTime.utc(today.year, today.month, today.day).difference(date).inDays;
  if (difference == 0) return 'Сегодня';
  if (difference == 1) return 'Вчера';
  const months = [
    'января',
    'февраля',
    'марта',
    'апреля',
    'мая',
    'июня',
    'июля',
    'августа',
    'сентября',
    'октября',
    'ноября',
    'декабря'
  ];
  return '${day.day} ${months[day.month - 1]}${day.year == today.year ? '' : ' ${day.year}'}';
}

String clockLabel(dynamic timestamp) {
  final date = messageTime(timestamp);
  return date == null
      ? ''
      : '${date.hour.toString().padLeft(2, '0')}:${date.minute.toString().padLeft(2, '0')}';
}
