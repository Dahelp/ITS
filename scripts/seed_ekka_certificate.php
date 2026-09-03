<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/scripts/install_certificates.php';

$registryUrl = 'https://tech.eaeunion.org/tech/registers/35-1/ru/registryList/conformityDocs/view/67e2e14330dcf8300e67d1e4';
$odataUrl = 'https://tech.eaeunion.org/odata/conformityDocDetailsType?$filter=id%20eq%20%2767e2e14330dcf8300e67d1e4%27&$top=1';
$json = @file_get_contents($odataUrl);
if ($json === false) {
    throw new RuntimeException('Не удалось получить карточку документа из реестра ЕАЭС.');
}
$payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
$record = $payload['value'][0] ?? null;
if (!$record || ($record['Id'] ?? '') !== '67e2e14330dcf8300e67d1e4') {
    throw new RuntimeException('Официальный реестр вернул неожиданную запись.');
}

$articleCodes = [];
foreach (($record['technicalRegulationObjectDetails']['productDetails'] ?? []) as $details) {
    $text = (string)($details['productName'] ?? '');
    preg_match_all('/EK-?\d+(?:AB|A|B)?/iu', $text, $matches);
    $tnVed = (string)($details['commodityCode'][0] ?? '');
    foreach ($matches[0] as $article) {
        $articleCodes[normaliseArticle($article)] = $tnVed;
    }
}
if (!$articleCodes) {
    throw new RuntimeException('В записи реестра не найден перечень артикулов EKKA.');
}

$pdo->beginTransaction();
try {
    $upsert = $pdo->prepare(
        "INSERT INTO certificates
            (document_type, number, name, date_start, date_end, registry_url, file_url, issuer, applicant, regulations, status, note, created_at, updated_at)
         VALUES
            ('certificate', :number, :name, :date_start, :date_end, :registry_url, '', :issuer, :applicant, :regulations, 'active', :note, NOW(), NOW())
         ON DUPLICATE KEY UPDATE
            name = VALUES(name), date_start = VALUES(date_start), date_end = VALUES(date_end),
            registry_url = VALUES(registry_url), issuer = VALUES(issuer), applicant = VALUES(applicant),
            regulations = VALUES(regulations), status = 'active', note = VALUES(note), updated_at = NOW()"
    );
    $upsert->execute([
        ':number' => (string)$record['docId'],
        ':name' => 'Фильтры EKKA: воздушные, топливные и масляные',
        ':date_start' => substr((string)$record['docStartDate'], 0, 10),
        ':date_end' => substr((string)$record['docValidityDate'], 0, 10),
        ':registry_url' => $registryUrl,
        ':issuer' => html_entity_decode((string)($record['conformityAuthorityV2Details']['businessEntityName'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        ':applicant' => (string)($record['applicantDetails']['businessEntityName'] ?? ''),
        ':regulations' => implode(', ', $record['technicalRegulationId'] ?? []),
        ':note' => 'Изготовитель: ' . (string)($record['technicalRegulationObjectDetails']['manufacturerDetails'][0]['businessEntityName'] ?? '')
            . '. Назначение выполнено строго по артикулам из записи реестра.',
    ]);
    $certificateId = (int)$pdo->query("SELECT id FROM certificates WHERE number = " . $pdo->quote((string)$record['docId']))->fetchColumn();

    $categories = $pdo->query('SELECT id, parent_id, name FROM category')->fetchAll(PDO::FETCH_ASSOC);
    $truckCategoryIds = truckTyreCategoryIds($categories);

    // Initial inventory policy confirmed by the owner: everything is not required,
    // except truck tyres and exact EKKA filter articles from this certificate.
    // Preserve statuses adjusted later by an administrator. Only classify products
    // that have not received a certification decision yet.
    $pdo->exec('UPDATE product SET certification_required = 0 WHERE certification_required IS NULL');
    if ($truckCategoryIds) {
        $slots = implode(',', array_fill(0, count($truckCategoryIds), '?'));
        $stmt = $pdo->prepare("UPDATE product SET certification_required = 1 WHERE category_id IN ({$slots})");
        $stmt->execute($truckCategoryIds);
    }

    $products = $pdo->query(
        "SELECT p.id, p.article, p.name, p.brand_id, b.name AS brand_name, c.name AS category_name
         FROM product p
         LEFT JOIN brand b ON b.id = p.brand_id
         LEFT JOIN category c ON c.id = p.category_id"
    )->fetchAll(PDO::FETCH_ASSOC);
    $matched = [];
    foreach ($products as $product) {
        $scopeText = mb_strtolower((string)$product['category_name'] . ' ' . (string)$product['name']);
        $isAllowedFilter = mb_strpos($scopeText, 'маслян') !== false
            || mb_strpos($scopeText, 'масля') !== false
            || mb_strpos($scopeText, 'топлив') !== false
            || mb_strpos($scopeText, 'воздуш') !== false;
        if (!$isAllowedFilter) {
            continue;
        }
        if (stripos((string)$product['brand_name'], 'EKKA') === false
            && stripos((string)$product['name'], 'EKKA') === false
            && stripos((string)$product['article'], 'EK') === false) {
            continue;
        }
        preg_match('/EK-?\d+(?:AB|A|B)?/iu', (string)$product['article'], $match);
        if (!$match) {
            preg_match('/EK-?\d+(?:AB|A|B)?/iu', (string)$product['name'], $match);
        }
        $key = isset($match[0]) ? normaliseArticle($match[0]) : '';
        if ($key !== '' && isset($articleCodes[$key])) {
            $matched[(int)$product['id']] = $articleCodes[$key];
        }
    }

    $pdo->prepare("DELETE FROM certificate_assignments WHERE certificate_id = ? AND target_type = 'product'")->execute([$certificateId]);
    $assign = $pdo->prepare(
        "INSERT INTO certificate_assignments (certificate_id, target_type, product_id, created_at)
         VALUES (?, 'product', ?, NOW())"
    );
    $mark = $pdo->prepare('UPDATE product SET certification_required = 1, tn_ved_code = ? WHERE id = ?');
    foreach ($matched as $productId => $tnVed) {
        $assign->execute([$certificateId, $productId]);
        $mark->execute([$tnVed, $productId]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

echo 'Документ: ' . $record['docId'] . PHP_EOL;
echo 'Артикулов в реестре: ' . count($articleCodes) . PHP_EOL;
echo 'Товаров EKKA сопоставлено: ' . count($matched) . PHP_EOL;
echo 'Категорий грузовых шин (включая дочерние): ' . count($truckCategoryIds) . PHP_EOL;

function normaliseArticle(string $value): string
{
    return strtoupper((string)preg_replace('/[^A-Z0-9]/i', '', $value));
}

function truckTyreCategoryIds(array $categories): array
{
    $ids = [];
    foreach ($categories as $category) {
        $name = mb_strtolower((string)$category['name']);
        if (mb_strpos($name, 'шин') !== false && (mb_strpos($name, 'груз') !== false || mb_strpos($name, 'спецтех') !== false)) {
            $ids[] = (int)$category['id'];
        }
    }
    do {
        $changed = false;
        foreach ($categories as $category) {
            $id = (int)$category['id'];
            if (!in_array($id, $ids, true) && in_array((int)$category['parent_id'], $ids, true)) {
                $ids[] = $id;
                $changed = true;
            }
        }
    } while ($changed);
    return array_values(array_unique($ids));
}
