<?php
namespace app\models\admin;

use app\models\AppModel;

class PlaginsBanner extends AppModel {
    public $attributes = [
        'name'         => '',
        'alias'        => '',
        'description'  => '',
        'content'      => '',
        'img'          => '',
        'img2'         => '',
        'link_url'     => '',
        'target_blank' => 1,
        'btn_text'     => 'Подробнее',
        'btn_color'    => 'btn-danger',
        'hide'         => 'show',
        'position'     => 0,
        'start_at'     => null,
        'end_at'       => null,
    ];

    public $rules = [
        'required' => ['name','link_url'],
        'lengthMax' => [
            ['name',255], ['alias',255], ['description',255], ['link_url',255], ['btn_text',64], ['btn_color',32]
        ],
        'integer' => ['position','target_blank'],
        'in' => [
            ['hide', ['show','hide','lock']]
        ],
    ];

    public static function activeForFront()
    {
        $now = date('Y-m-d H:i:s');
        return \R::getAll("
            SELECT * FROM plagins_banner
            WHERE hide='show'
              AND (start_at IS NULL OR start_at <= ?)
              AND (end_at   IS NULL OR end_at   >= ?)
            ORDER BY position DESC, id DESC
        ", [$now, $now]);
    }
}
