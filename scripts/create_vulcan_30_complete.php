<?php

use app\models\AppModel;
use app\models\admin\Avito;

require dirname(__DIR__) . '/config/init.php';
require CONF . '/db_bootstrap.php';

\ishop\App::$app = \ishop\Registry::instance();
foreach ((array)require CONF . '/params.php' as $key => $value) {
    \ishop\App::$app->setProperty($key, $value);
}

function normalizedSize(string $value): string
{
    $value = mb_strtolower($value, 'UTF-8');
    $value = str_replace(['×', 'х', '–', '—', ' '], ['x', 'x', '-', '-', ''], $value);
    return $value;
}

function findVulcanTire(string $size): array
{
    $rows = \R::getAll(
        "SELECT p.*, b.name AS brand_name
         FROM product p
         LEFT JOIN brand b ON b.id = p.brand_id
         WHERE p.hide != 'hide'
           AND (LOWER(p.name) LIKE '%vulcan%' OR LOWER(p.name) LIKE '%вулкан%')
         ORDER BY CASE WHEN LOWER(COALESCE(b.name, '')) = 'ekka' THEN 0 ELSE 1 END,
                  CASE WHEN p.quantity > 0 THEN 0 ELSE 1 END,
                  p.id DESC"
    );

    foreach ($rows as $row) {
        if (strpos(normalizedSize((string)$row['name']), $size) !== false) {
            return $row;
        }
    }

    throw new RuntimeException("Не найдена активная шина Vulcan размера {$size}");
}

function copyCompleteImage(string $source, string $target): void
{
    if (!is_file($source)) {
        return;
    }
    $dir = dirname($target);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException("Не удалось создать каталог {$dir}");
    }
    if (!is_file($target) && !copy($source, $target)) {
        throw new RuntimeException("Не удалось скопировать изображение {$source}");
    }
}

$front = findVulcanTire('30x9-14');
$rear = findVulcanTire('30x11-14');
$productIds = [(int)$front['id'], (int)$rear['id']];
sort($productIds);

$existingId = 0;
foreach (\R::getAll('SELECT id FROM plagins_complete ORDER BY id') as $candidate) {
    $candidateId = (int)$candidate['id'];
    $ids = array_map('intval', \R::getCol(
        'SELECT product_id FROM plagins_complete_product WHERE complete_id = ? ORDER BY product_id',
        [$candidateId]
    ));
    if ($ids === $productIds) {
        $quantities = \R::getAssoc(
            'SELECT product_id, qty FROM plagins_complete_product WHERE complete_id = ?',
            [$candidateId]
        );
        if ((int)($quantities[$productIds[0]] ?? 0) === 2 && (int)($quantities[$productIds[1]] ?? 0) === 2) {
            $existingId = $candidateId;
            break;
        }
    }
}

$name = 'Комплект шин 30x9-14/30x11-14 Vulcan EKKA для квадроциклов';

if ($existingId > 0) {
    $completeId = $existingId;
    echo "Комплект уже существует: ID {$completeId}\n";
} else {
    \R::begin();
    try {
        \R::exec(
            'INSERT INTO plagins_complete (name, category_id, content, title, description, keywords, alias, hide) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $name,
                (int)$front['category_id'],
                '<p><strong>Комплект шин Vulcan EKKA для квадроцикла</strong> состоит из четырёх новых шин: двух передних 30x9-14 и двух задних 30x11-14.</p><p>Шины подобраны по модели и рисунку протектора. Цена указана за полный комплект из 4 шин.</p>',
                $name . ' — купить в ИТС-Центр',
                'Комплект новых шин Vulcan EKKA для квадроцикла: 2 шины 30x9-14 и 2 шины 30x11-14. Цена указана за полный комплект.',
                'комплект шин 30x9-14 30x11-14, Vulcan EKKA, шины для квадроцикла',
                '',
                'show',
            ]
        );
        $completeId = (int)\R::getCell('SELECT LAST_INSERT_ID()');
        $alias = AppModel::createAlias('plagins_complete', 'alias', $name, $completeId);
        \R::exec('UPDATE plagins_complete SET alias = ? WHERE id = ?', [$alias, $completeId]);

        $frontImage = trim((string)($front['img'] ?? ''));
        if ($frontImage !== '') {
            $ext = strtolower(pathinfo($frontImage, PATHINFO_EXTENSION));
            $mainName = 'vulcan-30-complete-' . $completeId . ($ext ? '.' . $ext : '');
            copyCompleteImage(WWW . '/images/product/baseimg/' . $frontImage, WWW . '/images/complete/baseimg/' . $mainName);
            copyCompleteImage(WWW . '/images/product/mini/' . $frontImage, WWW . '/images/complete/mini/' . $mainName);
            \R::exec('UPDATE plagins_complete SET img = ? WHERE id = ?', [$mainName, $completeId]);
        }

        foreach ([$front, $rear] as $product) {
            \R::exec(
                'INSERT INTO plagins_complete_product (complete_id, product_id, qty, price, discount, discount_amount) VALUES (?, ?, 2, ?, 0, 0)',
                [$completeId, (int)$product['id'], (float)$product['price']]
            );
        }

        $rearImage = trim((string)($rear['img'] ?? ''));
        if ($rearImage !== '') {
            $ext = strtolower(pathinfo($rearImage, PATHINFO_EXTENSION));
            $galleryName = 'vulcan-30-rear-' . $completeId . ($ext ? '.' . $ext : '');
            copyCompleteImage(WWW . '/images/product/baseimg/' . $rearImage, WWW . '/images/complete/gallery/' . $galleryName);
            if (is_file(WWW . '/images/complete/gallery/' . $galleryName)) {
                \R::exec('INSERT INTO plagins_complete_gallery (complete_id, img) VALUES (?, ?)', [$completeId, $galleryName]);
            }
        }

        \R::commit();
        echo "Создан комплект: ID {$completeId}\n";
    } catch (Throwable $e) {
        \R::rollback();
        throw $e;
    }
}

$adId = Avito::createArchivedForComplete($completeId);
$ad = \R::load('avito_ad', $adId);

echo 'Передняя шина: ID ' . (int)$front['id'] . ', артикул ' . (string)$front['article'] . ', остаток ' . (int)$front['quantity'] . "\n";
echo 'Задняя шина: ID ' . (int)$rear['id'] . ', артикул ' . (string)$rear['article'] . ', остаток ' . (int)$rear['quantity'] . "\n";
echo 'Объявление Avito: ID ' . $adId . ', внешний ID ' . (string)$ad->ad_external_id . ', статус ' . (string)$ad->status . "\n";

