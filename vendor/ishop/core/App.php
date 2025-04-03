<?php

namespace ishop;

use NumberFormatter;
use Guzzlehttp\Guzzle;

class App{

    public static $app;

    public function __construct(){
        $query = trim($_SERVER['QUERY_STRING'] ?? '', '/');		
        session_start();
        self::$app = Registry::instance();
        $this->getParams();
        new ErrorHandler();
        Router::dispatch($query);
    }

    protected function getParams(){
        $params = require_once CONF . '/params.php';
        if(!empty($params)){
            foreach($params as $k => $v){
                self::$app->setProperty($k, $v);
            }
        }
    }
	
	public static function contdate($date_post) {
		
		list($currentDate, $currentOclock) = explode(' ', $date_post);
		list($y, $m, $d) = explode('-', $currentDate);
		$_monthsList = array(
		  "01" => "января",
		  "02" => "февраля",
		  "03" => "марта",
		  "04" => "апреля",
		  "05" => "мая",
		  "06" => "июня",
		  "07" => "июля",
		  "08" => "августа",
		  "09" => "сентября",
		  "10" => "октября",
		  "11" => "ноября",
		  "12" => "декабря"
		);		 

		$currentDate = ''.$d.' '.$_monthsList[$m].' '.$y.'';
		return $currentDate;
	}
	
	public static function abbreviateddate($date_post) {
		
		list($y, $m, $d) = explode('-', $date_post);
		$_monthsList = array(
		  "01" => "янв",
		  "02" => "фев",
		  "03" => "мар",
		  "04" => "апр",
		  "05" => "мая",
		  "06" => "июн",
		  "07" => "июл",
		  "08" => "авг",
		  "09" => "сен",
		  "10" => "окт",
		  "11" => "ноя",
		  "12" => "дек"
		);		 

		$currentDate = ''.$d.' '.$_monthsList[$m].' '.$y.'';
		return $currentDate;
	}
	
	public static function contdatetime($date_post) {
		
		list($currentDate, $currentOclock) = explode(' ', $date_post);
		list($y, $m, $d) = explode('-', $currentDate);
		list($h, $i, $s) = explode(':', $currentOclock);
		$_monthsList = array(
		  "01" => "января",
		  "02" => "февраля",
		  "03" => "марта",
		  "04" => "апреля",
		  "05" => "мая",
		  "06" => "июня",
		  "07" => "июля",
		  "08" => "августа",
		  "09" => "сентября",
		  "10" => "октября",
		  "11" => "ноября",
		  "12" => "декабря"
		);		 

		$currentDate = ''.$d.' '.$_monthsList[$m].' '.$y.' '.$h.':'.$i.'';
		return $currentDate;
	}
	
	public static function getPeriod($date1,$date2){
		$date1 = date_create_from_format('Y-m-d', $date1);
		$date2 = date_create_from_format('Y-m-d', $date2);
		$interval = date_diff($date1, $date2);
		$y='';$m='';$d='';

		if ($interval->y>0) {
			if ($interval->y>4) 
				$y .=$interval->y . ' лет'; 
			else if ($interval->y == 1)
				$y .=$interval->y . ' год'; 
			else 
				$y .=$interval->y . ' года';
			$y .= ', ';
		}

		if ($interval->m>0) {
			if ($interval->m>4) 
				$m .= $interval->m . ' месяцев';
			else if ($interval->m>1)
				$m .= $interval->m . ' месяца';
			else 
				$m .= $interval->m . ' месяц';
			$m .= ', ';
		}

		if ($interval->d>0) {
			if ($interval->d>4) 
				$d .= $interval->d . ' дней';
			else if ($interval->d>1)
				$d .= $interval->d . ' дня';
			else
				$d .= $interval->d . ' день';
			
		}
		else { $d .= ' сегодня'; }
		
		return $y . $m . $d;
		
	}
	
	public static function getPeriodMailbox($date1,$date2){
		$date1 = date_create_from_format('Y-m-d H:i:s', $date1);
		$date2 = date_create_from_format('Y-m-d H:i:s', $date2);
		$interval = date_diff($date1, $date2);
		$d='';
		return $interval->d;		
	}
	
	public static function options($alt_name) {
	
		$options = \R::getAll("SELECT znachenie FROM options WHERE alt_name = ?", [$alt_name]);
		foreach($options as $option){
			$znachenie = $option["znachenie"];
		}	
		return $znachenie;
	
	}
	
	//номер счёта
	public static function invoice_num($input, $pad_len = 7, $prefix = null) {
		if ($pad_len <= strlen($input))
		trigger_error('<strong>$pad_len</strong> не может быть меньше или равна длине <strong>$input</strong> для генерации номера счета', E_USER_ERROR);

		if (is_string($prefix))
		return sprintf("%s%s", $prefix, str_pad($input, $pad_len, "0", STR_PAD_LEFT));

		return str_pad($input, $pad_len, "0", STR_PAD_LEFT);
	}
	
	// Форматирование цен.
	public static function format_price($value)
	{
		return number_format($value, 2, ',', ' ');
	}
 
