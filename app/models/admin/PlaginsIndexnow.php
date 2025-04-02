<?php

namespace app\models\admin;

use app\models\AppModel;
use Guzzlehttp\Guzzle;

class PlaginsIndexnow extends AppModel{
	
	public $attributes = [
		'search_engine' => '',
		'url' => '',
        'verification' => '',
        'hide' => '',
	
    ];

    public $rules = [
        'required' => [
            ['search_engine'],
            ['url'],
            ['verification'],
        ],
    ];

	public function indexNowEngine($url, $controller, $alias, $verification){        

			$client = new \GuzzleHttp\Client();
			$response = $client->request('GET', 'https://'.$url.'/indexnow?url='.PATH.'/'.$controller.'/'.$alias.'&key='.$verification.'');
			if($response->getStatusCode() == "200") { $status_code = "OK"; }else{ $status_code = $response->getBody(); }
			$search_engine = "<br>".$url." IndexNow: ".$status_code."";
		
        return $search_engine;
    }
	
	public function checkUnique(){
        $indexnow = \R::findOne('plagins_indexnow', 'search_engine = ?', [$this->attributes['search_engine']]);
        if($indexnow){
            if($indexnow->search_engine == $this->attributes['search_engine']){
                $this->errors['unique'][] = 'Поисковая система уже существует';
            }
            return false;
        }
        return true;
    }
	
}