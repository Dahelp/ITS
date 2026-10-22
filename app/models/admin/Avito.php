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
    private const RIM_DIA_VALUES = '0,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,38,40,42,42.1,52,52.1,52.5,54,54.1,55,56,56.1,56.2,56.5,56.6,56.7,56.62,57,57.06,57.1,57.2,57.3,57.12,58,58.1,58.5,58.6,58.7,58.8,59,59.1,59.5,59.6,60,60.06,60.1,60.2,60.5,60.8,60.15,61.1,61.9,62.5,62.6,62.56,63,63.3,63.4,63.5,63.34,63.35,64,64.1,64.3,64.5,64.12,65,65.05,65.06,65.1,65.2,66,66.06,66.1,66.2,66.4,66.5,66.6,66.7,66.9,66.15,66.45,66.46,66.55,66.56,66.69,67,67.04,67.05,67.06,67.08,67.1,67.2,67.7,67.14,68,68.1,69,69.1,69.5,70,70.02,70.1,70.2,70.3,70.4,70.5,70.6,70.7,70.27,71,71.1,71.4,71.5,71.6,71.8,71.56,71.58,71.69,72,72.1,72.2,72.3,72.4,72.5,72.6,72.7,72.55,72.56,72.62,72.69,73,73.06,73.1,73.2,73.3,73.6,73.8,73.9,74,74.1,74.2,74.5,74.6,75,75.1,76,76.1,76.9,77,77.7,77.8,77.9,77.77,78,78.05,78.09,78.1,78.3,78.5,79,79.1,79.5,79.6,80,80.1,81.5,82,83,83.5,83.7,84,84.1,84.2,86.8,86.9,87,87.1,87.2,89,89.1,89.3,90,92,92.1,92.2,92.3,92.5,93,93.1,95,95.1,95.3,95.4,95.5,95.6,96,98,98.1,98.5,98.6,100,100.1,100.3,100.5,101,102,104.1,106,106.1,106.2,106.3,106.5,106.6,106.25,107,107.1,107.5,107.6,108,108.1,108.2,108.3,108.4,108.5,108.6,108.7,109,109.5,109.7,109.8,110,110.1,110.2,110.3,110.5,110.6,111,111.2,111.6,111.8,112,112.1,112.2,112.5,113,113.1,114.3,116.1,116.7,117,118,118.3,120,120.9,121,122.5,125,125.1,125.2,127,130,130.1,130.8,131,134,135,138.8,139,139.7,140,141,142,142.1,145,146,147,150,152.4,160,161,161.1,163,164,165,175,176,180,199.5,200,202,205,214,219,220,221,222,230,281,282,370,370.5,371,372,402,423,428.8,431,446,448,449,452,460,472,480,600.3';

    public static function rimDiaValues(): array
    {
        return explode(',', self::RIM_DIA_VALUES);
    }

    /**
     * RedBean rejects underscored bean types in dispense(), while the legacy
     * table is physically named avito_ad. Create the row with SQL and then
     * load it through the query API, which safely supports that table name.
     */
    public static function createAdBean()
    {
        $uuid = self::uuidV4();
        $id = 0;
        \R::exec('LOCK TABLES avito_ad WRITE');
        try {
            $id = (int)\R::getCell('SELECT COALESCE(MAX(id), 0) + 1 FROM avito_ad');
            \R::exec('INSERT INTO avito_ad (id, uuid) VALUES (?, ?)', [$id, $uuid]);
        } finally {
            \R::exec('UNLOCK TABLES');
        }
        $ad = \R::findOne('avito_ad', 'id = ?', [$id]);
        if (!$ad) {
            throw new \RuntimeException('Не удалось создать запись объявления Avito');
        }
        return $ad;
    }

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
        'ad_type'       => 'Товар от производителя',
        'product_type'  => 'Шины для грузовиков и спецтехники',
        'spare_part_type' => '',
        'technic_spare_part_type' => '',
        'part_type' => '',
        'cooling_system_spare_part_type' => '',
        'fuel_system_part_type' => '',
        'interior_part_type' => '',
        'availability' => '',
        'originality' => '',
        'manufacturer' => '',
        'oem_number' => '',
        'original_vendor' => '',
        'original_number' => '',
        'technic' => '',
        'vehicle_make' => '',
        'vehicle_model' => '',
        'vehicle_generation' => '',
        'vehicle_modification' => '',
        'nds' => '22',
        'postponed_payment' => '',
        'wholesale_type' => '',
        'wholesale_min_order_type' => '',
        'wholesale_min_order_value' => '',
        'wholesale_packing' => '',
        'wholesale_packing_value' => '',
        'wholesale_measure_unit' => '',
        'wholesale_discount_ladder_type' => '',
        'wholesale_discount_value' => '',
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
        'minimum_order_quantity' => '',
        'multi_item' => '',
        'rims_repair' => '',
        'rims_straightened' => '',
        'rims_welded' => '',
        'rims_with_cracks' => '',
        'rims_with_geometry_change' => '',
        'painting_type' => '',
        'center_caps' => '',
        'pressure_sensors' => '',
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
                    $ad = self::createAdBean();
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

                \app\services\AvitoWarehouse::apply($ad);
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
        \app\services\AvitoProductTemplate::ensureTitleStorage();
        // Cleanup of an orphan that could be left by the legacy failed insert
        // path. Never touch a populated row, even if its historical ID is 0.
        \R::exec("DELETE FROM avito_ad
            WHERE id = 0
              AND COALESCE(ad_external_id, '') = ''
              AND COALESCE(avito_id, '') = ''
              AND COALESCE(article, '') = ''
              AND COALESCE(title, '') = ''");

        $hasAvitoUrl = \R::getCell("SHOW COLUMNS FROM avito_ad LIKE 'avito_url'");
        if (!$hasAvitoUrl) {
            \R::exec("ALTER TABLE avito_ad ADD avito_url VARCHAR(512) NULL DEFAULT NULL AFTER avito_id");
        }

        $columns = [
            'avito_publication_status' => 'VARCHAR(64)',
            'avito_status_updated_at' => 'DATETIME',
            'deletion_requested_at' => 'DATETIME',
            'manual_fields_json' => 'TEXT',
            'rim_bolts' => 'VARCHAR(16)',
            'rim_bolts_diameter' => 'VARCHAR(32)',
            'rim_type' => 'VARCHAR(64)',
            'rim_dia' => 'VARCHAR(32)',
            'minimum_order_quantity' => 'VARCHAR(32)',
            'multi_item' => 'VARCHAR(8)',
            'rims_repair' => 'VARCHAR(16)',
            'rims_straightened' => 'VARCHAR(16)',
            'rims_welded' => 'VARCHAR(16)',
            'rims_with_cracks' => 'VARCHAR(16)',
            'rims_with_geometry_change' => 'VARCHAR(16)',
            'painting_type' => 'VARCHAR(32)',
            'center_caps' => 'VARCHAR(16)',
            'pressure_sensors' => 'VARCHAR(16)',
            'rim_offset' => 'VARCHAR(32)',
            'rim_width' => 'VARCHAR(32)',
            'review_status' => 'VARCHAR(32)',
            'spare_part_type' => 'VARCHAR(128)',
            'technic_spare_part_type' => 'VARCHAR(128)',
            'part_type' => 'VARCHAR(128)',
            'cooling_system_spare_part_type' => 'VARCHAR(128)',
            'fuel_system_part_type' => 'VARCHAR(128)',
            'interior_part_type' => 'VARCHAR(128)',
            'availability' => 'VARCHAR(32)',
            'originality' => 'VARCHAR(32)',
            'manufacturer' => 'VARCHAR(128)',
            'oem_number' => 'VARCHAR(128)',
            'original_vendor' => 'VARCHAR(128)',
            'original_number' => 'VARCHAR(128)',
            'technic' => 'VARCHAR(128)',
            'vehicle_make' => 'VARCHAR(128)',
            'vehicle_model' => 'VARCHAR(128)',
            'vehicle_generation' => 'VARCHAR(128)',
            'vehicle_modification' => 'VARCHAR(128)',
            'nds' => 'VARCHAR(32)',
            'postponed_payment' => 'VARCHAR(32)',
            'wholesale_type' => 'VARCHAR(16)',
            'wholesale_min_order_type' => 'VARCHAR(32)',
            'wholesale_min_order_value' => 'VARCHAR(32)',
            'wholesale_packing' => 'VARCHAR(32)',
            'wholesale_packing_value' => 'VARCHAR(32)',
            'wholesale_measure_unit' => 'VARCHAR(32)',
            'wholesale_discount_ladder_type' => 'VARCHAR(64)',
            'wholesale_discount_value' => 'VARCHAR(64)',
        ];
        foreach ($columns as $name => $definition) {
            if (!\R::getCell("SHOW COLUMNS FROM avito_ad LIKE ?", [$name])) {
                \R::exec("ALTER TABLE avito_ad ADD `{$name}` {$definition} NULL DEFAULT NULL");
            }
        }

        // До появления покомпонентной истории отличить вручную заменённые фото
        // от исходных было невозможно. Сохраняем текущий медианабор существующих
        // объявлений как исходную администраторскую версию; новые изменения
        // дальше фиксируются по каждому полю при сохранении формы.
        \R::exec("UPDATE avito_ad
            SET manual_fields_json = '[\"images_json\"]'
            WHERE (manual_fields_json IS NULL OR manual_fields_json = '')
              AND images_json IS NOT NULL AND images_json != ''");

        $wheelAxleColumn = \R::getRow("SHOW COLUMNS FROM avito_ad LIKE 'wheel_axle'");
        $wheelAxleType = (string)($wheelAxleColumn['Type'] ?? '');
        if ($wheelAxleColumn
            && stripos($wheelAxleType, 'enum(') === 0
            && stripos($wheelAxleType, 'Любая') === false) {
            \R::exec("ALTER TABLE avito_ad MODIFY wheel_axle VARCHAR(64) NULL DEFAULT NULL");
        }

        // Старый ENUM не принимает промышленные индексы A1–A8 и молча сохраняет пустую строку.
        $speedIndexColumn = \R::getRow("SHOW COLUMNS FROM avito_ad LIKE 'speed_index'");
        $speedIndexType = (string)($speedIndexColumn['Type'] ?? '');
        if ($speedIndexColumn && stripos($speedIndexType, 'enum(') === 0) {
            \R::exec("ALTER TABLE avito_ad MODIFY speed_index VARCHAR(16) NULL DEFAULT NULL");
        }

        // В актуальном справочнике Avito появилось третье значение «Продаю своё».
        $adTypeColumn = \R::getRow("SHOW COLUMNS FROM avito_ad LIKE 'ad_type'");
        $adTypeType = (string)($adTypeColumn['Type'] ?? '');
        if ($adTypeColumn && stripos($adTypeType, 'enum(') === 0) {
            \R::exec("ALTER TABLE avito_ad MODIFY ad_type VARCHAR(64) NULL DEFAULT NULL");
        }

        foreach (['goods_type', 'product_type'] as $field) {
            $column = \R::getRow("SHOW COLUMNS FROM avito_ad LIKE ?", [$field]);
            if ($column && stripos((string)($column['Type'] ?? ''), 'enum(') === 0) {
                \R::exec("ALTER TABLE avito_ad MODIFY `{$field}` VARCHAR(128) NULL DEFAULT NULL");
            }
        }

        // Контакты едины для всех объявлений, а черновики должны быть в архиве.
        \R::exec(
            "UPDATE avito_ad SET manager_name = ?, contact_phone = ?, "
            . "status = CASE WHEN status = 'draft' THEN 'archived' ELSE status END "
            . 'WHERE manager_name IS NULL OR manager_name != ? '
            . "OR contact_phone IS NULL OR contact_phone != ? OR status = 'draft'",
            [self::MANAGER_NAME, self::CONTACT_PHONE, self::MANAGER_NAME, self::CONTACT_PHONE]
        );

        // Единые коммерческие параметры для всех объявлений.
        \R::exec(
            'UPDATE avito_ad SET ad_type = ?, nds = ? WHERE ad_type IS NULL OR ad_type != ? OR nds IS NULL OR nds != ?',
            ['Товар от производителя', '22', 'Товар от производителя', '22']
        );

        // С 2026 года в объявлениях указываем НДС 22%. Нормализуем также HTML-пробелы.
        $descriptions = \R::getAll("SELECT id, description FROM avito_ad WHERE description LIKE '%НДС%20%'");
        foreach ($descriptions as $descriptionRow) {
            $description = (string)($descriptionRow['description'] ?? '');
            $updatedDescription = self::normalizeDescriptionNds($description);
            if ($updatedDescription !== $description) {
                \R::exec('UPDATE avito_ad SET description = ? WHERE id = ?', [
                    $updatedDescription,
                    (int)$descriptionRow['id'],
                ]);
            }
        }
    }

    public static function normalizeDescriptionNds(string $description): string
    {
        $updated = preg_replace(
            '~НДС(?:\\s|&nbsp;|&#160;)*(?:[:\\-–—](?:\\s|&nbsp;|&#160;)*)?20(?:\\s|&nbsp;|&#160;)*%~iu',
            'НДС 22%',
            $description
        );
        return $updated === null ? $description : $updated;
    }
    public static function syncLinkedAdsFromProducts(): int
    {
        self::ensureSchema();

        // Availability exposed to Avito must never include units already reserved.
        \R::exec("\n            UPDATE avito_ad a\n            INNER JOIN product p ON p.article = a.article\n            SET a.status = CASE\n                WHEN GREATEST(0, COALESCE(p.quantity, p.rest, 0) - COALESCE(p.reserve, 0)) <= 0 THEN 'archived'\n                ELSE 'active'\n            END\n        ");
        // A deletion request must win over inventory synchronization so the ad
        // stays absent from the XML feed while Avito processes its removal.
        \R::exec("UPDATE avito_ad SET status = 'archived' WHERE deletion_requested_at IS NOT NULL");

        $rows = \R::getAll("\n            SELECT\n                a.id AS ad_id,\n                a.article AS ad_article,\n                p.id AS product_id,\n                p.article, p.name, p.description, p.content, p.price, p.quantity, p.rest, p.hide,\n                p.img, p.unload_img, p.alias, p.model, p.weight,\n                (SELECT GROUP_CONCAT(DISTINCT CONCAT(ag.title, ': ', av.value) SEPARATOR ' ')\n                   FROM attribute_product ap\n                   INNER JOIN attribute_value av ON av.id = ap.attr_id\n                   INNER JOIN attribute_group ag ON ag.id = av.attr_group_id\n                  WHERE ap.product_id = p.id) AS attribute_text,\n                b.name AS brand_name\n            FROM avito_ad a\n            INNER JOIN product p ON p.article = a.article\n            LEFT JOIN brand b ON b.id = p.brand_id\n            WHERE a.article IS NOT NULL AND a.article != '' AND a.deletion_requested_at IS NULL\n        ");

        $availableByProduct = \R::getAssoc('SELECT id, GREATEST(0, COALESCE(quantity, rest, 0) - COALESCE(reserve, 0)) FROM product');
        $updated = 0;
        foreach ($rows as $row) {
            $ad = \R::load('avito_ad', (int)$row['ad_id']);
            if (!$ad || !$ad->id) {
                continue;
            }
            if (!empty($ad->deletion_requested_at)) {
                continue;
            }

            $quantity = (int)($availableByProduct[(int)$row['product_id']] ?? 0);
            $price = (int)round((float)($row['price'] ?? 0));
            $manualFields = self::manualFields($ad);

            if (empty($ad->uuid)) {
                $ad->uuid = self::uuidV4();
            }
            if (empty($ad->ad_external_id)) {
                $ad->ad_external_id = self::externalIdFromArticle((string)$row['article']);
            }

            if (!in_array('price_rub', $manualFields, true)) {
                $ad->price_rub = $price > 0 ? $price : null;
            }
            // Quantity в XML Avito — это цена «за N шт.», а не складской остаток.
            $ad->quantity = 'за 1 шт.';
            $ad->status = self::publicationStatus($quantity);

            if (empty($ad->title)) {
                $ad->title = \app\services\AvitoProductTemplate::title($row);
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
            if ($images && !in_array('images_json', $manualFields, true)) {
                $ad->images_json = json_encode($images, JSON_UNESCAPED_UNICODE);
            }

            \app\services\AvitoWarehouse::apply($ad);
            \R::store($ad);
            $updated++;
        }

        return $updated;
    }

    public static function publicationStatus(int $availableQuantity): string
    {
        return $availableQuantity > 0 ? 'active' : 'archived';
    }

    public static function deletionIsConfirmed(?string $publicationStatus): bool
    {
        return in_array(mb_strtolower(trim((string)$publicationStatus), 'UTF-8'), [
            'old', 'removed', 'archived', 'unpublished', 'deleted',
        ], true);
    }

    public static function numericAvitoItemId(array $row): int
    {
        return self::apiItemId($row);
    }

    public static function manualFields($ad): array
    {
        $fields = json_decode((string)($ad->manual_fields_json ?? ''), true);
        return is_array($fields) ? array_values(array_unique(array_filter(array_map('strval', $fields)))) : [];
    }

    public static function rememberManualFields($ad, array $fields): void
    {
        $protected = array_values(array_unique(array_merge(self::manualFields($ad), $fields)));
        sort($protected);
        $ad->manual_fields_json = json_encode($protected, JSON_UNESCAPED_UNICODE);
    }

    public static function publicationSummary(): array
    {
        self::ensureSchema();
        $row = \R::getRow("SELECT
            COUNT(*) AS total,
            SUM(status = 'active') AS feed_active,
            SUM(LOWER(COALESCE(avito_publication_status, '')) IN ('active', 'published')) AS avito_published,
            SUM(avito_publication_status IS NOT NULL AND avito_publication_status != ''
                AND LOWER(avito_publication_status) NOT IN ('active', 'published')) AS avito_not_published,
            SUM(avito_publication_status IS NULL OR avito_publication_status = '') AS avito_unchecked,
            SUM((avito_id IS NOT NULL AND avito_id != '') OR (ad_external_id REGEXP '^[0-9]+$')) AS with_avito_id,
            SUM(NOT ((avito_id IS NOT NULL AND avito_id != '') OR (ad_external_id REGEXP '^[0-9]+$'))) AS without_avito_id
            FROM avito_ad");
        foreach ($row as $key => $value) {
            $row[$key] = (int)$value;
        }
        return $row;
    }

    public static function extractAvitoPublicationStatus(array $item): string
    {
        foreach (['status', 'state'] as $field) {
            if (!array_key_exists($field, $item)) {
                continue;
            }
            if (is_scalar($item[$field])) {
                return trim((string)$item[$field]);
            }
            if (is_array($item[$field])) {
                foreach (['value', 'code', 'name', 'title'] as $key) {
                    if (isset($item[$field][$key]) && is_scalar($item[$field][$key])) {
                        return trim((string)$item[$field][$key]);
                    }
                }
            }
        }
        return '';
    }

    public static function avitoPublicationPresentation(?string $status): array
    {
        $raw = trim((string)$status);
        $normalized = function_exists('mb_strtolower')
            ? mb_strtolower($raw, 'UTF-8')
            : strtolower($raw);

        $map = [
            'active' => ['Опубликовано', 'success'],
            'published' => ['Опубликовано', 'success'],
            'moderation' => ['На проверке Avito', 'info'],
            'pending' => ['Ожидает публикации', 'info'],
            'processing' => ['Обрабатывается Avito', 'info'],
            'blocked' => ['Заблокировано Avito', 'danger'],
            'banned' => ['Заблокировано Avito', 'danger'],
            'rejected' => ['Отклонено Avito', 'danger'],
            'declined' => ['Отклонено Avito', 'danger'],
            'removed' => ['Снято с публикации', 'secondary'],
            'archived' => ['В архиве Avito', 'secondary'],
            'closed' => ['Завершено на Avito', 'secondary'],
            'expired' => ['Истёк срок публикации', 'warning'],
            'old' => ['Истёк срок публикации', 'warning'],
        ];

        if ($normalized === '') {
            return ['Не проверено', 'light'];
        }
        return $map[$normalized] ?? [$raw, 'secondary'];
    }

    public static function getFeedRows(int $id = 0, bool $onlyActive = false): array
    {
        self::syncLinkedAdsFromProducts();
        self::syncLegacyStatusesFromApi();
        if ($id > 0) {
            return \R::getAll("SELECT * FROM avito_ad WHERE id = ? AND deletion_requested_at IS NULL LIMIT 1", [$id]);
        }
        if ($onlyActive) {
            return \R::getAll("SELECT * FROM avito_ad WHERE status = 'active' AND deletion_requested_at IS NULL ORDER BY id DESC");
        }
        return \R::getAll("SELECT * FROM avito_ad WHERE deletion_requested_at IS NULL ORDER BY id DESC");
    }

    /**
     * Legacy Avito ads can predate the product/article link. Keep their feed
     * status in sync with the stock Avito already stores for that item ID.
     */
    public static function syncLegacyStatusesFromApi(): int
    {
        self::ensureSchema();
        $rows = \R::getAll("SELECT a.id, a.avito_id, a.ad_external_id
            FROM avito_ad a
            LEFT JOIN product p ON p.article = a.article
            WHERE p.id IS NULL AND a.deletion_requested_at IS NULL
              AND (a.avito_id REGEXP '^[0-9]+$' OR a.ad_external_id REGEXP '^[0-9]+$')");
        if (!$rows) {
            return 0;
        }

        try {
            $client = new \app\services\AvitoApiClient();
            $client->getAccessToken();
        } catch (\Throwable $e) {
            return 0;
        }

        $byItemId = [];
        foreach ($rows as $row) {
            $itemId = self::apiItemId($row);
            if ($itemId > 0) {
                $byItemId[$itemId][] = (int)$row['id'];
            }
        }

        $updated = 0;
        foreach (array_chunk(array_keys($byItemId), 10) as $chunk) {
            try {
                $response = $client->api('POST', '/stock-management/1/info', [
                    'json' => [
                        'item_ids' => array_map('intval', $chunk),
                        'strong_consistency' => true,
                    ],
                ]);
            } catch (\Throwable $e) {
                continue;
            }
            foreach (($response['stocks'] ?? []) as $stock) {
                $itemId = (int)($stock['item_id'] ?? 0);
                if ($itemId <= 0 || empty($byItemId[$itemId])) {
                    continue;
                }
                $status = self::publicationStatus((int)($stock['quantity'] ?? 0));
                foreach ($byItemId[$itemId] as $adId) {
                    $updated += (int)\R::exec('UPDATE avito_ad SET status = ? WHERE id = ? AND status != ? AND deletion_requested_at IS NULL', [$status, $adId, $status]);
                }
            }
        }
        return $updated;
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
            $r = \app\services\AvitoWarehouse::normalize($r);
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
            self::xmlAddIfNotEmpty($ad, 'SparePartType', (string)($r['spare_part_type'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'TechnicSparePartType', (string)($r['technic_spare_part_type'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'PartType', (string)($r['part_type'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'CoolingSystemSparePartType', (string)($r['cooling_system_spare_part_type'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'FuelSystemPartType', (string)($r['fuel_system_part_type'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'InteriorPartType', (string)($r['interior_part_type'] ?? ''));
            self::xmlAdd($ad, 'Title', (string)($r['title'] ?? ''));
            self::xmlCdata($ad, 'Description', self::descriptionValue($r));
            if (!empty($r['price_rub'])) {
                self::xmlAdd($ad, 'Price', (string)((int)$r['price_rub']));
            }
            self::xmlAdd($ad, 'Condition', (string)($r['item_condition'] ?? 'Новое'));
            self::xmlAddIfNotEmpty($ad, 'Availability', (string)($r['availability'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'Originality', (string)($r['originality'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'Manufacturer', (string)($r['manufacturer'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'OEM', (string)($r['oem_number'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'OriginalVendor', (string)($r['original_vendor'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'OriginalNumber', (string)($r['original_number'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'Technic', (string)($r['technic'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'Make', (string)($r['vehicle_make'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'Model', (string)($r['vehicle_model'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'Generation', (string)($r['vehicle_generation'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'Modification', (string)($r['vehicle_modification'] ?? ''));
            self::xmlAdd($ad, 'TargetAudience', (string)($r['target_audience'] ?? ''));
            self::xmlAdd($ad, 'VideoURL', (string)($r['video_url'] ?? ''));
            self::xmlAdd($ad, 'VideoFileURL', (string)($r['video_file_url'] ?? ''));
            $productType = trim((string)($r['product_type'] ?? ''));
            $isTruckTire = $productType === 'Шины для грузовиков и спецтехники';
            $isMotoTire = $productType === 'Мотошины';
            if ($isTruckTire || $isMotoTire) {
                self::xmlAddIfNotEmpty($ad, 'Brand', (string)($r['brand'] ?? ''));
            }
            if ($isTruckTire) {
                self::xmlAddIfNotEmpty($ad, 'Model', self::avitoModelValue($productType, (string)($r['model'] ?? '')));
                self::xmlAdd($ad, 'TireSectionWidth', (string)($r['tire_section_width'] ?? ''));
                self::xmlAdd($ad, 'TireAspectRatio', (string)($r['tire_aspect_ratio'] ?? ''));
                self::xmlAdd($ad, 'RimDiameter', (string)($r['rim_diameter'] ?? ''));
                self::xmlAddIfNotEmpty($ad, 'TireType', (string)($r['tire_type'] ?? ''));
                self::xmlAddIfNotEmpty($ad, 'Quantity', (string)($r['quantity'] ?? ''));
                self::xmlAddIfNotEmpty($ad, 'SpeedIndex', (string)($r['speed_index'] ?? ''));
                self::xmlAddIfNotEmpty($ad, 'PlyRayting', (string)($r['ply_rating'] ?? ''));
                self::xmlAddIfNotEmpty($ad, 'Construction', (string)($r['construction'] ?? ''));
                self::xmlAddIfNotEmpty($ad, 'TubeType', (string)($r['tube_type'] ?? ''));
                self::xmlAddIfNotEmpty($ad, 'WheelAxle', (string)($r['wheel_axle'] ?? ''));
                self::xmlAddIfNotEmpty($ad, 'LoadIndex', (string)($r['load_index'] ?? ''));
                self::xmlAddIfNotEmpty($ad, 'ResidualTreadSV', (string)($r['residual_tread_sv'] ?? ''));
                self::xmlAddIfNotEmpty($ad, 'Design', (string)($r['design'] ?? ''));
                self::xmlAddIfNotEmpty($ad, 'VehicleType', (string)($r['vehicle_type'] ?? ''));
            }
            if ($isMotoTire) {
                self::xmlAddIfNotEmpty($ad, 'Model', (string)($r['model'] ?? ''));
                self::xmlAdd($ad, 'RimDiameter', (string)($r['rim_diameter'] ?? ''));
                self::xmlAdd($ad, 'TireSectionWidth', (string)($r['tire_section_width'] ?? ''));
                self::xmlAdd($ad, 'TireAspectRatio', (string)($r['tire_aspect_ratio'] ?? ''));
                self::xmlAdd($ad, 'WheelAxle', (string)($r['wheel_axle'] ?? ''));
            }
            if ($productType === 'Диски') {
                self::xmlAddIfNotEmpty($ad, 'RimDiameter', (string)($r['rim_diameter'] ?? ''));
                self::xmlAdd($ad, 'RimBolts', (string)($r['rim_bolts'] ?? ''));
                self::xmlAdd($ad, 'RimBoltsDiameter', (string)($r['rim_bolts_diameter'] ?? ''));
                self::xmlAdd($ad, 'RimType', (string)($r['rim_type'] ?? ''));
                self::xmlAdd($ad, 'RimDIA', (string)($r['rim_dia'] ?? ''));
                self::xmlAdd($ad, 'RimOffset', (string)($r['rim_offset'] ?? ''));
                self::xmlAdd($ad, 'RimWidth', (string)($r['rim_width'] ?? ''));
                self::xmlAddIfNotEmpty($ad, 'MinimumOrderQuantity', (string)($r['minimum_order_quantity'] ?? ''));
                self::xmlAddIfNotEmpty($ad, 'MultiItem', (string)($r['multi_item'] ?? ''));
                self::xmlAddIfNotEmpty($ad, 'RimsRepair', (string)($r['rims_repair'] ?? ''));
                self::xmlAddIfNotEmpty($ad, 'RimsStraightened', (string)($r['rims_straightened'] ?? ''));
                self::xmlAddIfNotEmpty($ad, 'RimsWelded', (string)($r['rims_welded'] ?? ''));
                self::xmlAddIfNotEmpty($ad, 'RimsWithCracks', (string)($r['rims_with_cracks'] ?? ''));
                self::xmlAddIfNotEmpty($ad, 'RimsWithGeometryChange', (string)($r['rims_with_geometry_change'] ?? ''));
                self::xmlAddIfNotEmpty($ad, 'PaintingType', (string)($r['painting_type'] ?? ''));
                self::xmlAddIfNotEmpty($ad, 'CenterCaps', (string)($r['center_caps'] ?? ''));
                self::xmlAddIfNotEmpty($ad, 'PressureSensors', (string)($r['pressure_sensors'] ?? ''));
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
            self::xmlAddIfNotEmpty($ad, 'NDS', (string)($r['nds'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'PostponedPayment', (string)($r['postponed_payment'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'WholesaleType', (string)($r['wholesale_type'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'WholesaleMinOrderType', (string)($r['wholesale_min_order_type'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'WholesaleMinOrderValue', (string)($r['wholesale_min_order_value'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'WholesalePacking', (string)($r['wholesale_packing'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'WholesalePackingValue', (string)($r['wholesale_packing_value'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'WholesaleMeasureUnit', (string)($r['wholesale_measure_unit'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'WholesaleDiscountLadderType', (string)($r['wholesale_discount_ladder_type'] ?? ''));
            self::xmlAddIfNotEmpty($ad, 'WholesaleDiscountValue', (string)($r['wholesale_discount_value'] ?? ''));
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
        \app\services\AvitoWarehouse::apply($ad);
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
            'ad_type' => 'Товар от производителя',
            'nds' => '22',
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
        \app\services\AvitoTireFields::apply($ad, $row);

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
            // ATV uses flotation sizing: outer diameter × width - rim (e.g. 13×6-5).
            // Avito has no outer-diameter field, so the complete size stays in Title,
            // while supported structured fields receive width and rim. Profile is selected manually.
            if (preg_match('~\b\d+(?:[.,]\d+)?\s*[xх×]\s*(\d+(?:[.,]\d+)?)\s*-\s*(\d+(?:[.,]\d+)?)\b~iu', $text, $m)) {
                if (empty($ad->tire_section_width)) $ad->tire_section_width = str_replace(',', '.', $m[1]);
                if (empty($ad->rim_diameter)) $ad->rim_diameter = str_replace(',', '.', $m[2]);
            }
            if (empty($ad->wheel_axle)) $ad->wheel_axle = 'Любая';
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
        if ($productType !== 'Шины для грузовиков и спецтехники') {
            return '';
        }
        $modelAliases = [
            'L5' => 'L-5',
            'L 5' => 'L-5',
            'E3/L-3' => 'E3/L3',
            'E3-L3' => 'E3/L3',
        ];
        foreach ($modelAliases as $source => $target) {
            if (strcasecmp($model, $source) === 0) {
                $model = $target;
                break;
            }
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
        $tireOptional = [
            'brand' => 'Бренд', 'model' => 'Модель', 'tire_section_width' => 'Ширина профиля',
            'tire_aspect_ratio' => 'Высота профиля', 'rim_diameter' => 'Диаметр',
            'tire_type' => 'Сезонность', 'speed_index' => 'Индекс скорости',
            'ply_rating' => 'Слойность', 'construction' => 'Каркас', 'tube_type' => 'Тип TT/TL',
            'wheel_axle' => 'Ось', 'load_index' => 'Индекс нагрузки',
        ];
        $partsOptional = [
            'availability' => 'Доступность', 'originality' => 'Происхождение',
            'manufacturer' => 'Производитель', 'oem_number' => 'Номер детали OEM',
            'original_vendor' => 'Производитель оригинала', 'original_number' => 'Номер оригинала',
            'technic' => 'Тип спецтехники', 'vehicle_make' => 'Марка техники',
            'vehicle_model' => 'Модель техники', 'vehicle_generation' => 'Поколение',
            'vehicle_modification' => 'Модификация',
        ];
        $diskOptional = [
            'availability' => 'Доступность',
            'minimum_order_quantity' => 'Минимальное количество',
            'multi_item' => 'Объединение объявлений',
            'rims_repair' => 'Ремонт дисков',
            'rims_straightened' => 'Выпрямление дисков',
            'rims_welded' => 'Сварка дисков',
            'rims_with_cracks' => 'Трещины',
            'rims_with_geometry_change' => 'Изменение геометрии',
            'painting_type' => 'Покраска',
            'center_caps' => 'Центральные колпачки',
            'pressure_sensors' => 'Датчики давления',
        ];
        $optional = $tireOptional;
        $type = trim((string)($row['product_type'] ?? ''));
        if ($type === 'Шины для грузовиков и спецтехники') {
            $required += [
                'brand' => 'Бренд', 'tire_section_width' => 'Ширина профиля',
                'tire_aspect_ratio' => 'Высота профиля', 'rim_diameter' => 'Диаметр',
            ];
        } elseif ($type === 'Мотошины') {
            $required += [
                'tire_section_width' => 'Ширина профиля', 'tire_aspect_ratio' => 'Высота профиля',
                'rim_diameter' => 'Диаметр', 'wheel_axle' => 'Ось',
            ];
            $optional = ['brand' => 'Бренд', 'model' => 'Модель'];
        } elseif ($type === 'Диски') {
            $optional = $diskOptional;
            $required += [
                'rim_diameter' => 'Диаметр', 'rim_bolts' => 'Количество отверстий',
                'rim_bolts_diameter' => 'PCD', 'rim_type' => 'Тип диска', 'rim_dia' => 'DIA',
                'rim_offset' => 'Вылет ET', 'rim_width' => 'Ширина обода',
            ];
        } elseif ($type === 'Для грузовиков и спецтехники') {
            $optional = $partsOptional;
            $required += [
                'spare_part_type' => 'Вид запчасти',
                'technic_spare_part_type' => 'Тип запчасти',
            ];
            $categoryFields = [
                'Система смазки' => ['part_type' => 'Тип детали'],
                'Система выпуска' => ['part_type' => 'Тип детали'],
                'Система охлаждения ДВС' => ['cooling_system_spare_part_type' => 'Тип детали системы охлаждения'],
                'Топливная система' => ['fuel_system_part_type' => 'Тип детали топливной системы'],
                'Детали салона' => ['interior_part_type' => 'Тип детали салона'],
            ];
            foreach ($categoryFields[trim((string)($row['technic_spare_part_type'] ?? ''))] ?? [] as $field => $label) {
                $optional[$field] = $label;
            }
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

        $provisionalRequired = [];
        $provisionalTitle = trim((string)($row['title'] ?? ''));
        foreach ($required as $field => $label) {
            if (mb_strtolower(trim((string)($row[$field] ?? '')), 'UTF-8') !== 'другое') {
                continue;
            }
            $detail = $label . ': выбрано «Другое»';
            if ($field === 'tire_aspect_ratio'
                && preg_match('~(?<!\d)(\d+(?:[.,]\d+)?)\s*[xх×]\s*\d+(?:[.,]\d+)?\s*[-xх×]\s*\d+(?:[.,]\d+)?~ui', $provisionalTitle, $match)) {
                $detail .= ' (ожидаемое значение ' . str_replace(',', '.', $match[1]) . ')';
            }
            $provisionalRequired[$field] = $detail;
        }

        $warnings = [];
        $title = trim((string)($row['title'] ?? ''));
        $description = trim(strip_tags((string)($row['description'] ?? '')));
        if ($title !== '' && (mb_strlen($title, 'UTF-8') < 12 || mb_strlen($title, 'UTF-8') > \app\services\AvitoProductTemplate::TITLE_LIMIT)) {
            $warnings[] = 'Название желательно сделать длиной 12–50 символов';
        }
        if ($titleError = \app\services\AvitoProductTemplate::titleError($title)) {
            $missingRequired['title'] = $titleError;
        }
        foreach (\app\services\AvitoTireFields::validationErrors($row) as $field => $message) {
            $warnings[] = ($labels[$field] ?? $field).': '.$message.' (справочник от 15.10.2026)';
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
        if ($type === 'Диски') {
            $rimDia = str_replace(',', '.', trim((string)($row['rim_dia'] ?? '')));
            if ($rimDia !== '' && !in_array($rimDia, self::rimDiaValues(), true)) {
                $warnings[] = 'DIA «' . $rimDia . '» отсутствует в справочнике Avito — выберите допустимое значение';
            }
        }
        foreach ($provisionalRequired as $detail) {
            $warnings[] = $detail . ' — проверить справочник Avito и заменить точным значением, когда оно появится';
        }

        $requiredTotal = count($required);
        $requiredFilled = $requiredTotal - count($missingRequired);
        $optionalTotal = count($optional);
        $optionalFilled = $optionalTotal - count($missingOptional);
        $requiredScore = $requiredTotal ? (int)round($requiredFilled * 100 / $requiredTotal) : 100;
        $optionalScore = $optionalTotal ? (int)round($optionalFilled * 100 / $optionalTotal) : 100;
        $textScore = 0;
        if ($title !== '') $textScore += 25;
        if (mb_strlen($title, 'UTF-8') >= 12 && mb_strlen($title, 'UTF-8') <= \app\services\AvitoProductTemplate::TITLE_LIMIT) $textScore += 15;
        if ($descriptionLength >= 250) $textScore += 25;
        if ($descriptionLength >= 600) $textScore += 15;
        if (preg_match('~\d~u', $title)) $textScore += 10;
        if (count(self::decodeImages($row['images_json'] ?? '')) >= 2) $textScore += 10;

        $reviewStatus = trim((string)($row['review_status'] ?? 'confirmed'));
        if ($missingRequired) {
            $status = 'error';
            $label = 'Нужно исправить';
        } elseif ($provisionalRequired) {
            $status = 'warning';
            $label = 'Заполнено временно — требуется уточнение';
        } elseif ($reviewStatus === 'needs_review') {
            $status = 'warning';
            $label = 'Требует проверки';
        } elseif ($missingOptional || $warnings) {
            $status = 'warning';
            $label = 'Подтверждено — есть рекомендации';
        } else {
            $status = 'ready';
            $label = 'Готово к выгрузке';
        }

        return compact(
            'status', 'label', 'missingRequired', 'provisionalRequired', 'missingOptional', 'warnings',
            'requiredScore', 'optionalScore', 'textScore', 'requiredTotal', 'optionalTotal'
        );
    }

    public static function createArchivedForProduct(int $productId): int
    {
        self::ensureSchema();
        $row = \R::getRow("SELECT p.*, b.name AS brand_name,
            (SELECT GROUP_CONCAT(DISTINCT CONCAT(ag.title, ': ', av.value) SEPARATOR '\n')
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

        $ad = self::createAdBean();
        $ad->uuid = self::uuidV4();
        $ad->ad_external_id = self::externalIdFromArticle($article);
        $ad->article = $article;
        $ad->status = 'archived';
        $ad->review_status = 'needs_review';
        $ad->title = \app\services\AvitoProductTemplate::title($row);
        $ad->description = self::buildGeneratedDescription($row);
        $ad->price_rub = (int)round((float)($row['price'] ?? 0));
        $ad->quantity = 'за 1 шт.';
        $name = mb_strtolower((string)($row['name'] ?? ''), 'UTF-8');
        $ad->product_type = self::generatedProductType($row);
        self::fillDefaults($ad);
        self::normalizeCatalogFields($ad, $row);
        if (!empty($row['weight'])) $ad->weight_kg = (string)$row['weight'];
        $images = self::productImageUrls($row);
        if ($images) $ad->images_json = json_encode($images, JSON_UNESCAPED_UNICODE);
        \app\services\AvitoWarehouse::apply($ad);
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

    public static function buildGeneratedDescription(array $row): string
    {
        $name = trim((string)($row['name'] ?? ''));
        $source = self::buildDescription($row);
        $attributes = trim((string)($row['attribute_text'] ?? ''));
        $type = self::generatedProductType($row);

        if ($type === 'Мотошины') {
            $intro = 'Новая шина для квадроцикла, ATV или другой мототехники.';
        } elseif ($type === 'Диски') {
            $intro = 'Новый колёсный диск для грузовой или специальной техники.';
        } elseif ($type === 'Фильтры и фильтроэлементы') {
            $intro = 'Новый фильтр или фильтроэлемент для обслуживания техники.';
        } elseif ($type === 'Система охлаждения ДВС') {
            $intro = 'Новая деталь системы охлаждения двигателя.';
        } elseif ($type === 'Система смазки') {
            $intro = 'Новая деталь системы смазки двигателя.';
        } elseif ($type === 'Система выпуска') {
            $intro = 'Новая деталь системы выпуска.';
        } elseif ($type === 'Топливная система') {
            $intro = 'Новая деталь топливной системы.';
        } elseif ($type === 'Детали салона') {
            $intro = 'Новая деталь кабины или салона.';
        } else {
            $intro = ''; // Назначение конкретной шины уже указано в названии и характеристиках.
        }

        $productWord = in_array($type, ['Мотошины', 'Шины для грузовиков и спецтехники'], true) ? 'ШИНЫ' : 'ТОВАРЫ';
        $html = '<p><strong>▶️ ТОЛЬКО НОВЫЕ ' . $productWord . ' С ГАРАНТИЕЙ!</strong><br>'
            . ($intro !== '' ? '◀️ ' . self::descriptionHtml($intro) . ' В наличии на складе.' : '') . '</p>';
        $html .= '<p><strong>' . self::descriptionHtml($name) . '</strong></p>';
        if ($source !== '' && $source !== $name) {
            $source = self::limitText($source, 2800);
            $sentences = preg_split('~(?<=[.!?])\s+(?=[А-ЯA-Z0-9])~u', $source, -1, PREG_SPLIT_NO_EMPTY);
            if (!$sentences) $sentences = [$source];
            foreach (array_chunk($sentences, 2) as $paragraph) {
                $html .= '<p>' . self::descriptionHtml(implode(' ', $paragraph)) . '</p>';
            }
        }
        $attributeRows = preg_split('~\R+~u', $attributes, -1, PREG_SPLIT_NO_EMPTY);
        if ($attributeRows) {
            $html .= '<p><strong>Характеристики:</strong><br>';
            foreach ($attributeRows as $attribute) {
                $attribute = trim($attribute);
                if (preg_match('~^([^:]{1,80}):\s*(.+)$~u', $attribute, $parts)) {
                    $html .= '<strong>' . self::descriptionHtml($parts[1]) . ':</strong> '
                        . self::descriptionHtml($parts[2]) . '<br>';
                } else {
                    $html .= self::descriptionHtml($attribute) . '<br>';
                }
            }
            $html .= '</p>';
        }
        $html .= '<p><strong>Цена и наличие:</strong><br>'
            . '<strong>Цена указана за 1 шт.</strong><br>'
            . 'Актуальное количество смотрите в объявлении.</p>'
            . '<p><strong>Доставка:</strong><br>'
            . '▶️ Москва и область: доставка курьером, стоимость рассчитывается по адресу.<br>'
            . '▶️ По России: отправка транспортными компаниями.<br>'
            . '👉 Бесплатно довозим товар до терминала транспортной компании.<br>'
            . 'Возможен самовывоз со склада ИТС-Центр.</p>'
            . '<p><strong>Оплата:</strong><br>'
            . '✅ Наличные или карта при самовывозе.<br>'
            . '✅ Безналичная оплата по счёту.<br>'
            . '✅ Работаем с НДС 22%, предоставляем документы.</p>'
            . '<p><strong>Обращайтесь — поможем с выбором и ответим на вопросы!</strong><br>'
            . '🧡 Добавьте объявление в избранное, чтобы не потерять.</p>';
        return self::limitText($html, 7500);
    }

    private static function descriptionHtml(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function applyGeneratedTemplateToAd(int $adId): void
    {
        self::ensureSchema();
        $ad = \R::findOne('avito_ad', 'id = ?', [$adId]);
        if (!$ad) throw new \RuntimeException('Объявление не найдено');
        $row = \R::getRow("SELECT p.*, b.name AS brand_name,
            (SELECT GROUP_CONCAT(DISTINCT CONCAT(ag.title, ': ', av.value) SEPARATOR '\n')
               FROM attribute_product ap
               INNER JOIN attribute_value av ON av.id = ap.attr_id
               INNER JOIN attribute_group ag ON ag.id = av.attr_group_id
              WHERE ap.product_id = p.id) AS attribute_text
            FROM product p LEFT JOIN brand b ON b.id = p.brand_id
            WHERE p.article = ? LIMIT 1", [trim((string)$ad->article)]);
        if (!$row) throw new \RuntimeException('Связанный товар не найден');
        $ad->description = self::buildGeneratedDescription($row);
        self::rememberManualFields($ad, ['description']);
        \app\services\AvitoWarehouse::apply($ad);
        \R::store($ad);
    }

    public static function generatedProductType(array $row): string
    {
        return \app\services\AvitoProductTemplate::productType($row);
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
            throw new \RuntimeException('Не удалось подключиться к Авито для получения номеров объявлений.');
        }
        $updated = self::syncAvitoIdsFromExternalIds($client);
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
                $publicationStatus = self::extractAvitoPublicationStatus($item);
                if ($itemId === '') {
                    continue;
                }

                $count = \R::exec(
                    "UPDATE avito_ad
                     SET avito_url = CASE WHEN ? != '' THEN ? ELSE avito_url END,
                         avito_publication_status = CASE WHEN ? != '' THEN ? ELSE avito_publication_status END,
                         avito_status_updated_at = NOW()
                     WHERE avito_id = ? OR ad_external_id = ?",
                    [$url, $url, $publicationStatus, $publicationStatus, $itemId, $itemId]
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

    private static function syncAvitoIdsFromExternalIds(\app\services\AvitoApiClient $client): int
    {
        $stats = \app\services\AvitoIdSync::refreshPending(true, $client);
        if (!empty($stats['busy'])) throw new \RuntimeException('Проверка номеров Авито уже выполняется. Повторите через минуту.');
        if (!empty($stats['errors'])) throw new \RuntimeException(implode(' ', $stats['errors']));
        return (int)$stats['assigned'];
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

        $rows = \R::getAll("\n            SELECT\n                a.id AS ad_id,\n                a.avito_id,\n                a.ad_external_id,\n                a.price_rub,\n                a.quantity,\n                p.rest\n            FROM avito_ad a\n            LEFT JOIN product p ON p.article = a.article\n            WHERE a.article IS NOT NULL AND a.article != '' AND a.deletion_requested_at IS NULL\n        ");

        if (!$rows) {
            return $stats;
        }

        $availableByAd = \R::getAssoc("SELECT a.id,
            GREATEST(0, COALESCE(p.quantity, p.rest, 0) - COALESCE(p.reserve, 0))
            FROM avito_ad a LEFT JOIN product p ON p.article = a.article");

        $client = new \app\services\AvitoApiClient();
        $client->getAccessToken();

        $stockBatch = [];
        $expectedStock = [];

        foreach ($rows as $row) {
            $itemId = self::apiItemId($row);
            $price = (int)round((float)($row['price_rub'] ?? 0));
            $quantity = (int)($availableByAd[(int)$row['ad_id']] ?? 0);
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
            $value = trim((string)($row[$field] ?? ''));
            if ($value !== '' && ctype_digit($value)) {
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












