<?php

namespace app\models\admin;

use app\models\AppModel;

class Certificate extends AppModel
{
    public $attributes = [
        'document_type' => 'declaration',
        'number' => '',
        'name' => '',
        'date_start' => null,
        'date_end' => null,
        'registry_url' => '',
        'file_url' => '',
        'issuer' => '',
        'applicant' => '',
        'regulations' => '',
        'status' => 'active',
        'note' => '',
        'created_at' => '',
        'updated_at' => '',
    ];

    public $rules = [
        'required' => [['document_type'], ['number'], ['registry_url'], ['status']],
    ];

    public static function syncAssignments(int $certificateId, array $data): void
    {
        \R::exec('DELETE FROM certificate_assignments WHERE certificate_id = ?', [$certificateId]);
        $targets = $data['assignments'] ?? [];
        foreach ($targets as $target) {
            $type = (string)($target['target_type'] ?? '');
            if (!in_array($type, ['product', 'category', 'brand', 'category_brand'], true)) {
                continue;
            }
            $productId = (int)($target['product_id'] ?? 0);
            $categoryId = (int)($target['category_id'] ?? 0);
            $brandId = (int)($target['brand_id'] ?? 0);
            $valid = ($type === 'product' && $productId)
                || ($type === 'category' && $categoryId)
                || ($type === 'brand' && $brandId)
                || ($type === 'category_brand' && $categoryId && $brandId);
            if (!$valid) {
                continue;
            }
            \R::exec(
                'INSERT INTO certificate_assignments (certificate_id, target_type, product_id, category_id, brand_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
                [$certificateId, $type, $productId ?: null, $categoryId ?: null, $brandId ?: null]
            );
        }
    }

    public static function syncProductAssignments(int $productId, array $certificateIds): void
    {
        \R::exec("DELETE FROM certificate_assignments WHERE target_type = 'product' AND product_id = ?", [$productId]);
        foreach (array_unique(array_map('intval', $certificateIds)) as $certificateId) {
            if ($certificateId > 0) {
                \R::exec(
                    "INSERT INTO certificate_assignments (certificate_id, target_type, product_id, created_at) VALUES (?, 'product', ?, NOW())",
                    [$certificateId, $productId]
                );
            }
        }
    }
}
