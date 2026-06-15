<?php

namespace app\controllers\admin;

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class ExportController extends AppController {

    public function indexAction(){
        if(!empty($_POST)) {
            $products = $this->getProductsForExport($_POST);

            if(!$products) {
                $_SESSION['error'] = 'Товары для экспорта не найдены';
                redirect(ADMIN . '/export');
            }

            $format = $_POST['format'] ?? '';
            $priceType = $_POST['price_type'] ?? 'retail';

            switch($format) {
                case 'xls':
                    $file = $this->exportXls($products, $priceType);
                    break;
                case 'csv':
                    $file = $this->exportCsv($products, $priceType);
                    break;
                case 'xml':
                    $file = $this->exportXml($products, $priceType);
                    break;
                case 'pdf':
                    $file = $this->exportPdf($products, $priceType);
                    break;
                default:
                    $_SESSION['error'] = 'Выберите формат файла';
                    redirect(ADMIN . '/export');
            }

            $historyError = '';
            $historySaved = $this->saveHistory($file, $format, $priceType, count($products), $_POST, $historyError);
            $_SESSION['success'] = 'Экспорт выполнен! Скачать файл: <a href="'.$this->html($file['url']).'" target="_blank">'.$this->html($file['url']).'</a>';
            if(!$historySaved) {
                $_SESSION['success'] .= '<br>История не сохранена: '.$this->html($historyError ?: 'ошибка записи в export_history');
            }
            redirect(ADMIN . '/export');
        }

        $categories = \R::getAll("SELECT id, name, parent_id FROM category ORDER BY parent_id, name");
        $brands = \R::getAll("SELECT id, name FROM brand ORDER BY name");
        $historyTableMissing = false;

        try {
            $history = \R::getAll("SELECT * FROM export_history ORDER BY id DESC LIMIT 100");
        } catch(\Exception $e) {
            $history = [];
            $historyTableMissing = true;
        }

        $this->setMeta('Экспорт товаров');
        $this->set(compact('categories', 'brands', 'history', 'historyTableMissing'));
    }

    public function deleteAction(){
        $id = $this->getRequestID();
        $file = \R::getRow("SELECT * FROM export_history WHERE id = ?", [$id]);

        if(!$file) {
            $_SESSION['error'] = 'Файл не найден в истории экспорта';
            redirect(ADMIN . '/export');
        }

        $absolutePath = $this->absoluteExportPath($file['file_path']);
        if($absolutePath && is_file($absolutePath)) {
            @unlink($absolutePath);
        }

        \R::exec("DELETE FROM export_history WHERE id = ?", [$id]);
        $_SESSION['success'] = 'Файл удален из папки и истории';
        redirect(ADMIN . '/export');
    }

    protected function getProductsForExport($data){
        $mode = $data['actSelect'] ?? '';
        $where = [];
        $params = [];

        if($mode == '1' || $mode == '4') {
            $categoryId = !empty($data['category_id']) ? (int)$data['category_id'] : 0;
            if(!$categoryId) {
                $_SESSION['error'] = 'Выберите категорию товаров';
                redirect(ADMIN . '/export');
            }
            $where[] = 'product.category_id = ?';
            $params[] = $categoryId;
        }

        if($mode == '2' || $mode == '4') {
            $brandId = !empty($data['brand_id']) ? (int)$data['brand_id'] : 0;
            if(!$brandId) {
                $_SESSION['error'] = 'Выберите производителя';
                redirect(ADMIN . '/export');
            }
            $where[] = 'product.brand_id = ?';
            $params[] = $brandId;
        }

        if($mode == '3') {
            $articles = $this->parseArticles($data['article'] ?? '');
            if(!$articles) {
                $_SESSION['error'] = 'Укажите артикул товара';
                redirect(ADMIN . '/export');
            }
            $where[] = 'product.article IN ('.implode(',', array_fill(0, count($articles), '?')).')';
            $params = array_merge($params, $articles);
        }

        if($mode == '5') {
            $where[] = '1 = 1';
        }

        if(!$mode) {
            $_SESSION['error'] = 'Выберите что выгружать';
            redirect(ADMIN . '/export');
        }

        $sqlWhere = $where ? 'WHERE '.implode(' AND ', $where) : '';
        return \R::getAll("SELECT product.*, brand.name AS brand_name, category.name AS category_name
            FROM product
            LEFT JOIN brand ON brand.id = product.brand_id
            LEFT JOIN category ON category.id = product.category_id
            {$sqlWhere}
            ORDER BY product.name", $params);
    }

    protected function parseArticles($value){
        $items = preg_split('/[\s,;]+/', trim((string)$value));
        $items = array_filter(array_map('trim', $items), function($item){
            return $item !== '';
        });
        return array_values(array_unique($items));
    }

    protected function exportXls($products, $priceType){
        $fileName = $this->fileName('xls');
        $filePath = WWW . '/xls/' . $fileName;
        $this->ensureDirectory(dirname($filePath));

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Export');

        foreach(['A', 'B', 'C', 'D', 'E', 'F'] as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->setCellValue('A1', 'ID (артикул)');
        $sheet->setCellValue('B1', 'Номенклатура');
        $sheet->setCellValue('C1', 'Наличие');
        $sheet->setCellValue('D1', $this->priceTitle($priceType));
        $sheet->setCellValue('E1', 'Ссылка на товар');
        $sheet->setCellValue('F1', 'Параметры');

        $row = 2;
        foreach($products as $prod) {
            $url = PATH . '/product/' . $prod['alias'];
            $sheet->setCellValue('A'.$row, $prod['article']);
            $sheet->setCellValue('B'.$row, $prod['name']);
            $sheet->setCellValue('C'.$row, $prod['quantity']);
            $sheet->setCellValue('D'.$row, $this->priceValue($prod, $priceType));
            $sheet->setCellValue('E'.$row, $url);
            $sheet->setCellValue('F'.$row, $this->paramsText((int)$prod['id']));
            $sheet->getCell('E'.$row)->getHyperlink()->setUrl($url);
            $sheet->getStyle('E'.$row)->applyFromArray([
                'font' => [
                    'color' => ['rgb' => '0000FF'],
                    'underline' => 'single',
                ],
            ]);
            $row++;
        }

        $writer = new Xls($spreadsheet);
        $writer->save($filePath);

        return $this->fileResult($filePath, 'xls/' . $fileName, PATH . '/xls/' . $fileName, 'xls');
    }

    protected function exportCsv($products, $priceType){
        $fileName = $this->fileName('csv');
        $filePath = WWW . '/csv/' . $fileName;
        $this->ensureDirectory(dirname($filePath));

        $fd = fopen($filePath, 'wb');
        fwrite($fd, "\xEF\xBB\xBF");
        fputcsv($fd, ['ID (артикул)', 'Номенклатура', 'Наличие', $this->priceTitle($priceType), 'Ссылка на товар', 'Параметры'], ';');

        foreach($products as $prod) {
            fputcsv($fd, [
                $prod['article'],
                $prod['name'],
                $prod['quantity'],
                $this->priceValue($prod, $priceType),
                PATH . '/product/' . $prod['alias'],
                $this->paramsText((int)$prod['id']),
            ], ';');
        }

        fclose($fd);

        return $this->fileResult($filePath, 'csv/' . $fileName, PATH . '/csv/' . $fileName, 'csv');
    }

    protected function exportXml($products, $priceType){
        $fileName = $this->fileName('xml');
        $filePath = WWW . '/xml/' . $fileName;
        $this->ensureDirectory(dirname($filePath));
        $date = date('Y-m-d H:i');

        $categories = $this->categoriesForProducts($products);

        $text = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $text .= "<yml_catalog date=\"".$this->xml($date)."\">\n";
        $text .= "<shop>\n";
        $text .= "<name></name>\n";
        $text .= "<company></company>\n";
        $text .= "<url>".$this->xml(PATH)."</url>\n";
        $text .= "<currencies><currency id=\"RUR\" rate=\"1\"/></currencies>\n";
        $text .= "<categories>\n";

        foreach($categories as $cat) {
            $parent = !empty($cat['parent_id']) ? ' parentId="'.$this->xml($cat['parent_id']).'"' : '';
            $text .= "<category id=\"".$this->xml($cat['id'])."\"".$parent.">".$this->xml($cat['name'])."</category>\n";
        }

        $text .= "</categories>\n";
        $text .= "<offers>\n";

        foreach($products as $prod) {
            $quantity = (int)$prod['quantity'];
            $available = $quantity > 0 ? 'true' : 'false';
            $text .= "<offer id=\"".$this->xml($prod['article'])."\" available=\"".$available."\">\n";
            $text .= "<url>".$this->xml(PATH . '/product/' . $prod['alias'])."</url>\n";
            $text .= "<price>".$this->xml($this->priceValue($prod, $priceType))."</price>\n";
            $text .= "<currencyId>RUR</currencyId>\n";
            $text .= "<categoryId>".$this->xml($prod['category_id'])."</categoryId>\n";
            if(!empty($prod['img'])) {
                $text .= "<picture>".$this->xml(PATH . '/images/product/baseimg/' . $prod['img'])."</picture>\n";
            }
            $text .= "<quantity>".$quantity."</quantity>\n";
            $text .= "<store>true</store>\n";
            $text .= "<pickup>true</pickup>\n";
            $text .= "<delivery>true</delivery>\n";
            $text .= "<local_delivery_cost></local_delivery_cost>\n";
            $text .= "<name>".$this->xml($prod['name'])."</name>\n";
            $text .= "<model>".$this->xml($prod['model'])."</model>\n";
            $text .= "<vendor>".$this->xml($prod['brand_name'])."</vendor>\n";
            $text .= "<sales_notes></sales_notes>\n";
            $text .= "<description></description>\n";

            foreach($this->productParams((int)$prod['id']) as $param) {
                $text .= "<param name=\"".$this->xml($param['attribute_name'])."\">".$this->xml($param['attribute_text'])."</param>\n";
            }

            $text .= "<country_of_origin></country_of_origin>\n";
            $text .= "</offer>\n";
        }

        $text .= "</offers>\n";
        $text .= "</shop>\n";
        $text .= "</yml_catalog>";

        file_put_contents($filePath, $text);

        return $this->fileResult($filePath, 'xml/' . $fileName, PATH . '/xml/' . $fileName, 'xml');
    }

    protected function exportPdf($products, $priceType){
        $fileName = $this->fileName('pdf');
        $filePath = WWW . '/xls/' . $fileName;
        $this->ensureDirectory(dirname($filePath));
        $rows = '';

        foreach($products as $prod) {
            $rows .= '<tr>';
            $rows .= '<td>'.$this->html($prod['article']).'</td>';
            $rows .= '<td>'.$this->html($prod['name']).'</td>';
            $rows .= '<td>'.$this->html($prod['quantity']).'</td>';
            $rows .= '<td>'.$this->html($this->priceValue($prod, $priceType)).'</td>';
            $rows .= '<td>'.$this->html(PATH . '/product/' . $prod['alias']).'</td>';
            $rows .= '<td>'.$this->html($this->paramsText((int)$prod['id'])).'</td>';
            $rows .= '</tr>';
        }

        $html = '<!doctype html>
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
                    h1 { font-size: 16px; margin: 0 0 12px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #999; padding: 5px; vertical-align: top; }
                    th { background: #eee; }
                </style>
            </head>
            <body>
                <h1>Экспорт товаров</h1>
                <table>
                    <thead>
                        <tr>
                            <th>ID (артикул)</th>
                            <th>Номенклатура</th>
                            <th>Наличие</th>
                            <th>'.$this->html($this->priceTitle($priceType)).'</th>
                            <th>Ссылка на товар</th>
                            <th>Параметры</th>
                        </tr>
                    </thead>
                    <tbody>'.$rows.'</tbody>
                </table>
            </body>
            </html>';

        $fontCache = CACHE . '/dompdf';
        $this->ensureDirectory($fontCache);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('fontDir', ROOT . '/vendor/dompdf/dompdf/lib/fonts');
        $options->set('fontCache', $fontCache);
        $options->set('tempDir', $fontCache);
        $options->set('chroot', [ROOT, WWW, ROOT . '/vendor/dompdf/dompdf/lib/fonts']);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
        file_put_contents($filePath, $dompdf->output());

        return $this->fileResult($filePath, 'xls/' . $fileName, PATH . '/xls/' . $fileName, 'pdf');
    }

    protected function productParams($productId){
        return \R::getAll("SELECT attribute.attribute_name, product_attribute.attribute_text
            FROM attribute
            JOIN product_attribute ON product_attribute.attribute_id = attribute.id
            WHERE product_attribute.product_id = ?
            ORDER BY attribute.attribute_name", [$productId]);
    }

    protected function paramsText($productId){
        $params = [];
        foreach($this->productParams($productId) as $param) {
            if((string)$param['attribute_text'] !== '') {
                $params[] = $param['attribute_name'] . ': ' . $param['attribute_text'];
            }
        }
        return implode('; ', $params);
    }

    protected function categoriesForProducts($products){
        $categoryIds = [];
        foreach($products as $prod) {
            if(!empty($prod['category_id'])) {
                $categoryIds[] = (int)$prod['category_id'];
            }
        }
        $categoryIds = array_values(array_unique($categoryIds));

        if(!$categoryIds) {
            return [];
        }

        $categories = \R::getAll("SELECT id, name, parent_id FROM category WHERE id IN (".implode(',', array_fill(0, count($categoryIds), '?')).") ORDER BY parent_id, name", $categoryIds);
        $parentIds = [];
        foreach($categories as $category) {
            if((int)$category['parent_id'] > 0) {
                $parentIds[] = (int)$category['parent_id'];
            }
        }

        if($parentIds) {
            $parentIds = array_values(array_unique($parentIds));
            $parents = \R::getAll("SELECT id, name, parent_id FROM category WHERE id IN (".implode(',', array_fill(0, count($parentIds), '?')).") ORDER BY parent_id, name", $parentIds);
            $categories = array_merge($parents, $categories);
        }

        $unique = [];
        foreach($categories as $category) {
            $unique[(int)$category['id']] = $category;
        }

        return array_values($unique);
    }

    protected function priceValue($prod, $priceType){
        return $priceType == 'opt' ? $prod['opt_price'] : $prod['price'];
    }

    protected function priceTitle($priceType){
        return $priceType == 'opt' ? 'Оптовая цена' : 'Розничная цена';
    }

    protected function fileName($extension){
        return 'export-products-' . date('Ymd-His') . '.' . $extension;
    }

    protected function fileResult($filePath, $relativePath, $url, $ext){
        return [
            'path' => $filePath,
            'relative_path' => $relativePath,
            'file_name' => basename($relativePath),
            'url' => $url,
            'ext' => $ext,
            'size' => is_file($filePath) ? filesize($filePath) : 0,
        ];
    }

    protected function saveHistory($file, $format, $priceType, $productsCount, $data, &$error = ''){
        try {
            \R::exec(
                "INSERT INTO export_history
                    (file_name, file_path, format, format_title, file_ext, file_size, products_count, articles, created_at, user_id)
                 VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $file['file_name'],
                    $file['relative_path'],
                    $format,
                    $this->formatTitle($format) . ' / ' . $this->priceTitle($priceType),
                    $file['ext'],
                    (int)$file['size'],
                    (int)$productsCount,
                    ($data['actSelect'] ?? '') == '3' ? implode(',', $this->parseArticles($data['article'] ?? '')) : '',
                    date('Y-m-d H:i:s'),
                    isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0,
                ]
            );
        } catch(\Exception $e) {
            $error = $e->getMessage();
            return false;
        }

        return true;
    }

    protected function formatTitle($format){
        $formats = [
            'xls' => 'XLS',
            'csv' => 'CSV',
            'xml' => 'YML/XML',
            'pdf' => 'PDF',
        ];
        return $formats[$format] ?? $format;
    }

    protected function absoluteExportPath($path){
        $path = str_replace('\\', '/', trim((string)$path, '/'));
        if(!preg_match('#^(xls|xml|csv)/[a-zA-Z0-9._-]+$#', $path)) {
            return null;
        }
        return WWW . '/' . $path;
    }

    protected function ensureDirectory($path){
        if(!is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }

    protected function xml($value){
        return htmlspecialchars((string)$value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    protected function html($value){
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
