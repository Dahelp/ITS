<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\CatalogSeoAudit as S;
function seoAssert($ok,$msg){if(!$ok)throw new RuntimeException($msg);}
$p=['name'=>'Fallback name','seo_h1'=>'','title'=>'','description'=>'','content'=>''];
$t=[['id'=>4,'name'=>'Шина {size}','title'=>'Купить {size}','description'=>'Описание {size}','content'=>'<p>Размер {size}</p>']];
$a=S::assess($p,$t,['size'=>'6.50-10']);seoAssert($a['fields']['title']['value']==='Купить 6.50-10'&&!$a['issues'],'Template rendering');
$a=S::assess($p,$t,[]);seoAssert(in_array('effective_title',$a['issues'])&&$a['fields']['h1']['empty'],'Missing variable removes whole line, no invented fallback');
$a=S::assess(array_replace($p,['seo_h1'=>'Мой {size}','title'=>'Свой {size}','description'=>'Своё','content'=>'<p>Текст</p>']),$t,['size'=>'12-16.5']);seoAssert($a['fields']['h1']['value']==='Мой 12-16.5'&&$a['fields']['title']['value']==='Свой {size}'&&$a['fields']['title']['source']==='product','Own metadata literal; H1 rendered');
$a=S::assess($p,[],[]);seoAssert($a['fields']['h1']['source']==='name'&&$a['fields']['h1']['value']==='Fallback name','H1 name fallback');
$a=S::assess($p,array_merge($t,$t),['size'=>'6.50-10']);seoAssert($a['ambiguous']&&in_array('seo_template_conflict',$a['issues']),'Ambiguous active templates flagged');
$a=S::assess(array_replace($p,['content'=>'<p>&nbsp;</p>']),[],[]);seoAssert($a['fields']['content']['empty'],'HTML whitespace empty');
echo "Effective SEO: shared renderer, missing variables, own field precedence, H1 fallback, duplicate templates and HTML whitespace OK\n";
