<?php 

use ishop\App;
$shop_name = \ishop\App::$app->getProperty('shop_name');
$shop_description = \ishop\App::$app->getProperty('shop_description');
$date = date("Y-m-d H:m");
$date_update = date("Y-m-d H:i");
$viewcrons = \R::findOne('cron', 'id = ?', [$_GET["id"]]);
$fd = fopen("cron/".$viewcrons["url_download"]."", 'w+') or die("не удалось создать файл");
    $text = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
        <rss xmlns:yandex=\"http://news.yandex.ru\"
        xmlns:media=\"http://search.yahoo.com/mrss/\"
        xmlns:turbo=\"http://turbo.yandex.ru\"
        version=\"2.0\">
        <channel>
        <title>".$shop_name."</title>
        <link>".PATH."</link>
        <description>".$shop_description."</description>
        <language>ru</language>";
		
		$query = \R::getAll("SELECT contents.*, content_type.param_url, content_type.name as type_name FROM content_type, contents WHERE contents.type_id = content_type.id AND contents.hide = 'show' AND content_type.hide_rss = 'show'");		

		foreach($query as $item) {

		  $datas = date("D, d M Y G:i:s +0300", strtotime($item['date_post']));
		  $text.= "<item turbo=\"true\">
                <link>".PATH."/".$item["param_url"]."/".$item['alias']."</link>
            <turbo:source></turbo:source>
            <turbo:topic></turbo:topic>
            <pubDate>".$datas."</pubDate>
            <author></author>
            <metrics>
                <yandex schema_identifier=\"".$item['id']."\">
                    <breadcrumblist>
                        <breadcrumb url=\"".PATH."\" text=\"Главная\"/>
                        <breadcrumb url=\"".PATH."/".$item["param_url"]."\" text=\"".$item['type_name']."\"/>
                        <breadcrumb url=\"".PATH."/".$item["param_url"]."/".$item['alias']."\" text=\"".$item['name']."\"/>
                    </breadcrumblist>
                </yandex>
            </metrics>
            <yandex:related></yandex:related>
            <turbo:content>
                <![CDATA[
					<header>
						<h1>".$item['name']."</h1>						
					</header>
					<figure>";
					if($item['img'] !="") {
                        $text.= "<img alt=\"".$item['name']."\" src=\"".PATH."/images/contents/baseimg/".$item['img']."\">";
					}else{
						$text.= "<img alt=\"".$item['name']."\" src=\"".PATH."/images/no_image.jpg\">";
					}
                    $text.= "</figure>
                    ".$item['content']."
                ]]>
            </turbo:content>
        </item>";
		
		}
		$text.= "</channel>
</rss>";
fwrite($fd, $text);
fclose($fd);

$xcol = array_key_last($updt);
if($xcol <= $cnt ){ 
	\R::exec("UPDATE cron SET date_update = '".$date_update."' WHERE id = '".$_GET["id"]."'");
	if($_SESSION['user']['id']) { \R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','49','cron','".$_GET["id"]."','".date('Y-m-d H:i:s')."','".$_SESSION['user']['id']."')"); }
	else { \R::exec("INSERT INTO `admin_last_history`(`gh_id`, `ah_id`, `name_tbl`, `id_tbl`, `date_modified`, `customer_id`) VALUES ('2','51','cron','".$_GET["id"]."','".date('Y-m-d H:i:s')."','NULL')");  }
}
$_SESSION['success'] = 'Задание "'.$viewcrons["name"].'" выполнено!';
redirect("".PATH."/admin/cron");

?>