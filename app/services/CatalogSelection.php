<?php
namespace app\services;

/** Shared, whitelisted listing state. Status flags remain editorial choices. */
final class CatalogSelection
{
    public const STATUSES = [''=>'Все товары','hit'=>'Хиты','new'=>'Новинки','sale'=>'Распродажа','discount'=>'Со скидкой'];
    public const SORTS = ['stock'=>'Сначала в наличии','price_asc'=>'Цена: по возрастанию','price_desc'=>'Цена: по убыванию','rating'=>'Сначала с высоким рейтингом','popular'=>'По популярности','newest'=>'Сначала новые','discount'=>'Сначала с большей скидкой','name'=>'По названию'];
    public static function state(array $query): array
    {
        $sort=is_string($query['sort']??null)?$query['sort']:'stock';
        $sort=['price'=>'price_asc','nal'=>'stock','rate'=>'rating'][$sort]??$sort;
        $status=is_string($query['status']??null)?$query['status']:'';
        $perpage=(int)(is_scalar($query['perpage']??null)?($query['perpage']??24):24);
        return ['sort'=>isset(self::SORTS[$sort])?$sort:'stock','status'=>isset(self::STATUSES[$status])?$status:'','in_stock'=>($query['in_stock']??'')==='1','perpage'=>in_array($perpage,[24,48,96],true)?$perpage:24];
    }
    public static function stock(): string
    {
        return 'GREATEST(0,COALESCE(product.quantity,0)+COALESCE((SELECT SUM(m.quantity) FROM modification m WHERE m.product_id=product.id),0)-COALESCE(product.reserve,0))';
    }
    public static function price(): string
    {
        // Same active promotion and rounding rules as the shared product card.
        $promotional="GREATEST(0,COALESCE((SELECT CASE WHEN a.type_id=1 THEN ROUND(product.price*(1-a.znachenie/100),-1) WHEN a.type_id=2 THEN product.price-a.znachenie ELSE product.price END FROM actions a WHERE a.product_id=product.id AND a.hide='show' AND a.date_end>NOW() ORDER BY a.id LIMIT 1),product.price))";
        return "CASE WHEN EXISTS(SELECT 1 FROM modification mm WHERE mm.product_id=product.id) THEN GREATEST(product.price,COALESCE((SELECT MAX(mp.price) FROM modification mp WHERE mp.product_id=product.id),product.price)) ELSE $promotional END";
    }
    public static function discount(): string
    {
        $price=self::price();
        return "CASE WHEN EXISTS(SELECT 1 FROM modification md WHERE md.product_id=product.id) THEN 0 WHEN product.price>0 AND ($price)<product.price THEN 100*(product.price-($price))/product.price WHEN product.price>0 AND product.price_rrs>product.price THEN 100*(product.price_rrs-product.price)/product.price_rrs ELSE 0 END";
    }
    public static function where(array $state): string
    {
        $where=['hit'=>"AND product.hit='1'",'new'=>"AND product.new_product='1'",'sale'=>"AND product.sale='1'",'discount'=>'AND ('.self::discount().')>0'][$state['status']]??'';
        if($state['in_stock'])$where.=' AND ('.self::stock().')>0';
        return $where;
    }
    public static function order(array $state): string
    {
        $price=self::price();$stock=self::stock();
        $rating='COALESCE((SELECT AVG(rv.point) FROM review_product rp JOIN review rv ON rv.id=rp.review_id WHERE rp.product_id=product.id),0)';
        $order=['stock'=>"$stock DESC",'price_asc'=>"(($price)>0) DESC, ($price) ASC",'price_desc'=>"(($price)>0) DESC, ($price) DESC",'rating'=>"$rating DESC",'popular'=>'(SELECT COUNT(DISTINCT op.order_id) FROM order_product op WHERE op.product_id=product.id) DESC','newest'=>"product.new_product DESC, product.id DESC",'discount'=>'('.self::discount().') DESC','name'=>'product.name ASC'][$state['sort']];
        return 'ORDER BY '.$order.', product.id DESC';
    }
}
