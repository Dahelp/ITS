<?php
namespace app\services;

/** Removes catalogue dependencies atomically; commercial records keep their snapshots. */
class ProductDeletion
{
    private array $tables;
    private array $files = [];
    private string $imageRoot;

    public function __construct(?string $imageRoot = null)
    {
        $this->tables = array_fill_keys(\R::inspect(), true);
        $this->imageRoot = $imageRoot ?? WWW . '/images';
    }

    private function exists(string $table): bool
    {
        return isset($this->tables[$table]);
    }

    private function remove(string $table, string $where, array $params): void
    {
        if ($this->exists($table)) \R::exec("DELETE FROM `$table` WHERE $where", $params);
    }

    private function queueFile(string $folder, $name, string $table, string $column): void
    {
        $name = (string)$name;
        if ($name !== '' && basename($name) === $name) {
            $this->files[] = [$folder, $name, $table, $column];
        }
    }

    public function delete(int $id): array
    {
        if ($id <= 0) throw new \InvalidArgumentException('Некорректный ID товара.');
        $this->files = [];
        \R::begin();
        try {
            $lock = \R::getDatabaseAdapter()->getDatabase()->getDatabaseType() === 'mysql' ? ' FOR UPDATE' : '';
            $product = \R::getRow('SELECT * FROM product WHERE id = ?' . $lock, [$id]);
            if (!$product) throw new \InvalidArgumentException('Товар уже удалён или не найден.');
            foreach (['baseimg', 'mini'] as $folder) $this->queueFile('product/'.$folder, $product['img'] ?? '', 'product', 'img');
            $this->queueFile('product/unload', $product['unload_img'] ?? '', 'product', 'unload_img');
            if ($this->exists('gallery')) {
                foreach (\R::getCol('SELECT img FROM gallery WHERE product_id = ?', [$id]) as $name) {
                    $this->queueFile('product/gallery', $name, 'gallery', 'img');
                }
            }
            // A set cannot keep its meaning after one of its components is removed.
            if ($this->exists('plagins_complete_product')) {
                $completeIds = \R::getCol('SELECT DISTINCT complete_id FROM plagins_complete_product WHERE product_id = ?', [$id]);
                foreach ($completeIds as $completeId) {
                    if ($this->exists('plagins_complete')) {
                        $image = \R::getCell('SELECT img FROM plagins_complete WHERE id = ?', [$completeId]);
                        foreach (['baseimg', 'mini'] as $folder) $this->queueFile('complete/'.$folder, $image, 'plagins_complete', 'img');
                    }
                    if ($this->exists('plagins_complete_gallery')) {
                        foreach (\R::getCol('SELECT img FROM plagins_complete_gallery WHERE complete_id = ?', [$completeId]) as $image) {
                            $this->queueFile('complete/gallery', $image, 'plagins_complete_gallery', 'img');
                        }
                    }
                    $this->archiveAds('complete_id', (int)$completeId);
                    $this->remove('plagins_complete_gallery', 'complete_id = ?', [$completeId]);
                    $this->remove('plagins_complete_product', 'complete_id = ?', [$completeId]);
                    $this->remove('plagins_complete', 'id = ?', [$completeId]);
                }
            }
            if ($this->exists('review_product')) {
                $reviews = \R::getCol('SELECT review_id FROM review_product WHERE product_id = ?', [$id]);
                $this->remove('review_product', 'product_id = ?', [$id]);
                foreach ($reviews as $reviewId) {
                    if (\R::getCell('SELECT COUNT(*) FROM review_product WHERE review_id = ?', [$reviewId])) continue;
                    if ($this->exists('review_gallery')) {
                        foreach (\R::getCol('SELECT img FROM review_gallery WHERE review_id = ?', [$reviewId]) as $image) {
                            foreach (['gallery', 'mini'] as $folder) $this->queueFile('review/'.$folder, $image, 'review_gallery', 'img');
                        }
                    }
                    $this->remove('review_gallery', 'review_id = ?', [$reviewId]);
                    $this->remove('review', 'id = ?', [$reviewId]);
                }
            }
            foreach (['attribute_product','modification','gallery','service_product','product_attribute','product_tags',
                'product_bookmarks','product_wishlists','actions','in_stock','in_stock_history','mail_availability',
                'product_abbreviated','plagins_cross','product_cross','product_cross_context','certificate_assignments'] as $table) {
                $this->remove($table, 'product_id = ?', [$id]);
            }
            $this->remove('related_product', 'product_id = ? OR related_id = ?', [$id, $id]);
            $this->remove('similar_product', 'product_id = ? OR similar_id = ?', [$id, $id]);
            $this->remove('content_related', 'related_id = ?', [$id]);
            // Orders, requests and conversations have independent value and their own names/prices.
            foreach (['order_product','mail_oneclick','mail_request','chat_session'] as $table) {
                if ($this->exists($table)) {
                    $external = $table === 'order_product' ? ', external = 1, mod_id = 0' : '';
                    \R::exec("UPDATE `$table` SET product_id = 0$external WHERE product_id = ?", [$id]);
                }
            }
            if (!empty($product['article'])) $this->archiveAds('article', $product['article']);
            $this->remove('product', 'id = ?', [$id]);
            \R::commit();
        } catch (\Throwable $e) {
            \R::rollback();
            throw $e;
        }
        // Only unlink after the database commit, and never unlink shared image files.
        foreach ($this->files as [$folder, $name, $table, $column]) {
            if (\R::getCell("SELECT COUNT(*) FROM `$table` WHERE `$column` = ?", [$name])) continue;
            $file = $this->imageRoot . '/' . $folder . '/' . $name;
            if (is_file($file) && !unlink($file)) error_log('Product deletion: could not remove image in '.$folder);
        }
        return $product;
    }

    private function archiveAds(string $column, $value): void
    {
        if (!$this->exists('avito_ad') || !isset(\R::inspect('avito_ad')[$column])) return;
        $fields = \R::inspect('avito_ad');
        $set = "status = 'archived', `$column` = NULL";
        foreach (['article', 'complete_id'] as $link) {
            if ($link !== $column && isset($fields[$link])) $set .= ", `$link` = NULL";
        }
        if (isset($fields['stock_quantity'])) $set .= ', stock_quantity = 0';
        \R::exec("UPDATE avito_ad SET $set WHERE `$column` = ?", [$value]);
    }
}
