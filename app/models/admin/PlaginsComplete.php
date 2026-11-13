<?php

namespace app\models\admin;

use app\models\AppModel;

class PlaginsComplete extends AppModel{

    public $attributes = [
        'name' => '',
        'category_id' => '',
		'content' => '',
		'title' => '',
        'description' => '',		
        'keywords' => '',
		'alias' => '',
		'hide' => 'show',
    ];

    public $rules = [
        'required' => [
            ['name'],            
        ],        
    ];

    public function load($data){
        parent::load($data);
        $this->attributes['category_id'] = max(0, (int)$this->attributes['category_id']);
        if (!in_array($this->attributes['hide'], ['show', 'hide', 'lock'], true)) {
            $this->attributes['hide'] = 'show';
        }
    }

    public function save($table, $valid = true){
        $this->attributes['img'] = $this->attributes['img'] ?? '';
        return parent::save($table, false);
    }

    public function validate($data){
        if (!parent::validate($data)) { return false; }
        try {
            self::normalizeItems($data['complete'] ?? []);
            \app\services\CompleteAvito::saveLinks((int)($data['id'] ?? $_GET['id'] ?? 0), (string)($data['avito_ids'] ?? ''), true);
        } catch (\InvalidArgumentException $e) {
            $this->errors['complete'][] = $e->getMessage();
            return false;
        }
        return true;
    }

	public function checkUnique(){
        $attribute = \R::findOne('plagins_complete', 'name = ?', [$this->attributes['name']]);
        if($attribute){
            if($attribute->name == $this->attributes['name']){
                $this->errors['unique'][] = 'Это название комплекта уже существует';
            }
            return false;
        }
        return true;
    }

	public function editProductComplete($id, $data){
        $rows = self::normalizeItems($data['complete'] ?? []);
        \R::begin();
        try {
            \R::exec('DELETE FROM plagins_complete_product WHERE complete_id = ?', [$id]);
            foreach ($rows as $row) {
                \R::exec('INSERT INTO plagins_complete_product (complete_id, product_id, qty, price, discount, discount_amount) VALUES (?, ?, ?, ?, ?, ?)',
                    [$id, $row['product_id'], $row['qty'], $row['price'], $row['discount'], $row['discount'] * $row['qty']]);
            }
            \R::commit();
        } catch (\Throwable $e) {
            \R::rollback();
            throw $e;
        }
    }

    public static function normalizeItems(array $items): array {
        $rows = [];
        foreach ($items as $item) {
            $pid = (int)($item['product_id'] ?? 0);
            if ($pid <= 0) { continue; }
            $product = \R::getRow('SELECT price FROM product WHERE id = ?', [$pid]);
            if (!$product) { throw new \InvalidArgumentException('Товар комплекта не найден'); }
            $qty = max(1, (int)($item['quantity'] ?? 1));
            $price = ($item['price'] ?? '') === '' ? (float)$product['price'] : max(0, (float)$item['price']);
            $discount = min($price, max(0, (float)($item['discount'] ?? 0)));
            if (isset($rows[$pid])) { $rows[$pid]['qty'] += $qty; continue; }
            $rows[$pid] = ['product_id' => $pid, 'qty' => $qty, 'price' => $price, 'discount' => $discount];
        }
        return array_values($rows);
    }

    public function getImg(){
        if(!empty($_SESSION['single'])){
            $this->attributes['img'] = $_SESSION['single'];
            unset($_SESSION['single']);
        }
    }

    public function saveGallery($id){
        if(!empty($_SESSION['multi'])){
            $sql_part = '';
            foreach($_SESSION['multi'] as $v){
                $sql_part .= "('$v', $id),";
            }
            $sql_part = rtrim($sql_part, ',');
            foreach ($_SESSION['multi'] as $img) {
                \R::exec('INSERT INTO plagins_complete_gallery (complete_id, img) VALUES (?, ?)', [$id, $img]);
            }
            unset($_SESSION['multi']);
        }
    }
	
	public function uploadImg($name, $wmax, $hmax, $wmaxmini, $hmaxmini){		
        $ext = strtolower(preg_replace("#.+\.([a-z]+)$#i", "$1", $_FILES[$name]['name'])); // расширение картинки
        $types = array("image/gif", "image/png", "image/jpeg", "image/pjpeg", "image/x-png"); // массив допустимых расширений
		$size = \R::findOne('options', 'alt_name = ?', ['option_size_product']);
		$size_product = $size->znachenie * 1048576;
        if($_FILES[$name]['size'] > $size_product){
            $res = array("error" => "Ошибка! Максимальный вес файла - ".$size->znachenie." Мб!");
            exit(json_encode($res));
        }
        if($_FILES[$name]['error']){
            $res = array("error" => "Ошибка! Возможно, файл слишком большой.");
            exit(json_encode($res));
        }
        if(!in_array($_FILES[$name]['type'], $types)){
            $res = array("error" => "Допустимые расширения - .gif, .jpg, .png");
            exit(json_encode($res));
        }
        $new_name = bin2hex(random_bytes(16)).".$ext";
        foreach (['tmp', 'baseimg', 'gallery', 'mini'] as $dir) {
            if (!is_dir(WWW.'/images/complete/'.$dir)) { mkdir(WWW.'/images/complete/'.$dir, 0775, true); }
        }
		
		$tmpdir = WWW . '/images/complete/tmp/'.$new_name.'';
        $basedir = WWW . '/images/complete/baseimg/'.$new_name.'';
		$galdir = WWW . '/images/complete/gallery/'.$new_name.'';
		$minidir = WWW . '/images/complete/mini/'.$new_name.'';
		
		if($name == 'single'){
			if(@move_uploaded_file($_FILES[$name]['tmp_name'], $tmpdir)){
            
                $_SESSION['single'] = $new_name;
            
				self::resize($tmpdir, $basedir, $wmax, $hmax, $ext);
				self::resize($tmpdir, $minidir, $wmaxmini, $hmaxmini, $ext);
				@unlink($tmpdir);
				$res = array("file" => $new_name);
				exit(json_encode($res));
			}
		}else{
			if(@move_uploaded_file($_FILES[$name]['tmp_name'], $tmpdir)){
                $_SESSION['multi'][] = $new_name;
				
				self::resize($tmpdir, $galdir, $wmax, $hmax, $ext);
				$res = array("file" => $new_name);
				exit(json_encode($res));
			}
        }
    }
}
