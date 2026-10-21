# Человечек в блоке консультации

Только `/home-preview`: мультяшный персонаж сохранён, элемент под ним перекрашен в корпоративный красный, ноутбук сделан контрастным серебристо-серым. Убрана прозрачность 0.9 у нового изображения. Основные страницы продолжают использовать исходный файл.

Файл: `public/images/home-preview/callback-mascot-red.webp`, 600×959, прозрачный фон. Генерация встроенным imagegen из `public/images/w.png`, затем уточнение ноутбука. Конвертация в WebP с сохранением альфа-канала.

Первый промпт:

Edit this original 3D cartoon website mascot illustration. Preserve the exact friendly cartoon man, exaggerated elongated proportions, brown swept hair, beard, blue business suit, red tie, brown shoes, laptop, seated pose and the speech bubble shape beneath him with its three white circular dots. Improve crispness and clean polished 3D rendering subtly. The required change: recolor the entire speech bubble seat from bright pink/magenta to the corporate warm brick red used by the website buttons: main hue #c0392b, lit areas #d9473b and deep shaded areas #8f1f1f. Absolutely NO pink, fuchsia, magenta or neon red on the seat. Restrained rich warm red, soft matte/satin material, natural shading, white dots remain white. Do not change mascot style to realistic people. Full figure and full speech bubble visible, comfortable margins, transparent background, no text, no extra objects.

Уточняющий промпт:

Refine this exact approved 3D cartoon illustration. Keep the same man, face, hairstyle, beard, long cartoon proportions, seated pose, blue suit, red tie, shoes and warm corporate brick-red speech bubble seat (#c0392b with #d9473b lit areas and #8f1f1f shadows), all three white dots. Make character crisp, rich saturated colors and stronger clean contrast, no pale washed-out lighting. IMPORTANT: change the near-white laptop to a distinct medium silver-gray / graphite metallic laptop, visible dark-gray edge and hinge, clear dimensional bevels, subtle brushed metal material and shading. Laptop must stand out strongly against a white website background even at small display size; avoid pure white laptop panels. Keep entire figure and seat visible, no cropping. Transparent background, no text, retain polished friendly cartoon style, no realism.
