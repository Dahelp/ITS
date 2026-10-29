<?php
namespace app\widgets\complete;

// Adapt complete data for the same rendering template used by product cards.
class Complete
{
    public function __construct($complete, $curr, $tpl = '')
    {
        $this->run($complete, $curr);
    }

    protected function run($complete, $curr)
    {
        $items = \R::getAll("SELECT cp.qty, cp.price, cp.discount, p.quantity, p.reserve,
            COALESCE((SELECT SUM(m.quantity) FROM modification m WHERE m.product_id = p.id), 0) AS modification_quantity
            FROM plagins_complete_product cp JOIN product p ON p.id = cp.product_id WHERE cp.complete_id = ?", [(int)$complete['id']]);
        $price = $discount = $pieces = 0;
        foreach ($items as $item) {
            $price += (float)$item['price'] * (int)$item['qty'];
            $discount += (float)$item['discount'] * (int)$item['qty'];
            $pieces += (int)$item['qty'];
        }
        $inventory=\app\services\CompleteInventory::forComplete((int)$complete['id']);$quantity=max(0,(int)$inventory['available']);$stockText=$quantity>0?'В наличии: '.$quantity.' компл.':'Нет в наличии';
        $categoryName = htmlspecialchars((string)\R::getCell('SELECT name FROM category WHERE id = ?', [(int)$complete['category_id']]), ENT_QUOTES, 'UTF-8');
        $seoName = (string)$complete['name'];
        $cardUrl = htmlspecialchars(PATH . '/complete/' . (string)$complete['alias'], ENT_QUOTES, 'UTF-8');
        $cardImageUrl = htmlspecialchars(PATH . '/images/complete/mini/' . basename((string)$complete['img']), ENT_QUOTES, 'UTF-8');
        $final = max(0, $price - $discount);
        $cross = $discount > 0 ? $price : null;
        $companyTip = null;
        $modification = [];
        $hasDiscountBadge = $discount > 0;
        $productHit = !empty($complete['hit']);
        $productNew = !empty($complete['new_product']);
        $isCompleteCard = true;
        $curr = array_merge(['symbol_left' => '', 'symbol_right' => '₽', 'value' => 1], $curr ?? []);
        require dirname(__DIR__) . '/product/card.php';
    }
}
