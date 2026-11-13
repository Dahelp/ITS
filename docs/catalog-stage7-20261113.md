# Этап 7. Фильтры и SEO-посадочные

Проверено и опубликовано 13.11.2026. Изменены только `public/js/catalog-facets.js` и шаблон `app/views/itscenter/Category/view.php`. Товары, привязки фильтров, настройки категорий и SEO-записи не редактировались.

## Исправления

1. На мобильном экране AJAX-обновление сбрасывало внутреннюю прокрутку панели. До исправления позиция менялась с 908 на 0 px; после — сохраняется 908 px. Сохраняются открытая панель, блокировка прокрутки фона, состояние групп и раскрытие вариантов.
2. При переходах между посадочными обновлялись обычные meta/title/canonical, но оставались старые Open Graph. Теперь синхронизируются og:title, og:description, og:url и og:image; отсутствующие в новой странице элементы удаляются.
3. Без JavaScript отложенные карточки не загружались. Добавлена noscript-ссылка «Показать все N товаров этой страницы без JavaScript». По ней сервер отдаёт всю выбранную страницу, без отложенной порции. Служебный URL содержит perpage, получает существующий noindex, follow и canonical чистой категории/посадочной. Объём ограничен обычными 24/48/96 товарами. Стандартная подгрузка для посетителей с JS сохранена.

## Проверка интерфейса на боевом сайте

- Ширины 320, 360, 390, 768, 1024, 1440 px: горизонтального переполнения нет. До 420 px одна карточка, на планшете две, на узком десктопе три, на широком десктопе четыре. Адаптивные пороги не менялись.
- На десктопе нет вложенной прокрутки панели или групп. Длинные группы раскрываются через «Показать ещё»; отмеченный восьмой вариант Superguider остаётся видимым после сворачивания.
- На мобильном фильтры открываются отдельной нижней панелью с одной прокруткой содержимого и доступной кнопкой «К товарам». Закрытие восстанавливает прокрутку фона, Escape возвращает фокус кнопке фильтров.
- Подтверждены автоматическое применение, наличие, сортировка, OR внутри размера, AND между производителем и размером, сброс, Назад/Вперёд. Сброс очищает фильтры и наличие, сохраняя сортировку и размер страницы.
- EKKA: 20 товаров; EKKA + 12-16.5: 5; EKKA + (12-16.5 OR 10-16.5): 12; добавление наличия: 6. Проверка относится к моменту замера, остатки меняются.
- Страница 24: 12 начальных + 12 дополнительных уникальных карточек. Страница 48: 12 + 36. Полный HTML без JS: соответственно 24 и 48 карточек, HTTP 200.

Это проверка адаптивных размеров браузера, а не тест на физических iPhone/Android.

## SEO

Выполнены 20 HTTP-сценариев: категория, производитель, уточнение, два размера, безопасное перенаправление неоднозначного адреса, наличие, сортировка, неизвестный ID, пагинация, неизвестный alias и 10 существующих посадочных в разных категориях. В выборке есть размер со слешем `15.5/80-24`.

Проверены HTTP, один H1, Title, Description, canonical, robots, ручные H1 и соответствие количества результатов SQL-условиям. Рабочие посадочные отвечают 200; неизвестный alias — 404. Уточнённый подбор получает noindex, follow и canonical основной чистой страницы. Чистые посадочные сохраняют существующие SEO-настройки. Мультивыбор не создаёт SEO-записей автоматически.

Сохранена действующая общая политика пагинации сайта: page>1 закрыт noindex, follow, canonical указывает на первую страницу. Изначальная проверка ожидала иную политику; после проверки общего View и layout ожидание исправлено, сама политика не менялась.

В БД 730 активных SEO-привязок, отсутствующих category/attribute-ссылок нет. У 13 привязок режима landing отсутствуют видимые товары с соответствующим значением в категории и её подкатегориях. Это отдельные замечания по данным, а не повод автоматически создавать пустые индексируемые страницы или назначать перенаправления. Пример `/category/shiny-dlya-minipogruzchikov/30x10-20` действительно отвечает 404. Для остальных 12 отсутствие товаров установлено по БД; все они отдельно по HTTP не обходились. Перенаправление возможно только после подтверждения эквивалентной целевой страницы.

## Проверки и публикация

Прошли CatalogSelectionTest, CatalogFacetRouteTest, CatalogFacetsDatabaseTest и CatalogFacetBridgeDatabaseTest. Проверены OR/AND, счётчики с исключением собственной группы, нулевые варианты, неизвестные ID, изоляция категорий, подтверждённые характеристики, резервный старый источник и неизменность связей.

Публикация с проверкой исходных SHA256 и резервной копией: `/home/s/shinaspec/catalog-stage7-20261113`. Итоговые SHA двух файлов проверены. Записей в БД установщик не выполняет; контрольные суммы таблиц настроек до/после HTTP-аудита совпали.

Артефакты: `outputs/stage7-http-audit.json`, `outputs/stage7-browser-check.json`, `outputs/stage7-responsive.json`, `outputs/stage7-pagination-check.json`, `outputs/stage7-publication-verification.json`. Скриншоты: `outputs/stage7-desktop-live.png`, `outputs/stage7-mobile-live.png`.

## Привязки для последующей проверки данных

| ID | Адрес |
| --- | --- |
| 128 | [https://its-center.ru/category/shiny-dlya-minipogruzchikov/30x10-20](https://its-center.ru/category/shiny-dlya-minipogruzchikov/30x10-20) |
| 393 | [https://its-center.ru/category/shiny-dlya-minipogruzchikov/master-devil](https://its-center.ru/category/shiny-dlya-minipogruzchikov/master-devil) |
| 30 | [https://its-center.ru/category/gruzovye-shiny/16-00-20](https://its-center.ru/category/gruzovye-shiny/16-00-20) |
| 36 | [https://its-center.ru/category/shiny-dlya-ekskavatorov-pogruzchikov/16-70-24](https://its-center.ru/category/shiny-dlya-ekskavatorov-pogruzchikov/16-70-24) |
| 38 | [https://its-center.ru/category/atv/16x6-50-7](https://its-center.ru/category/atv/16x6-50-7) |
| 45 | [https://its-center.ru/category/shiny-dlya-shahtnoy-tehniki/18-00r25](https://its-center.ru/category/shiny-dlya-shahtnoy-tehniki/18-00r25) |
| 98 | [https://its-center.ru/category/atv/26x12-12](https://its-center.ru/category/atv/26x12-12) |
| 127 | [https://its-center.ru/category/gruzovye-shiny/305-70r22-5](https://its-center.ru/category/gruzovye-shiny/305-70r22-5) |
| 141 | [https://its-center.ru/category/atv/4.10-4](https://its-center.ru/category/atv/4.10-4) |
| 395 | [https://its-center.ru/category/shiny-dlya-ekskavatorov-pogruzchikov/master-force](https://its-center.ru/category/shiny-dlya-ekskavatorov-pogruzchikov/master-force) |
| 397 | [https://its-center.ru/category/shiny-dlya-vilochnyh-pogruzchikov/master-lift](https://its-center.ru/category/shiny-dlya-vilochnyh-pogruzchikov/master-lift) |
| 399 | [https://its-center.ru/category/shiny-dlya-frontalnyh-pogruzchikov/master-loader](https://its-center.ru/category/shiny-dlya-frontalnyh-pogruzchikov/master-loader) |
| 401 | [https://its-center.ru/category/shiny-dlya-ekskavatorov-pogruzchikov/master-power](https://its-center.ru/category/shiny-dlya-ekskavatorov-pogruzchikov/master-power) |