	// Сумма прописью.
	public static function str_price($value)
	{
		$value = explode('.', number_format($value, 2, '.', ''));

		$f = new NumberFormatter('ru', NumberFormatter::SPELLOUT);
		//$f = numfmt_create( 'ru', NumberFormatter::SPELLOUT );
		
		$str = $f->format($value[0]);
	 
		// Первую букву в верхний регистр.
		$str = mb_strtoupper(mb_substr($str, 0, 1)) . mb_substr($str, 1, mb_strlen($str));
	 
		// Склонение слова "рубль".
		$num = $value[0] % 100;
		if ($num > 19) { 
			$num = $num % 10; 
		}	
		switch ($num) {
			case 1: $rub = 'рубль'; break;
			case 2: 
			case 3: 
			case 4: $rub = 'рубля'; break;
			default: $rub = 'рублей';
		}	
		
		return $str . ' ' . $rub . ' ' . $value[1] . ' копеек.';
	}
	
	public static function seoreplace($text, $id){
		
		$data = explode(PHP_EOL, $text);
		
		foreach($data as $restext) {
		if(preg_match_all('|{(.*)}|Uis', $restext, $result))
		{
			foreach($result[1] as $urlsys) {					
				$url_attribute = \R::getCell("SELECT product_attribute.attribute_text FROM attribute, product_attribute WHERE product_attribute.attribute_id = attribute.id AND product_attribute.product_id = '".$id."' AND attribute.url_params = '".$urlsys."'");
				if($url_attribute !="") {
					$text = preg_replace('!{'.$urlsys.'}!', ''.$url_attribute.'', $text);
					
				}
				else{
					$text = str_replace(''.$restext.'', '', $text);					
				}
				
			}	
							
		}			
			
		}	
		$text = str_replace(array("\r","\n", "  "), " ",$text);
		$text = str_replace("  ", " ",$text);
		$text = trim($text);
		return $text;
	}
	
	public static function seoreplacefilter($text, $id){
		
		$data = explode(PHP_EOL, $text);
		
		foreach($data as $restext) {
		if(preg_match_all('|{(.*)}|Uis', $restext, $result))
		{
			foreach($result[1] as $urlsys) {					
				$url_attribute = \R::getCell("SELECT attribute_value.value FROM attribute_group, attribute_value WHERE attribute_value.attr_group_id = attribute_group.id AND attribute_value.id = '".$id."' AND attribute_group.url_params = '".$urlsys."'");
				if($url_attribute !="") {
					$text = preg_replace('!{'.$urlsys.'}!', ''.$url_attribute.'', $text);
					
				}
				else{
					$text = str_replace(''.$restext.'', '', $text);					
				}
				
			}	
							
		}			
			
		}	
		$text = str_replace(array("\r","\n", "  "), " ",$text);
		$text = str_replace("  ", " ",$text);
		$text = trim($text);
		return $text;
	}
	
	public static function seoreplacetiposize($text, $values){
		
		$data = explode(PHP_EOL, $text);
		
		foreach($data as $restext) {
		if(preg_match_all('|{(.*)}|Uis', $restext, $result))
		{
			foreach($result[1] as $urlsys) {					
				$url_attribute = \R::getCell("SELECT product_attribute.attribute_text FROM `product_attribute`, `attribute` WHERE attribute.id = product_attribute.attribute_id AND attribute.url_params = '".$urlsys."' AND product_attribute.product_id IN (".$values.")");
				if($url_attribute !="") {
					$text = preg_replace('!{'.$urlsys.'}!', ''.$url_attribute.'', $text);
					
				}
				else{
					$text = str_replace(''.$restext.'', '', $text);					
				}
				
			}	
							
		}			
			
		}	
		$text = str_replace(array("\r","\n", "  "), " ",$text);
		$text = str_replace("  ", " ",$text);
		$text = trim($text);
		return $text;
	}
	
	public static function on_line() {
		 $wine = 300; // секунды
		 global $REMOTE_ADDR;

		\R::exec("DELETE FROM user_online WHERE unix+".$wine." < ".time()." OR user_id = '".$_SESSION['user']['id']."'");
		\R::exec("INSERT INTO user_online (ip, user_id, unix) VALUES ('".$_SERVER['REMOTE_ADDR']."', '".$_SESSION['user']['id']."', '".time()."')");		
	}
	
	public static function styleCss() {	
		$options = \R::getAll("SELECT znachenie FROM options WHERE tip = 'Оформление'");
		foreach($options as $option){
			$znachenie = $option["znachenie"];
		}	
		return $znachenie;
	}
	
	public static function upFirstLetter($str, $encoding = 'UTF-8')
	{
		return mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding)
		. mb_substr($str, 1, null, $encoding);
	}
	
	public static function downFirstLetter($str, $encoding = 'UTF-8')
	{
		return mb_strtolower(mb_substr($str, 0, 1, $encoding), $encoding)
		. mb_substr($str, 1, null, $encoding);
	}
	
	public static function generate_password($number)
	{
		$arr = array('a','b','c','d','e','f', 'g','h','i','j','k','l', 'm','n','o','p','r','s', 't','u','v','x','y','z', 'A','B','C','D','E','F', 'G','H','I','J','K','L', 'M','N','O','P','R','S', 'T','U','V','X','Y','Z', '1','2','3','4','5','6', '7','8','9','0');

		$pass = "";
		for($i = 0; $i < $number; $i++)
		{
			$index = rand(0, count($arr) - 1);
			$pass .= $arr[$index];
		}
		return $pass;
	}
	
	public static function upRegistrLetter($alias) {
		 if (preg_match('/(?=.*[A-Z])(?=.*\D)/', $alias)) {
			header('HTTP/1.1 404 Not Found', true, 404);
			include("errors/404.php"); 

			exit();
		}		
	}
	
}