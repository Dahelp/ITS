<?php
/** Product photos share one ordered list between the page and the zoom viewer. */
$galleryProduct = $galleryProduct ?? $product;
$galleryKind = $galleryKind ?? 'product';
$productPhotos = [['type' => 'base', 'file' => $galleryProduct->img, 'alt' => $galleryProduct->name . ' — основной вид']];
foreach ($gallery as $photoIndex => $photo) {
    $filename = is_object($photo) ? $photo->img : $photo['img'];
    if ($filename) $productPhotos[] = ['type' => 'gallery', 'file' => $filename, 'alt' => $galleryProduct->name . ' — дополнительное фото ' . count($productPhotos)];
}
$photoEscape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$renderPhoto = static function (array $photo, bool $thumb = false, bool $eager = false) use ($galleryKind) {
    if ($galleryKind === 'complete') {
        $dir = $photo['type'] === 'base' ? 'baseimg' : 'gallery';
        return \app\helpers\Img::picture(['fallback' => '/images/complete/' . $dir . '/' . ltrim((string)$photo['file'], '/')], $photo['alt'], $thumb ? 120 : 600, $thumb ? 120 : 600, ['eager' => $eager]);
    }
    return \app\helpers\Img::productPicture($thumb && $photo['type'] === 'base' ? 'mini' : $photo['type'], $photo['file'], $photo['alt'], ['eager' => $eager]);
};
?>
<link rel="stylesheet" href="/css/product-gallery.css?v=<?= (int)filemtime(WWW . '/css/product-gallery.css') ?>">
<script defer src="/js/product-gallery.js?v=<?= (int)filemtime(WWW . '/js/product-gallery.js') ?>"></script>
<section class="pg" data-product-gallery aria-label="Фотографии товара">
  <div class="pg-stage">
    <?php foreach ($productPhotos as $index => $photo):
        $directory = '/images/' . $galleryKind . ($photo['type'] === 'base' ? '/baseimg/' : '/gallery/');
        $file = (string)$photo['file'];
        $photoUrl = preg_match('~^(?:/|https?://)~i', $file) ? $file : $directory . $file;
        if (!$file) $photoUrl = '/images/' . (\ishop\App::$app->getProperty('og_logo') ?: 'Logo_round_1200.jpg');
        // Prefer the full resolution original when it exists, keeping older uploads compatible.
        $original = '/images/' . $galleryKind . '/original/' . basename($file);
        if ($file && is_file(WWW . $original)) $photoUrl = $original;
    ?>
      <a class="pg-photo" href="<?= $photoEscape($photoUrl) ?>" data-pg-photo data-pg-index="<?= $index ?>" aria-label="Увеличить: <?= $photoEscape($photo['alt']) ?>"<?= $index ? ' hidden' : '' ?>>
        <?= $renderPhoto($photo, false, $index === 0) ?>
      </a>
    <?php endforeach; ?>
    <span class="pg-enlarge" aria-hidden="true">⤢ Увеличить</span>
    <?php if (count($productPhotos) > 1): ?>
      <button class="pg-arrow pg-arrow--prev" type="button" data-pg-prev aria-label="Предыдущее фото">‹</button>
      <button class="pg-arrow pg-arrow--next" type="button" data-pg-next aria-label="Следующее фото">›</button>
    <?php endif; ?>
  </div>
  <div class="pg-meta"><span>Нажмите на фото, чтобы увеличить</span><span data-pg-count aria-live="polite">1 / <?= count($productPhotos) ?></span></div>
  <?php if (count($productPhotos) > 1): ?>
    <div class="pg-thumbs" aria-label="Выбор фотографии">
      <?php foreach ($productPhotos as $index => $photo): ?>
        <button class="pg-thumb" type="button" data-pg-thumb="<?= $index ?>" aria-label="Фото <?= $index + 1 ?>: <?= $photoEscape($photo['alt']) ?>" aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>">
          <?= $renderPhoto($photo, true) ?>
        </button>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
