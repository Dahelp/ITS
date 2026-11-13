# Изображения категорий цепей — 30.10.2026

На боевом its-center.ru установлены три новые фотографии, созданные встроенным image_gen:

| Категория | ID | Изображение |
|---|---:|---|
| Цепи противоскольжения | 43 | snow-chains-category-v1.webp |
| Цепи противоскольжения для спецтехники | 44 | snow-chains-special-equipment-category-v1.webp |
| Цепи противоскольжения для вилочных погрузчиков | 45 | snow-chains-forklift-category-v1.webp |

Основная категория представлена парой промышленных колёс: тракторное с Сотой и погрузочное с Лесенкой. Для спецтехники создано отдельное изображение колеса с глубоким шевронным протектором и Сотой; для вилочных погрузчиков — с блочным протектором и Лесенкой. Все на белом фоне, без указания поставщика, логотипов и шипов.

Оригинальные PNG и точные промпты: `outputs/chain-category-images-20261030/`, файл `prompts.json`. Производные WebP: `public/images/category/baseimg/` (600×600) и `public/images/category/mini/` (240×240). Полная карта — `app/data/chain-category-images.json`.

Подготовка файлов: `php bin/prepare_chain_category_images.php`. Установка: `php scripts/install_chain_category_images.php --apply`; изменяется только `category.img` для трёх указанных записей. Прежние данные сохранены на сервере в `storage/chain-category-images/before-20261030-091702.json`. Сброшены соответствующие кеши категорий и меню.

Проверка: `php bin/verify_chain_category_images.php` — 3 привязки, 6 HTTP-проверок файлов и 2 страницы каталога; ошибок нет. В браузере проверены основная категория на `/catalog` и обе подкатегории на `/catalog/cepi-protivoskolzheniya`. Снимки — `main-category-installed.png` и `subcategories-installed.png` в папке исходников. Остальные поля категорий не изменились.
