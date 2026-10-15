<?php

namespace app\models\admin;

use app\models\AppModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class Avito extends AppModel
{
    private const MANAGER_NAME = 'ИТС-Центр';
    private const CONTACT_PHONE = '8 495 424-98-90';

    public $attributes = [

        'avito_id'       => '',
        'avito_url'      => '',
        'status'         => 'draft',
        'ad_external_id' => '',
        'article'        => '',

        'date_begin'     => '',
        'date_end'       => '',
        'listing_fee'    => 'Package',
        'ad_status'      => 'Free',

        'manager_name'   => 'ИТС-Центр',
        'contact_phone'  => '8 495 424-98-90',
        'contact_method' => 'По телефону и в сообщениях',
        'address'        => '',
        'latitude'       => '',
        'longitude'      => '',
        'seller_address_id' => '',

        'category'       => 'Запчасти и аксессуары',
        'title'          => '',
        'description'    => '',
        'price_rub'      => '',
        'video_url'      => '',
        'video_file_url' => '',
        'images_json'    => '',
        'params_json'    => '',

        'promo'             => '',
        'promo_auto_json'   => '',
        'promo_manual_json' => '',

        'internet_calls'    => 'Нет',
        'calls_devices_json'=> '',
        'delivery_json'     => '',

        'weight_kg'     => '',
        'length_cm'     => '',
        'height_cm'     => '',
        'width_cm'      => '',
        'return_policy' => '',
        'delivery_subsidy' => '',

        'goods_type'    => 'Шины, диски и колёса',
        'ad_type'       => 'Товар приобретен на продажу',
        'product_type'  => 'Шины для грузовиков и спецтехники',
        'brand'         => '',
        'model'         => '',
        'tire_section_width' => '',
        'rim_diameter'       => '',
        'tire_aspect_ratio'  => '',
        'tire_type'          => '',
        'quantity'           => '',
        'speed_index'        => '',
        'ply_rating'         => '',
        'construction'       => '',
        'tube_type'          => '',
        'wheel_axle'         => '',
        'load_index'         => '',
        'residual_tread_sv'  => '',
        'design'             => '',
        'vehicle_type'       => '',
        'item_condition'     => 'Новое',
        'target_audience'    => '',
        'review_status'      => 'confirmed',
        'rim_bolts'          => '',
        'rim_bolts_diameter' => '',
        'rim_type'           => '',
        'rim_dia'            => '',
        'rim_offset'         => '',
        'rim_width'          => '',
    ];

    /**
     * Правила валидации — можно расширить
     */
    public $rules = [
        'required' => [
            ['ad_external_id'],
            ['title'],
            ['price_rub'],
            ['category'],
        ]
    ];
    
    /** Максимум строк, которые читаем с листа (начиная с 5-й) */
    const MAX_ROWS = 2000;

    /** Максимум колонок, которые читаем (A..BH = 60 колонок) */
    const MAX_COLS = 60;

    /**
     * Сохранить загруженный файл и вернуть путь.
     */
    public static function handleUpload(array $file): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Ошибка загрузки файла.');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'], true)) {
            throw new \RuntimeException('Неверный формат файла. Разрешены только XLSX/XLS.');
        }

        $uploadDir = ROOT . '/public/uploads/avito_import';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $newName  = 'avito_import_' . date('Ymd_His') . '.' . $ext;
        $fullPath = $uploadDir . '/' . $newName;

        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw new \RuntimeException('Не удалось сохранить загруженный файл.');
        }

        return $fullPath;
    }

    /**
     * Ограниченная загрузка книги: только нужный диапазон (строки/колонки).
     */
    protected static function loadSpreadsheetLimited(string $path)
    {
        @ini_set('memory_limit', '512M');

        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new \RuntimeException('Библиотека PhpSpreadsheet не найдена.');
        }

        $reader = IOFactory::createReaderForFile($path);

        // читаем только данные, без стилей и формул
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }

        $maxRows = self::MAX_ROWS;
        $maxCols = self::MAX_COLS;

        // Фильтр: только 2-я строка (заголовки) и строки 5..MAX_ROWS, колонки до MAX_COLS
        $filter = new class($maxRows, $maxCols) implements IReadFilter {
            /** @var int */
            private $maxRows;
            /** @var int */
            private $maxCols;

            public function __construct(int $maxRows, int $maxCols)
            {
                $this->maxRows = $maxRows;
                $this->maxCols = $maxCols;
            }

            public function readCell($column, $row, $sheetName = ''): bool
            {
                $colIndex = Coordinate::columnIndexFromString($column);

                // Заголовки (строка 2) — читаем первые maxCols колонок
                if ($row === 2) {
                    return $colIndex <= $this->maxCols;
                }

                // Контент начинается с 5-й строки
                if ($row < 5) {
                    return false;
                }

                // Ограничение по строкам и колонкам
                if ($row > $this->maxRows) {
                    return false;
                }
                if ($colIndex > $this->maxCols) {
                    return false;
                }

                return true;
            }
        };

        if (method_exists($reader, 'setReadFilter')) {
            $reader->setReadFilter($filter);
        }

        return $reader->load($path);
    }

    /**
     * Предпросмотр: считаем строки, собираем примеры.
     * Здесь НИЧЕГО не пишем в БД.
     */
    public static function buildPreview(string $path, int $limitPerSheet = 20): array
    {
        $spreadsheet = self::loadSpreadsheetLimited($path);

        $totalValid   = 0;
        $totalInvalid = 0;
        $sheetsPreview = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            /** @var Worksheet $sheet */
            $sheetTitle = (string)$sheet->getTitle();

            // пропускаем "Инструкция" и все "Спр-*"
            if (mb_strtolower($sheetTitle, 'UTF-8') === 'инструкция') {
                continue;
            }
            if (mb_strpos($sheetTitle, 'Спр-') === 0) {
                continue;
            }

            $headerRow = 2;
            $colMap    = self::buildColumnMap($sheet, $headerRow);

            if (!$colMap) {
                // если не смогли прочитать заголовки — пропускаем лист
                continue;
            }

            $startRow = 5;
            $maxRow   = self::MAX_ROWS;

            $rowsValid   = 0;
            $rowsInvalid = 0;
            $sample      = [];
            $emptyInRow  = 0;

            for ($row = $startRow; $row <= $maxRow; $row++) {
                $externalId  = self::getCellByHeader($sheet, $colMap, 'Уникальный идентификатор объявления', $row);
                $titleAd     = self::getCellByHeader($sheet, $colMap, 'Название объявления', $row);
                $category    = self::getCellByHeader($sheet, $colMap, 'Категория', $row);
                $price       = self::getCellByHeader($sheet, $colMap, 'Цена', $row);
                $productType = self::getCellByHeader($sheet, $colMap, 'Тип товара', $row);

                // полностью пустая строка?
                if ($externalId === '' && $titleAd === '' && $category === '' && $price === '') {
                    $emptyInRow++;
                    // если подряд много пустых строк — считаем, что данных дальше нет
                    if ($emptyInRow >= 50) {
                        break;
                    }
                    continue;
                } else {
                    $emptyInRow = 0;
                }

                $rowErrors = [];
                if ($externalId === '') $rowErrors[] = 'Пустой "Уникальный идентификатор объявления".';
                if ($titleAd === '')    $rowErrors[] = 'Пустое "Название объявления".';
                if ($category === '')   $rowErrors[] = 'Пустая "Категория".';
                if ($price === '')      $rowErrors[] = 'Пустая "Цена".';

                if ($rowErrors) {
                    $rowsInvalid++;
                } else {
                    $rowsValid++;
                }

                if (count($sample) < $limitPerSheet) {
                    $sample[] = [
                        'row'          => $row,
                        'id'           => $externalId,
                        'title'        => $titleAd,
                        'category'     => $category,
                        'product_type' => $productType,
                        'price'        => $price,
                        'errors'       => $rowErrors,
                    ];
                }
            }

            if ($rowsValid === 0 && $rowsInvalid === 0) {
                continue;
            }

            $totalValid   += $rowsValid;
            $totalInvalid += $rowsInvalid;

            $sheetsPreview[] = [
                'title'        => $sheetTitle,
                'rows_valid'   => $rowsValid,
                'rows_invalid' => $rowsInvalid,
                'sample'       => $sample,
            ];
        }

        return [
            'total_valid'   => $totalValid,
            'total_invalid' => $totalInvalid,
            'sheets'        => $sheetsPreview,
        ];
    }

    /**
     * Импорт из XLSX.     
    **/

    public static function importFromXlsx(string $path): array
{
    $spreadsheet = self::loadSpreadsheetLimited($path);

    $inserted = 0;   // условно "импортированные" / обновлённые строки
    $errors   = [];  // ошибки по строкам

    foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
        /** @var Worksheet $sheet */
        $sheetTitle = (string)$sheet->getTitle();

        // пропускаем "Инструкция" и "Спр-*"
        if (mb_strtolower($sheetTitle, 'UTF-8') === 'инструкция') {
            continue;
        }
        if (mb_strpos($sheetTitle, 'Спр-') === 0) {
            continue;
        }

        $headerRow = 2;
        $colMap    = self::buildColumnMap($sheet, $headerRow);
        if (!$colMap) {
            continue;
        }

        $startRow   = 5;
        $maxRow     = self::MAX_ROWS;
        $emptyInRow = 0;

        for ($row = $startRow; $row <= $maxRow; $row++) {

            // --- ЧТЕНИЕ ВСЕХ НУЖНЫХ ПОЛЕЙ ОДИН РАЗ ---

            $externalId = self::getCellByHeader($sheet, $colMap, 'Уникальный идентификатор объявления', $row);
            $titleAd    = self::getCellByHeader($sheet, $colMap, 'Название объявления', $row);
            $category   = self::getCellByHeader($sheet, $colMap, 'Категория', $row);
            $priceRaw   = self::getCellByHeader($sheet, $colMap, 'Цена', $row);

            // тип товара
            $productType = self::getCellByHeader($sheet, $colMap, 'Тип товара', $row);

            // бренд/модель
            $brandFile = self::getCellByHeader($sheet, $colMap, 'Производитель', $row);
            $modelFile = self::getCellByHeader($sheet, $colMap, 'Модель', $row);

            // описание
            $descr = self::getCellByHeader($sheet, $colMap, 'Описание объявления', $row);

            // размеры
            $width   = self::getCellByHeader($sheet, $colMap, 'Ширина профиля', $row);
            $height  = self::getCellByHeader($sheet, $colMap, 'Высота профиля', $row);
            $diametr = self::getCellByHeader($sheet, $colMap, 'Диаметр', $row);

            // контакты
            $managerName = self::getCellByHeader($sheet, $colMap, 'Контактное лицо', $row);
            $phoneFile   = self::getCellByHeader($sheet, $colMap, 'Номер телефона', $row);
            $addrFile    = self::getCellByHeader($sheet, $colMap, 'Адрес', $row);

            // AvitoId (если в шаблоне он есть и заполнен)
            $avitoIdFile = self::getCellByHeader($sheet, $colMap, 'Номер объявления на Авито', $row);

            // ссылки на фото
            $imagesRaw = self::getCellByHeader($sheet, $colMap, 'Ссылки на фото', $row);

            // состояние / индексы / слойность
            $condRaw    = self::getCellByHeader($sheet, $colMap, 'Состояние', $row);
            $loadIndex  = self::getCellByHeader($sheet, $colMap, 'Индекс нагрузки', $row);
            $speedIndex = self::getCellByHeader($sheet, $colMap, 'Индекс скорости', $row);
            $plyRating  = self::getCellByHeader($sheet, $colMap, 'Слойность', $row);

            // количество
            $qty = self::getCellByHeader($sheet, $colMap, 'Количество', $row);

            // статус (из файла, на русском)
            $statusFile = self::getCellByHeader($sheet, $colMap, 'AvitoStatus', $row);

            // полностью пустая строка?
            if ($externalId === '' && $titleAd === '' && $category === '' && $priceRaw === '') {
                $emptyInRow++;
                if ($emptyInRow >= 50) {
                    break;
                }
                continue;
            } else {
                $emptyInRow = 0;
            }

            // --- ВАЛИДАЦИЯ ОБЯЗАТЕЛЬНЫХ ---

            $rowErrors = [];
            if ($externalId === '') $rowErrors[] = 'Пустой "Уникальный идентификатор объявления".';
            if ($titleAd === '')    $rowErrors[] = 'Пустое "Название объявления".';
            if ($category === '')   $rowErrors[] = 'Пустая "Категория".';
            if ($priceRaw === '')   $rowErrors[] = 'Пустая "Цена".';

            if ($rowErrors) {
                $errors[] = [
                    'sheet'  => $sheetTitle,
                    'row'    => $row,
                    'id'     => $externalId,
                    'title'  => $titleAd,
                    'errors' => $rowErrors,
                ];
                continue;
            }

            // --- НОРМАЛИЗАЦИЯ ЦЕНЫ ---

            $priceClean = str_replace([' ', "\u{00A0}"], '', $priceRaw);
            $priceClean = str_replace(',', '.', $priceClean);
            $price      = (int)round((float)$priceClean);

            // --- ПРИВЕДЕНИЕ СТАТУСА ---

            $statusEnum = null;
            if ($statusFile !== '') {
                $statusFile = trim($statusFile);
                $mapStatus  = [
                    'Активно'   => 'active',
                    'Черновик'  => 'draft',
                    'Архив'     => 'archived',
                ];
                if (isset($mapStatus[$statusFile])) {
                    $statusEnum = $mapStatus[$statusFile];
                }
            }

            // --- СОСТОЯНИЕ (Новое / Б/у) ---

            $itemCondition = 'Новое';
            if ($condRaw === 'Новое' || $condRaw === 'Б/у') {
                $itemCondition = $condRaw;
            }

            // --- Количество (ENUM 'за X шт.') ---

            $quantityEnum = null;
            if ($qty !== '') {
                $qtyInt = (int)$qty;
                if ($qtyInt >= 1 && $qtyInt <= 10) {
                    $quantityEnum = 'за ' . $qtyInt . ' шт.';
                }
            }

            // --- ЗАПИСЬ В БД ---

            try {
                // ищем по ad_external_id — либо обновляем, либо создаём
                $ad = \R::findOne('avito_ad', 'ad_external_id = ?', [$externalId]);
                if (!$ad) {
                    $ad = \R::dispense('avito_ad');
                    $ad->uuid           = self::uuid4();
                    $ad->ad_external_id = $externalId;
                }

                // статус — только если есть в файле и смогли замапить
                if ($statusEnum !== null) {
                    $ad->status = $statusEnum;
                }

                // AvitoId из файла
                if ($avitoIdFile !== '') {
                    $ad->avito_id = (int)$avitoIdFile;
                }

                // Обязательные / основные
                $ad->title       = $titleAd;
                $ad->category    = $category ?: 'Запчасти и аксессуары';
                $ad->price_rub   = $price;
                $ad->description = ($descr !== '' ? $descr : ($ad->description ?? ''));

                // Типы
                $ad->goods_type = 'Шины, диски и колёса';
                if ($productType !== '') {
                    $ad->product_type = $productType;
                } elseif (empty($ad->product_type)) {
                    $ad->product_type = 'Шины для грузовиков и спецтехники';
                }

                // Бренд / модель
                if ($brandFile !== '') {
                    $ad->brand = $brandFile;
                }
                if ($modelFile !== '') {
                    $ad->model = $modelFile;
                }

                // Размеры — если в файле пусто, не трогаем существующие
                if ($width   !== '') $ad->tire_section_width = $width;
                if ($height  !== '') $ad->tire_aspect_ratio  = $height;
                if ($diametr !== '') $ad->rim_diameter       = $diametr;

                // Состояние
                $ad->item_condition = $itemCondition;

                // Индексы / слойность
                if ($loadIndex  !== '') $ad->load_index  = $loadIndex;
                if ($speedIndex !== '') $ad->speed_index = $speedIndex;
                if ($plyRating  !== '') $ad->ply_rating  = $plyRating;

                // Количество
                if ($quantityEnum !== null) {
                    $ad->quantity = $quantityEnum;
                }

                // Контакты
                if ($managerName !== '') {
                    $ad->manager_name = $managerName;
                }
                if ($phoneFile !== '') {
                    $ad->contact_phone = $phoneFile;
                }
                if ($addrFile !== '') {
                    $ad->address = $addrFile;
                }

                // Фото: "url | url | url" → JSON-массив
                if ($imagesRaw !== '') {
                    $parts = preg_split('~\s*\|\s*~', $imagesRaw);
                    $urls  = [];
                    foreach ($parts as $u) {
                        $u = trim($u);
                        if ($u !== '') {
                            $urls[] = $u;
                        }
                    }
                    if ($urls) {
                        $ad->images_json = json_encode($urls, JSON_UNESCAPED_UNICODE);
                    }
                }

                \R::store($ad);
                $inserted++;

            } catch (\Throwable $e) {
                $errors[] = [
                    'sheet'  => $sheetTitle,
                    'row'    => $row,
                    'id'     => $externalId,
                    'title'  => $titleAd,
                    'errors' => ['Ошибка БД: ' . $e->getMessage()],
                ];
            }
        }
    }

    return [
        'inserted' => $inserted,
        'errors'   => $errors,
    ];
}


    // ===== helpers =====

    /**
     * Построить карту "заголовок колонки" -> индекс колонки, по строке $headerRow.
     */
    protected static function buildColumnMap(Worksheet $sheet, int $headerRow): array
    {
        $map = [];

        // Берём максимум до MAX_COLS колонок
        $maxColIndex = self::MAX_COLS;

        for ($colIndex = 1; $colIndex <= $maxColIndex; $colIndex++) {
            $value = $sheet->getCellByColumnAndRow($colIndex, $headerRow)->getValue();
            if (!is_null($value)) {
                $header = trim((string)$value);
                if ($header !== '') {
                    $map[$header] = $colIndex;
                }
            }
        }

        return $map;
    }

    /**
     * Получить значение ячейки по названию колонки (из заголовка).
     */
    protected static function getCellByHeader(Worksheet $sheet, array $colMap, string $header, int $row): string
    {
        if (!isset($colMap[$header])) {
            return '';
        }
        $colIndex = $colMap[$header];
        $value    = $sheet->getCellByColumnAndRow($colIndex, $row)->getValue();

        if (is_null($value)) {
            return '';
        }

        return trim((string)$value);
    }

    protected static function toDateTime($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $ts = ExcelDate::excelToTimestamp($value);
            return date('Y-m-d H:i:s', $ts);
        }

        $ts = strtotime((string)$value);
        if ($ts === false) {
            return null;
        }
        return date('Y-m-d H:i:s', $ts);
    }

    protected static function uuid4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    public static function ensureSchema(): void
    {
        $hasAvitoUrl = \R::getCell("SHOW COLUMNS FROM avito_ad LIKE 'avito_url'");
        if (!$hasAvitoUrl) {
            \R::exec("ALTER TABLE avito_ad ADD avito_url VARCHAR(512) NULL DEFAULT NULL AFTER avito_id");
        }

        $columns = [
            'rim_bolts' => 'VARCHAR(16)',
            'rim_bolts_diameter' => 'VARCHAR(32)',
            'rim_type' => 'VARCHAR(64)',
            'rim_dia' => 'VARCHAR(32)',
            'rim_offset' => 'VARCHAR(32)',
            'rim_width' => 'VARCHAR(32)',
            'review_status' => 'VARCHAR(32)',
        ];
        foreach ($columns as $name => $definition) {
            if (!\R::getCell("SHOW COLUMNS FROM avito_ad LIKE ?", [$name])) {
                \R::exec("ALTER TABLE avito_ad ADD `{$name}` {$definition} NULL DEFAULT NULL");
            }
        }

        $wheelAxleColumn = \R::getRow("SHOW COLUMNS FROM avito_ad LIKE 'wheel_axle'");
        $wheelAxleType = (string)($wheelAxleColumn['Type'] ?? '');
        if ($wheelAxleColumn
            && stripos($wheelAxleType, 'enum(') === 0
            && stripos($wheelAxleType, 'Любая') === false) {
            \R::exec("ALTER TABLE avito_ad MODIFY wheel_axle VARCHAR(64) NULL DEFAULT NULL");
        }

        // Контакты едины для всех объявлений, а черновики должны быть в архиве.
        \R::exec(
            "UPDATE avito_ad SET manager_name = ?, contact_phone = ?, "
            . "status = CASE WHEN status = 'draft' THEN 'archived' ELSE status END "
            . 'WHERE manager_name IS NULL OR manager_name != ? '
            . "OR contact_phone IS NULL OR contact_phone != ? OR status = 'draft'",
            [self::MANAGER_NAME, self::CONTACT_PHONE, self::MANAGER_NAME, self::CONTACT_PHONE]
        );
    }
    public static function syncLinkedAdsFromProducts(): int
    {
        self::ensureSchema();

        \R::exec("\n            UPDATE avito_ad a\n            LEFT JOIN product p ON p.article = a.article\n            SET a.status = CASE\n                WHEN COALESCE(p.rest, p.quantity, 0) <= 0 THEN 'archived'\n                WHEN a.review_status = 'needs_review' THEN 'archived'\n                ELSE 'active'\n            END\n        ");

        $rows = \R::getAll("\n            SELECT\n                a.id AS ad_id,\n                a.article AS ad_article,\n                p.id AS product_id,\n                p.article, p.name, p.description, p.content, p.price, p.quantity, p.rest, p.hide,\n                p.img, p.unload_img, p.alias, p.model, p.weight,\n                (SELECT GROUP_CONCAT(DISTINCT CONCAT(ag.title, ': ', av.value) SEPARATOR ' ')\n                   FROM attribute_product ap\n                   INNER JOIN attribute_value av ON av.id = ap.attr_id\n                   INNER JOIN attribute_group ag ON ag.id = av.attr_group_id\n                  WHERE ap.product_id = p.id) AS attribute_text,\n                b.name AS brand_name\n            FROM avito_ad a\n            INNER JOIN product p ON p.article = a.article\n            LEFT JOIN brand b ON b.id = p.brand_id\n            WHERE a.article IS NOT NULL AND a.article != ''\n        ");

        $updated = 0;
        foreach ($rows as $row) {
            $ad = \R::load('avito_ad', (int)$row['ad_id']);
            if (!$ad || !$ad->id) {
                continue;
            }

            $quantity = max(0, (int)($row['rest'] ?? $row['quantity'] ?? 0));
            $price = (int)round((float)($row['price'] ?? 0));

            if (empty($ad->uuid)) {
                $ad->uuid = self::uuidV4();
            }
            if (empty($ad->ad_external_id)) {
                $ad->ad_external_id = self::externalIdFromArticle((string)$row['article']);
            }

            $ad->price_rub = $price > 0 ? $price : null;
            // Quantity в XML Avito — это цена «за N шт.», а не складской остаток.
            $ad->quantity = 'за 1 шт.';
            $ad->status = $quantity > 0 && (string)$ad->review_status !== 'needs_review'
                ? 'active'
                : 'archived';

            if (empty($ad->title)) {
                $ad->title = self::limitText((string)$row['name'], 50);
            }
            if (empty($ad->description)) {
                $ad->description = self::buildDescription($row);
            }
            self::normalizeCatalogFields($ad, $row);
            if (empty($ad->weight_kg) && !empty($row['weight'])) {
                $ad->weight_kg = (string)$row['weight'];
            }

            self::fillDefaults($ad);

            $images = self::productImageUrls($row);
            if ($images) {
                $ad->images_json = json_encode($images, JSON_UNESCAPED_UNICODE);
            }

            \R::store($ad);
            $updated++;
        }

        return $updated;
    }

    public static function getFeedRows(int $id = 0, bool $onlyActive = false): array
    {
        self::syncLinkedAdsFromProducts();
        if ($id > 0) {
            return \R::getAll("SELECT * FROM avito_ad WHERE id = ? LIMIT 1", [$id]);
        }
        if ($onlyActive) {
            return \R::getAll("SELECT * FROM avito_ad WHERE status = 'active' ORDER BY id DESC");
        }
        return \R::getAll("SELECT * FROM avito_ad ORDER BY id DESC");
    }

    public static function buildFeedXml(int $id = 0, bool $onlyActive = true): string
    {
        return self::buildXml(self::getFeedRows($id, $onlyActive));
    }
    public static function buildXml(array $rows): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Ads/>');
        $xml->addAttribute('formatVersion', '3');
        $xml->addAttribute('target', 'Avito.ru');

        foreach ($rows as $r) {
            $ad = $xml->addChild('Ad');
            self::xmlAdd($ad, 'Id', !empty($r['ad_external_id']) ? (string)$r['ad_external_id'] : (string)$r['id']);
            self::xmlAdd($ad, 'AvitoId', (string)($r['avito_id'] ?? ''));
            self::xmlAdd($ad, 'Status', (string)($r['status'] ?? 'active'));
            self::xmlAdd($ad, 'ListingFee', (string)($r['listing_fee'] ?? 'Package'));
            self::xmlAdd($ad, 'AdStatus', (string)($r['ad_status'] ?? 'Free'));
            self::xmlAdd($ad, 'DateBegin', (string)($r['date_begin'] ?? ''));
            self::xmlAdd($ad, 'DateEnd', (string)($r['date_end'] ?? ''));
            self::xmlAdd($ad, 'ManagerName', (string)($r['manager_name'] ?? ''));
            self::xmlAdd($ad, 'ContactPhone', (string)($r['contact_phone'] ?? ''));
            self::xmlAdd($ad, 'ContactMethod', (string)($r['contact_method'] ?? ''));
            self::xmlAdd($ad, 'Address', (string)($r['address'] ?? ''));
            self::xmlAdd($ad, 'Latitude', (string)($r['latitude'] ?? ''));
            self::xmlAdd($ad, 'Longitude', (string)($r['longitude'] ?? ''));
            self::xmlAdd($ad, 'SellerAddressId', (string)($r['seller_address_id'] ?? ''));
            self::xmlAdd($ad, 'Category', (string)($r['category'] ?? ''));
            self::xmlAdd($ad, 'GoodsType', (string)($r['goods_type'] ?? ''));
            self::xmlAdd($ad, 'AdType', (string)($r['ad_type'] ?? ''));
            self::xmlAdd($ad, 'ProductType', (string)($r['product_type'] ?? ''));
            self::xmlAdd($ad, 'Title', (string)($r['title'] ?? ''));
            self::xmlCdata($ad, 'Description', self::descriptionValue($r));
            if (!empty($r['price_rub'])) {
                self::xmlAdd($ad, 'Price', (string)((int)$r['price_rub']));
            }
            self::xmlAdd($ad, 'Condition', (string)($r['item_condition'] ?? 'Новое'));
            self::xmlAdd($ad, 'TargetAudience', (string)($r['target_audience'] ?? ''));
            self::xmlAdd($ad, 'VideoURL', (string)($r['video_url'] ?? ''));
            self::xmlAdd($ad, 'VideoFileURL', (string)($r['video_file_url'] ?? ''));
            $productType = trim((string)($r['product_type'] ?? ''));
            if ($productType !== 'Мотошины' && $productType !== 'Диски') {
                self::xmlAddIfNotEmpty($ad, 'Brand', (string)($r['brand'] ?? ''));
            }
            $avitoModel = self::avitoModelValue($productType, (string)($r['model'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'Model', $avitoModel);
            self::xmlAdd($ad, 'TireSectionWidth', (string)($r['tire_section_width'] ?? ''));
            self::xmlAdd($ad, 'TireAspectRatio', (string)($r['tire_aspect_ratio'] ?? ''));
            self::xmlAdd($ad, 'RimDiameter', (string)($r['rim_diameter'] ?? ''));
            self::xmlAdd($ad, 'TireType', (string)($r['tire_type'] ?? ''));
            self::xmlAdd($ad, 'Quantity', (string)($r['quantity'] ?? ''));
            self::xmlAdd($ad, 'SpeedIndex', (string)($r['speed_index'] ?? ''));
            self::xmlAdd($ad, 'PlyRating', (string)($r['ply_rating'] ?? ''));
            self::xmlAdd($ad, 'Construction', (string)($r['construction'] ?? ''));
            self::xmlAdd($ad, 'TubeType', (string)($r['tube_type'] ?? ''));
            self::xmlAdd($ad, 'WheelAxle', (string)($r['wheel_axle'] ?? ''));
            self::xmlAdd($ad, 'LoadIndex', (string)($r['load_index'] ?? ''));
            self::xmlAdd($ad, 'ResidualTreadSV', (string)($r['residual_tread_sv'] ?? ''));
            self::xmlAdd($ad, 'Design', (string)($r['design'] ?? ''));
            self::xmlAdd($ad, 'VehicleType', (string)($r['vehicle_type'] ?? ''));
            if ($productType === 'Диски') {
                self::xmlAdd($ad, 'RimBolts', (string)($r['rim_bolts'] ?? ''));
                self::xmlAdd($ad, 'RimBoltsDiameter', (string)($r['rim_bolts_diameter'] ?? ''));
                self::xmlAdd($ad, 'RimType', (string)($r['rim_type'] ?? ''));
                self::xmlAdd($ad, 'RimDIA', (string)($r['rim_dia'] ?? ''));
                self::xmlAdd($ad, 'RimOffset', (string)($r['rim_offset'] ?? ''));
                self::xmlAdd($ad, 'RimWidth', (string)($r['rim_width'] ?? ''));
            }
            self::xmlAdd($ad, 'DeliverySubsidy', (string)($r['delivery_subsidy'] ?? ''));
            self::xmlAdd($ad, 'ReturnPolicy', (string)($r['return_policy'] ?? ''));
            self::xmlAdd($ad, 'InternetCalls', (string)($r['internet_calls'] ?? ''));
            self::xmlAdd($ad, 'CallsDevices', (string)($r['calls_devices_json'] ?? ''));
            self::xmlAdd($ad, 'DeliveryOptions', (string)($r['delivery_json'] ?? ''));
            self::xmlAdd($ad, 'Weight', (string)($r['weight_kg'] ?? ''));
            self::xmlAdd($ad, 'Length', (string)($r['length_cm'] ?? ''));
            self::xmlAdd($ad, 'Height', (string)($r['height_cm'] ?? ''));
            self::xmlAdd($ad, 'Width', (string)($r['width_cm'] ?? ''));
            self::xmlAdd($ad, 'Promo', (string)($r['promo'] ?? ''));
            self::xmlAdd($ad, 'PromoAutoOptions', (string)($r['promo_auto_json'] ?? ''));
            self::xmlAdd($ad, 'PromoManualOptions', (string)($r['promo_manual_json'] ?? ''));

            $imagesNode = $ad->addChild('Images');
            foreach (self::decodeImages($r['images_json'] ?? '') as $url) {
                $imgNode = $imagesNode->addChild('Image');
                $imgNode->addAttribute('url', $url);
            }
        }

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        return $dom->saveXML();
    }

    private static function fillDefaults($ad): void
    {
        $cfg = (array)(\ishop\App::$app->getProperty('avito') ?? []);
        $defaults = [
            'manager_name' => self::MANAGER_NAME,
            'contact_phone' => self::CONTACT_PHONE,
            'contact_method' => $cfg['default_contact_method'] ?? 'По телефону и в сообщениях',
            'address' => $cfg['default_address'] ?? '',
            'latitude' => $cfg['default_latitude'] ?? '',
            'longitude' => $cfg['default_longitude'] ?? '',
            'category' => 'Запчасти и аксессуары',
            'goods_type' => 'Шины, диски и колёса',
            'ad_type' => 'Товар приобретен на продажу',
            'product_type' => 'Шины для грузовиков и спецтехники',
            'item_condition' => 'Новое',
            'listing_fee' => 'Package',
            'ad_status' => 'Free',
        ];
        foreach ($defaults as $field => $value) {
            if (empty($ad->$field) && $value !== '') {
                $ad->$field = $value;
            }
        }
    }

    private static function normalizeCatalogFields($ad, array $row): void
    {
        $text = implode(' ', array_filter([
            (string)($row['name'] ?? ''),
            (string)($row['model'] ?? ''),
            strip_tags((string)($row['description'] ?? '')),
            strip_tags((string)($row['content'] ?? '')),
            (string)($row['attribute_text'] ?? ''),
        ]));
        $productType = trim((string)($ad->product_type ?? ''));

        $brandPatterns = [
            'Primex' => '~\bprimex\b~iu',
            'SUPERGUIDER' => '~\bsuperguider\b~iu',
            'EKKA' => '~\b(?:ekka|екка)\b~iu',
            'Boto' => '~\bboto\b~iu',
            'Galaxy' => '~\bgalaxy\b~iu',
            'HENGRUIDA' => '~\bhengruida\b~iu',
            'Windforce' => '~\bwindforce\b~iu',
        ];
        foreach ($brandPatterns as $brand => $pattern) {
            if (empty($ad->brand) && preg_match($pattern, $text)) {
                $ad->brand = $brand;
                break;
            }
        }
        if (empty($ad->brand) && !empty($row['brand_name'])) {
            $ad->brand = trim((string)$row['brand_name']);
        }

        $productModel = trim((string)($row['model'] ?? ''));
        if (empty($ad->model) && $productModel !== '' && self::containsToken($text, $productModel)) {
            $ad->model = $productModel;
        }

        if (empty($ad->ply_rating) && preg_match('~\b(\d{1,2})\s*PR\b~iu', $text, $match)) {
            $ad->ply_rating = $match[1];
        }

        // Комбинированная маркировка грузовых/индустриальных шин: 178A8.
        if (preg_match('~\b(\d{2,3})(A[1-8]|B|C|D|E|F|G|J|K|L|M|N|P|Q|R|S|T)\b~iu', $text, $match)) {
            if (empty($ad->load_index)) $ad->load_index = $match[1];
            if (empty($ad->speed_index)) $ad->speed_index = strtoupper($match[2]);
        }

        if ($productType === 'Мотошины') {
            if (empty($ad->wheel_axle)) $ad->wheel_axle = 'Любая';
            if (empty($ad->model)) $ad->model = 'Другая';
        } elseif ($productType === 'Шины для грузовиков и спецтехники') {
            if (empty($ad->wheel_axle)) $ad->wheel_axle = 'Универсальная';
            if (empty($ad->construction)) {
                $ad->construction = preg_match('~\d(?:[.,]\d+)?\s*R\s*\d~iu', $text) ? 'Радиальный' : 'Диагональный';
            }
            if (empty($ad->tube_type) && preg_match('~\b(?:TL|бескамерн)~iu', $text)) {
                $ad->tube_type = 'Бескамерная';
            } elseif (empty($ad->tube_type) && preg_match('~\b(?:TTF?|камерн|шинокомплект)~iu', $text)) {
                $ad->tube_type = 'Камерная';
            }
        } elseif ($productType === 'Диски') {
            self::normalizeRimFields($ad, $text);
        }
    }

    private static function normalizeRimFields($ad, string $text): void
    {
        if (empty($ad->rim_type)) $ad->rim_type = 'Штампованные';
        if (empty($ad->quantity)) $ad->quantity = 'за 1 шт.';
        if (preg_match('~(?:W\s*)?(\d+(?:[.,]\d+)?)\s*[xх×]\s*R?(\d+(?:[.,]\d+)?)~iu', $text, $m)) {
            if (empty($ad->rim_width)) $ad->rim_width = str_replace(',', '.', $m[1]);
            if (empty($ad->rim_diameter)) $ad->rim_diameter = str_replace(',', '.', $m[2]);
        }
        if (empty($ad->rim_offset) && preg_match('~\bET\s*(-?\d+(?:[.,]\d+)?)~iu', $text, $m)) {
            $ad->rim_offset = str_replace(',', '.', $m[1]);
        }
        if ((empty($ad->rim_bolts) || empty($ad->rim_bolts_diameter))
            && preg_match('~\bPCD\s*(\d+)\s*[xх×]\s*(\d+(?:[.,]\d+)?)~iu', $text, $m)) {
            if (empty($ad->rim_bolts)) $ad->rim_bolts = $m[1];
            if (empty($ad->rim_bolts_diameter)) $ad->rim_bolts_diameter = str_replace(',', '.', $m[2]);
        } elseif (empty($ad->rim_bolts) && preg_match('~(?:шпил(?:ек|ьки)|отверсти(?:й|я))\D{0,10}(\d+)~iu', $text, $m)) {
            $ad->rim_bolts = $m[1];
        }
        if (empty($ad->rim_dia) && preg_match('~(?:HUB|DIA|центрального отверстия)\D{0,10}(\d+(?:[.,]\d+)?)~iu', $text, $m)) {
            $ad->rim_dia = str_replace(',', '.', $m[1]);
        }
        if (empty($ad->rim_bolts_diameter)
            && preg_match('~(?:межболтов(?:ой|ого)\s+диаметр)\D{0,10}(\d+(?:[.,]\d+)?)~iu', $text, $m)) {
            $ad->rim_bolts_diameter = str_replace(',', '.', $m[1]);
        }
    }

    public static function avitoModelValue(string $productType, string $model): string
    {
        $model = trim($model);
        if ($productType === 'Мотошины') {
            return 'Другая';
        }
        if ($productType !== 'Шины для грузовиков и спецтехники') {
            return '';
        }
        $allowed = [
            'Atlas', 'C1', 'E3', 'E3/L3', 'G-2/L-2', 'Knight', 'L-2', 'L-5', 'L2',
            'P319', 'P323', 'QH-201', 'QH107', 'QH601', 'QH602', 'QH608', 'R-4',
            'SKS-1', 'Standard', 'Vulcan',
        ];
        foreach ($allowed as $value) {
            if (strcasecmp($model, $value) === 0) {
                return $value;
            }
        }
        return '';
    }

    public static function analyzeRow(array $row): array
    {
        $required = [
            'ad_external_id' => 'Внешний ID', 'title' => 'Название', 'description' => 'Описание',
            'price_rub' => 'Цена', 'category' => 'Категория', 'goods_type' => 'Вид товара',
            'ad_type' => 'Вид объявления', 'product_type' => 'Тип товара',
            'item_condition' => 'Состояние', 'manager_name' => 'Менеджер',
            'contact_phone' => 'Телефон', 'address' => 'Адрес', 'images_json' => 'Фотографии',
        ];
        $optional = [
            'brand' => 'Бренд', 'model' => 'Модель', 'tire_section_width' => 'Ширина профиля',
            'tire_aspect_ratio' => 'Высота профиля', 'rim_diameter' => 'Диаметр',
            'tire_type' => 'Сезонность', 'speed_index' => 'Индекс скорости',
            'ply_rating' => 'Слойность', 'construction' => 'Каркас', 'tube_type' => 'Тип TT/TL',
            'wheel_axle' => 'Ось', 'load_index' => 'Индекс нагрузки',
        ];
        $type = trim((string)($row['product_type'] ?? ''));
        if ($type === 'Шины для грузовиков и спецтехники') {
            $required += [
                'brand' => 'Бренд', 'tire_section_width' => 'Ширина профиля',
                'tire_aspect_ratio' => 'Высота профиля', 'rim_diameter' => 'Диаметр',
            ];
        } elseif ($type === 'Мотошины') {
            $required += [
                'model' => 'Модель Avito', 'tire_section_width' => 'Ширина профиля',
                'rim_diameter' => 'Диаметр', 'wheel_axle' => 'Ось',
            ];
        } elseif ($type === 'Диски') {
            $required += [
                'rim_diameter' => 'Диаметр', 'quantity' => 'Количество', 'rim_bolts' => 'Количество отверстий',
                'rim_bolts_diameter' => 'PCD', 'rim_type' => 'Тип диска', 'rim_dia' => 'DIA',
                'rim_offset' => 'Вылет ET', 'rim_width' => 'Ширина обода',
            ];
        }
        foreach (array_keys($required) as $field) {
            unset($optional[$field]);
        }

        $isEmpty = static function ($value): bool {
            if (is_array($value)) return count($value) === 0;
            return trim((string)$value) === '';
        };
        $missingRequired = [];
        foreach ($required as $field => $label) {
            $value = $row[$field] ?? '';
            if ($field === 'images_json') {
                $value = self::decodeImages($value);
            }
            if ($isEmpty($value) || ($field === 'price_rub' && (float)$value <= 0)) {
                $missingRequired[$field] = $label;
            }
        }
        $missingOptional = [];
        foreach ($optional as $field => $label) {
            if ($isEmpty($row[$field] ?? '')) {
                $missingOptional[$field] = $label;
            }
        }

        $warnings = [];
        $title = trim((string)($row['title'] ?? ''));
        $description = trim(strip_tags((string)($row['description'] ?? '')));
        if ($title !== '' && (mb_strlen($title, 'UTF-8') < 12 || mb_strlen($title, 'UTF-8') > 50)) {
            $warnings[] = 'Название желательно сделать длиной 12–50 символов';
        }
        $descriptionLength = mb_strlen($description, 'UTF-8');
        if ($descriptionLength < 250) {
            $warnings[] = 'Описание слишком короткое — желательно не менее 250 символов';
        }
        if (count(self::decodeImages($row['images_json'] ?? '')) < 2) {
            $warnings[] = 'Желательно добавить минимум 2 фотографии';
        }
        $model = trim((string)($row['model'] ?? ''));
        if ($model !== '' && $type === 'Шины для грузовиков и спецтехники'
            && self::avitoModelValue($type, $model) === '') {
            $warnings[] = 'Модели «' . $model . '» нет в справочнике Avito — она указана только в названии';
        }

        $requiredTotal = count($required);
        $requiredFilled = $requiredTotal - count($missingRequired);
        $optionalTotal = count($optional);
        $optionalFilled = $optionalTotal - count($missingOptional);
        $requiredScore = $requiredTotal ? (int)round($requiredFilled * 100 / $requiredTotal) : 100;
        $optionalScore = $optionalTotal ? (int)round($optionalFilled * 100 / $optionalTotal) : 100;
        $textScore = 0;
        if ($title !== '') $textScore += 25;
        if (mb_strlen($title, 'UTF-8') >= 12 && mb_strlen($title, 'UTF-8') <= 50) $textScore += 15;
        if ($descriptionLength >= 250) $textScore += 25;
        if ($descriptionLength >= 600) $textScore += 15;
        if (preg_match('~\d~u', $title)) $textScore += 10;
        if (count(self::decodeImages($row['images_json'] ?? '')) >= 2) $textScore += 10;

        $reviewStatus = trim((string)($row['review_status'] ?? 'confirmed'));
        if ($missingRequired) {
            $status = 'error';
            $label = 'Нужно исправить';
        } elseif ($reviewStatus === 'needs_review' || $warnings) {
            $status = 'warning';
            $label = 'Требует проверки';
        } else {
            $status = 'ready';
            $label = 'Готово к выгрузке';
        }

        return compact(
            'status', 'label', 'missingRequired', 'missingOptional', 'warnings',
            'requiredScore', 'optionalScore', 'textScore', 'requiredTotal', 'optionalTotal'
        );
    }

    public static function createArchivedForProduct(int $productId): int
    {
        self::ensureSchema();
        $row = \R::getRow("SELECT p.*, b.name AS brand_name,
            (SELECT GROUP_CONCAT(DISTINCT CONCAT(ag.title, ': ', av.value) SEPARATOR ' ')
               FROM attribute_product ap
               INNER JOIN attribute_value av ON av.id = ap.attr_id
               INNER JOIN attribute_group ag ON ag.id = av.attr_group_id
              WHERE ap.product_id = p.id) AS attribute_text
            FROM product p LEFT JOIN brand b ON b.id = p.brand_id WHERE p.id = ? LIMIT 1", [$productId]);
        if (!$row) {
            throw new \RuntimeException('Товар не найден');
        }
        $row['product_id'] = (int)$row['id'];
        $article = trim((string)($row['article'] ?? ''));
        if ($article === '') {
            throw new \RuntimeException('У товара не заполнен артикул');
        }
        $existing = \R::findOne('avito_ad', 'article = ?', [$article]);
        if ($existing) {
            return (int)$existing->id;
        }

        $ad = \R::dispense('avito_ad');
        $ad->uuid = self::uuidV4();
        $ad->ad_external_id = self::externalIdFromArticle($article);
        $ad->article = $article;
        $ad->status = 'archived';
        $ad->review_status = 'needs_review';
        $ad->title = self::limitText((string)($row['name'] ?? ''), 50);
        $ad->description = self::buildDescription($row);
        $ad->price_rub = (int)round((float)($row['price'] ?? 0));
        $ad->quantity = 'за 1 шт.';
        $name = mb_strtolower((string)($row['name'] ?? ''), 'UTF-8');
        $ad->product_type = preg_match('~\bдиск~u', $name) ? 'Диски' : 'Шины для грузовиков и спецтехники';
        self::fillDefaults($ad);
        self::normalizeCatalogFields($ad, $row);
        if (!empty($row['weight'])) $ad->weight_kg = (string)$row['weight'];
        $images = self::productImageUrls($row);
        if ($images) $ad->images_json = json_encode($images, JSON_UNESCAPED_UNICODE);
        return (int)\R::store($ad);
    }

    private static function containsToken(string $text, string $token): bool
    {
        return mb_stripos($text, $token, 0, 'UTF-8') !== false;
    }

    private static function productImageUrls(array $row): array
    {
        $urls = [];
        foreach (['unload_img' => 'unload', 'img' => 'baseimg'] as $field => $dir) {
            $file = trim((string)($row[$field] ?? ''));
            if ($file !== '') {
                $urls[] = rtrim(PATH, '/') . '/images/product/' . $dir . '/' . rawurlencode($file);
            }
        }
        if (!empty($row['product_id'])) {
            $gallery = \R::getCol('SELECT img FROM gallery WHERE product_id = ? ORDER BY id', [(int)$row['product_id']]);
            foreach ($gallery as $file) {
                $file = trim((string)$file);
                if ($file !== '') {
                    $urls[] = rtrim(PATH, '/') . '/images/product/gallery/' . rawurlencode($file);
                }
            }
        }
        return array_values(array_unique($urls));
    }

    private static function buildDescription(array $row): string
    {
        $text = trim(strip_tags((string)($row['content'] ?? '')));
        if ($text === '') {
            $text = trim((string)($row['description'] ?? ''));
        }
        if ($text === '') {
            $text = trim((string)($row['name'] ?? ''));
        }
        $text = preg_replace('~\s+~u', ' ', $text);
        return self::limitText($text, 7500);
    }

    private static function descriptionValue(array $row): string
    {
        $value = trim((string)($row['description'] ?? ''));
        if ($value === '') {
            $value = trim((string)($row['title'] ?? ''));
        }
        return self::limitText(preg_replace('~\r\n?~', "\n", $value), 7500);
    }

    private static function decodeImages($raw): array
    {
        $decoded = json_decode((string)$raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        $urls = [];
        foreach ($decoded as $img) {
            if (is_string($img) && trim($img) !== '') {
                $urls[] = trim($img);
            } elseif (is_array($img) && !empty($img['url'])) {
                $urls[] = trim((string)$img['url']);
            }
        }
        return array_values(array_unique(array_filter($urls)));
    }

    private static function xmlAdd(\SimpleXMLElement $node, string $name, string $value): void
    {
        $node->addChild($name, htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8'));
    }

    private static function xmlAddIfNotEmpty(\SimpleXMLElement $node, string $name, string $value): void
    {
        if (trim($value) !== '') {
            self::xmlAdd($node, $name, $value);
        }
    }

    private static function xmlCdata(\SimpleXMLElement $node, string $name, string $value): void
    {
        if ($value === '') {
            return;
        }
        $child = $node->addChild($name);
        $dom = dom_import_simplexml($child);
        $dom->appendChild($dom->ownerDocument->createCDATASection(str_replace(']]>', ']]]]><![CDATA[>', $value)));
    }

    private static function limitText(string $text, int $limit): string
    {
        if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $limit) {
            return mb_substr($text, 0, $limit, 'UTF-8');
        }
        if (!function_exists('mb_strlen') && strlen($text) > $limit) {
            return substr($text, 0, $limit);
        }
        return $text;
    }

    private static function externalIdFromArticle(string $article): string
    {
        $article = preg_replace('~[^a-zA-Z0-9_-]+~', '-', trim($article));
        return 'its-' . trim($article, '-');
    }

    private static function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    public static function syncUrlsFromApi(): int
    {
        self::ensureSchema();

        try {
            $client = new \app\services\AvitoApiClient();
            $client->getAccessToken();
        } catch (\Throwable $e) {
            return 0;
        }
        $updated = 0;
        $page = 1;
        $perPage = 100;

        do {
            try {
                $response = $client->api('GET', '/core/v1/items?page=' . $page . '&per_page=' . $perPage);
            } catch (\Throwable $e) {
                break;
            }
            $items = $response['resources'] ?? [];
            if (!is_array($items) || !$items) {
                break;
            }

            foreach ($items as $item) {
                $itemId = (string)($item['id'] ?? '');
                $url = trim((string)($item['url'] ?? ''));
                if ($itemId === '' || $url === '') {
                    continue;
                }

                $count = \R::exec(
                    "UPDATE avito_ad SET avito_url = ? WHERE avito_id = ? OR ad_external_id = ?",
                    [$url, $itemId, $itemId]
                );
                $updated += (int)$count;
            }

            $meta = $response['meta'] ?? [];
            $currentPage = (int)($meta['page'] ?? $page);
            $returnedPerPage = (int)($meta['per_page'] ?? $perPage);
            $page++;
        } while (count($items) >= max(1, $returnedPerPage) && $page <= $currentPage + 100);

        return $updated;
    }
    public static function pushLinkedAdsToApi(): array
    {
        self::ensureSchema();

        $stats = [
            'price_success' => 0,
            'price_failed' => 0,
            'stock_success' => 0,
            'stock_failed' => 0,
            'stock_checked' => 0,
            'stock_positive' => 0,
            'stock_mismatch' => 0,
            'stock_sent_total' => 0,
            'stock_sent_positive' => 0,
            'status_active' => 0,
            'status_archived' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $rows = \R::getAll("\n            SELECT\n                a.id AS ad_id,\n                a.avito_id,\n                a.ad_external_id,\n                a.price_rub,\n                a.quantity,\n                p.rest\n            FROM avito_ad a\n            LEFT JOIN product p ON p.article = a.article\n            WHERE a.article IS NOT NULL AND a.article != ''\n        ");

        if (!$rows) {
            return $stats;
        }

        $client = new \app\services\AvitoApiClient();
        $client->getAccessToken();

        $stockBatch = [];
        $expectedStock = [];

        foreach ($rows as $row) {
            $itemId = self::apiItemId($row);
            $price = (int)round((float)($row['price_rub'] ?? 0));
            $quantity = max(0, (int)($row['rest'] ?? $row['quantity'] ?? 0));
            if ($quantity > 0) {
                $stats['status_active']++;
            } else {
                $stats['status_archived']++;
            }

            if ($itemId <= 0) {
                $stats['skipped']++;
                continue;
            }

            if ($price > 0) {
                try {
                    $response = $client->api('POST', '/core/v1/items/' . $itemId . '/update_price', [
                        'json' => ['price' => $price],
                    ]);
                    $success = (bool)($response['result']['success'] ?? true);
                    if ($success) {
                        $stats['price_success']++;
                    } else {
                        $stats['price_failed']++;
                        self::addApiError($stats, $itemId, 'Цена не обновлена: Avito вернул success=false');
                    }
                } catch (\Throwable $e) {
                    $stats['price_failed']++;
                    self::addApiError($stats, $itemId, 'Цена: ' . $e->getMessage());
                }
            } else {
                $stats['skipped']++;
            }

            $stockBatch[] = [
                'item_id' => $itemId,
                'quantity' => $quantity,
            ];
            $expectedStock[$itemId] = $quantity;
            $stats['stock_sent_total'] += $quantity;
            if ($quantity > 0) {
                $stats['stock_sent_positive']++;
            }
        }

        foreach (array_chunk($stockBatch, 200) as $chunk) {
            try {
                $response = $client->api('PUT', '/stock-management/1/stocks', [
                    'json' => ['stocks' => $chunk],
                ]);
                $stocks = $response['stocks'] ?? [];
                if (!is_array($stocks)) {
                    $stats['stock_failed'] += count($chunk);
                    self::addApiError($stats, 0, 'Остатки: Avito вернул неожиданный ответ');
                    continue;
                }
                foreach ($stocks as $stock) {
                    if (!empty($stock['success'])) {
                        $stats['stock_success']++;
                    } else {
                        $stats['stock_failed']++;
                        $itemId = (int)($stock['item_id'] ?? 0);
                        $errors = $stock['errors'] ?? [];
                        $message = is_array($errors) ? implode('; ', array_map('strval', $errors)) : (string)$errors;
                        self::addApiError($stats, $itemId, 'Остаток: ' . ($message !== '' ? $message : 'не обновлен'));
                    }
                }
            } catch (\Throwable $e) {
                $stats['stock_failed'] += count($chunk);
                self::addApiError($stats, 0, 'Остатки: ' . $e->getMessage());
            }
        }

        self::checkStockInfo($client, array_keys($expectedStock), $expectedStock, $stats);

        return $stats;
    }

    private static function checkStockInfo(\app\services\AvitoApiClient $client, array $itemIds, array $expectedStock, array &$stats): void
    {
        foreach (array_chunk($itemIds, 10) as $chunk) {
            try {
                $response = $client->api('POST', '/stock-management/1/info', [
                    'json' => [
                        'item_ids' => array_map('intval', $chunk),
                        'strong_consistency' => true,
                    ],
                ]);
            } catch (\Throwable $e) {
                self::addApiError($stats, 0, 'Проверка остатков: ' . $e->getMessage());
                continue;
            }

            $stocks = $response['stocks'] ?? [];
            if (!is_array($stocks)) {
                continue;
            }

            foreach ($stocks as $stock) {
                $itemId = (int)($stock['item_id'] ?? 0);
                $quantity = (int)($stock['quantity'] ?? 0);
                $stats['stock_checked']++;
                if ($quantity > 0) {
                    $stats['stock_positive']++;
                }
                $expected = (int)($expectedStock[$itemId] ?? 0);
                if ($quantity !== $expected) {
                    $stats['stock_mismatch']++;
                    self::addApiError($stats, $itemId, 'После записи Avito API показывает остаток ' . $quantity . ', ожидали ' . $expected);
                }
            }
        }
    }

    public static function triggerAutoloadUpload(): array
    {
        try {
            $client = new \app\services\AvitoApiClient();
            $client->getAccessToken();
            $client->api('POST', '/autoload/v1/upload', [
                'rate_limit_retries' => 0,
            ]);
            return ['triggered' => true, 'message' => 'запущена'];
        } catch (\Throwable $e) {
            return ['triggered' => false, 'message' => $e->getMessage()];
        }
    }
    private static function apiItemId(array $row): int
    {
        foreach (['avito_id', 'ad_external_id'] as $field) {
            $value = preg_replace('~\D+~', '', (string)($row[$field] ?? ''));
            if ($value !== '') {
                return (int)$value;
            }
        }
        return 0;
    }

    private static function addApiError(array &$stats, int $itemId, string $message): void
    {
        if (count($stats['errors']) >= 5) {
            return;
        }
        $stats['errors'][] = ($itemId > 0 ? '#' . $itemId . ': ' : '') . $message;
    }
}












