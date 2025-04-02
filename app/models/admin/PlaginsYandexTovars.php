<?php

namespace app\models\admin;

use app\models\AppModel;
use Guzzlehttp\Guzzle;

class PlaginsYandexTovars extends AppModel{
	
	public function yandexTovars(){        

			$client = new \GuzzleHttp\Client();
			$response = $client->request('GET', 'https://yandex.ru/products/api/ext/partner/feeds-info', [
			'headers' => [
				'Authorization' => 'OAuth y0_AgAAAAAr0Z7TAArz7QAAAAD0EIQj39f-QWVnSFmwc0MBhUg_dh8E1a4',
				'Accept' => 'application/json',
			],
			]
		);

		$content = $response->getBody();
		return $parsed = json_decode($content, true);
    }	
}