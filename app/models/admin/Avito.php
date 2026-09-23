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
    public $attributes = [

        'avito_id'       => '',
        'status'         => 'draft',
        'ad_external_id' => '',
        'article'        => '',

        'date_begin'     => '',
        'date_end'       => '',
        'listing_fee'    => 'Package',
        'ad_status'      => 'Free',

        'manager_name'   => '',
        'contact_phone'  => '',
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
    public static function syncLinkedAdsFromProducts(): int
    {
        $rows = \R::getAll("\n            SELECT\n                a.id AS ad_id,\n                a.article AS ad_article,\n                p.id AS product_id,\n                p.article, p.name, p.description, p.content, p.price, p.quantity, p.hide,\n                p.img, p.unload_img, p.alias, p.model, p.weight,\n                b.name AS brand_name\n            FROM avito_ad a\n            INNER JOIN product p ON p.article = a.article\n            LEFT JOIN brand b ON b.id = p.brand_id\n            WHERE a.article IS NOT NULL AND a.article != ''\n        ");

        $updated = 0;
        foreach ($rows as $row) {
            $ad = \R::load('avito_ad', (int)$row['ad_id']);
            if (!$ad || !$ad->id) {
                continue;
            }

            $quantity = max(0, (int)($row['quantity'] ?? 0));
            $hidden = (int)($row['hide'] ?? 0) === 1;
            $price = (int)round((float)($row['price'] ?? 0));

            if (empty($ad->uuid)) {
                $ad->uuid = self::uuidV4();
            }
            if (empty($ad->ad_external_id)) {
                $ad->ad_external_id = self::externalIdFromArticle((string)$row['article']);
            }

            $ad->price_rub = $price > 0 ? $price : null;
            $ad->quantity = $quantity;
            $ad->status = ($hidden || $quantity <= 0 || $price <= 0) ? 'archived' : 'active';

            if (empty($ad->title)) {
                $ad->title = self::limitText((string)$row['name'], 50);
            }
            if (empty($ad->description)) {
                $ad->description = self::buildDescription($row);
            }
            if (empty($ad->brand) && !empty($row['brand_name'])) {
                $ad->brand = trim((string)$row['brand_name']);
            }
            if (empty($ad->model) && !empty($row['model'])) {
                $ad->model = trim((string)$row['model']);
            }
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

    public static function getFeedRows(int $id = 0): array
    {
        self::syncLinkedAdsFromProducts();
        if ($id > 0) {
            return \R::getAll("SELECT * FROM avito_ad WHERE id = ? LIMIT 1", [$id]);
        }
        return \R::getAll("SELECT * FROM avito_ad ORDER BY id DESC");
    }

    public static function buildFeedXml(int $id = 0): string
    {
        return self::buildXml(self::getFeedRows($id));
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
            self::xmlAdd($ad, 'ItemCondition', (string)($r['item_condition'] ?? 'Новое'));
            self::xmlAdd($ad, 'TargetAudience', (string)($r['target_audience'] ?? ''));
            self::xmlAdd($ad, 'VideoURL', (string)($r['video_url'] ?? ''));
            self::xmlAdd($ad, 'VideoFileURL', (string)($r['video_file_url'] ?? ''));
            self::xmlAdd($ad, 'Brand', (string)($r['brand'] ?? ''));
            self::xmlAdd($ad, 'Model', (string)($r['model'] ?? ''));
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
            'manager_name' => $cfg['default_manager_name'] ?? 'ИТС-Центр',
            'contact_phone' => $cfg['default_contact_phone'] ?? '+7 (495) 424-98-90',
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
}

