<?php
namespace app\helpers;

class UploadPresets
{
    public static function for(string $section, array $sizes): array
    {
        switch ($section) {
            case 'product':
                return [
                    'original_dir' => 'images/product/original',
                    'single' => [
                        ['dir'=>'images/product/baseimg', 'w'=>$sizes['w'],    'h'=>$sizes['h'],    'name'=>'{basename}.{ext}'],
                        ['dir'=>'images/product/mini',    'w'=>$sizes['wmini'],'h'=>$sizes['hmini'],'name'=>'{basename}.{ext}'],
                    ],
                    'multi'  => [
                        ['dir'=>'images/product/gallery', 'w'=>$sizes['w'],    'h'=>$sizes['h'],    'name'=>'{basename}.{ext}'],
                    ],
                    // важно: unload — JPEG вне зависимости от целевого формата проекта
                    'unload' => [
                        ['dir'=>'images/product/unload',  'w'=>$sizes['w'],    'h'=>$sizes['h'],    'name'=>'{basename}.jpg', 'fmt'=>'jpg'],
                    ],
                ];

            case 'complete':
                return [
                    'original_dir' => 'images/complete/original',
                    'single' => [
                        ['dir'=>'images/complete/baseimg','w'=>$sizes['w'],    'h'=>$sizes['h'],    'name'=>'{basename}.{ext}'],
                        ['dir'=>'images/complete/mini',   'w'=>$sizes['wmini'],'h'=>$sizes['hmini'],'name'=>'{basename}.{ext}'],
                    ],
                    'multi'  => [
                        ['dir'=>'images/complete/gallery','w'=>$sizes['w'],    'h'=>$sizes['h'],    'name'=>'{basename}.{ext}'],
                    ],
                ];

            case 'review':
                return [
                    'original_dir' => 'images/review/original',
                    'multi' => [
                        ['dir'=>'images/review/gallery','w'=>$sizes['w'],    'h'=>$sizes['h'],    'name'=>'{basename}.{ext}'],
                        ['dir'=>'images/review/mini',   'w'=>$sizes['wmini'],'h'=>$sizes['hmini'],'name'=>'{basename}.{ext}'],
                    ],
                ];

            case 'technics':
                return [
                    'original_dir' => 'images/technics/original',
                    'single' => [
                        ['dir'=>'images/technics/baseimg','w'=>$sizes['w'],    'h'=>$sizes['h'],    'name'=>'{basename}.{ext}'],
                        ['dir'=>'images/technics/mini',   'w'=>$sizes['wmini'],'h'=>$sizes['hmini'],'name'=>'{basename}.{ext}'],
                    ],
                ];

            case 'technics_type':
                return [
                    'original_dir' => 'images/technics_type/original',
                    'single' => [
                        ['dir'=>'images/technics_type/baseimg','w'=>$sizes['w'],'h'=>$sizes['h'],'name'=>'{basename}.{ext}'],
                    ],
                ];

            case 'technics_brand':
                return [
                    'original_dir' => 'images/technics_manufacturer/original',
                    'single' => [
                        ['dir'=>'images/technics_manufacturer/baseimg','w'=>$sizes['w'],'h'=>$sizes['h'],'name'=>'{basename}.{ext}'],
                    ],
                ];

            case 'category':
                return [
                    'original_dir' => 'images/category/original',
                    'single' => [
                        ['dir'=>'images/category/baseimg','w'=>$sizes['w'],'h'=>$sizes['h'],'name'=>'{basename}.{ext}'],
                    ],
                ];

            case 'brand':
                return [
                    'original_dir' => 'images/brand/original',
                    'single' => [
                        ['dir'=>'images/brand/baseimg','w'=>$sizes['w'],'h'=>$sizes['h'],'name'=>'{basename}.{ext}'],
                    ],
                ];

            case 'content':
                return [
                    'original_dir' => 'images/contents/original',
                    'single' => [
                        ['dir'=>'images/contents/baseimg','w'=>$sizes['w'],    'h'=>$sizes['h'],    'name'=>'{basename}.{ext}'],
                        ['dir'=>'images/contents/mini',   'w'=>$sizes['wmini'],'h'=>$sizes['hmini'],'name'=>'{basename}.{ext}'],
                    ],
                ];

            default:
                return ['original_dir'=>'images/original'];
        }
    }
}
