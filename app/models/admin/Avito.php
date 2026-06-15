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
}
