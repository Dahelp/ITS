<?php

namespace app\models;

use ishop\App;

class Cart extends AppModel {
	
	public $rules = [
        'required' => [            
            ['name'],
            ['email'],
			['telefon'],
        ],
        'email' => [
            ['email'],
        ],
		'telefon' => [
            ['telefon'],
        ]
    ];

    public function addToCart($product, $qty = 1, $max = null, $mod = null)
	{
		if (!isset($_SESSION['cart.currency'])) {
			$_SESSION['cart.currency'] = App::$app->getProperty('currency');
		}

		$qty = (int)$qty;
		if ($qty < 1) $qty = 1;

		// max: если не пришёл — считаем "без лимита"
		$max = ($max === null) ? PHP_INT_MAX : (int)$max;
		if ($max < 1) $max = PHP_INT_MAX;

		// --- MOD / base product ---
		if (!empty($mod)) {
			$ID     = "{$product->id}-{$mod->id}";
			$name   = "{$product->name} ({$mod->name_modification})";
			$price  = (float)$mod->price;
			$article = $mod->article;
			$unit   = $mod->unit;
			$weight = $product->weight;
			$volume = $product->volume;
		} else {
			$ID   = (string)$product->id;
			$name = $product->name;

			$date = date("Y-m-d H:i:s");
			$action = \R::findOne('actions', "product_id = ? AND hide = 'show' AND date_end > '".$date."'", [$product->id]);

			if ($action) {
				if ($action['type_id'] == "1") {
					$skidka = $product['price'] - ($product['price'] / 100 * $action['znachenie']);
					$skidka = explode('.', (string)$skidka);
					$skidka = $skidka[0] ?? $skidka;
					$price  = round((float)$skidka, -1);
				} elseif ($action['type_id'] == "2") {
					$price = (float)$product['price'] - (float)$action['znachenie'];
				} else {
					$price = (float)$product->price;
				}
			} else {
				$price = (float)$product->price;
			}

			$article = $product->article;
			$unit    = $product->unit;
			$weight  = $product->weight;
			$volume  = $product->volume;
		}

		// --- COMPANY tip / typeprice (без warning) ---
		$userId = (int)($_SESSION['user']['id'] ?? 0);

		$cprice = ($userId > 0)
			? \R::getRow('SELECT tip FROM company WHERE user_id = ?', [$userId])
			: [];

		$uprice = ($userId > 0)
			? \R::getRow(
				'SELECT company.tip, company_typeprice.znachenie
				FROM company, company_typeprice
				WHERE company.id = company_typeprice.company_id
				AND company.user_id = ?
				AND company_typeprice.category_id = ?',
				[$userId, (int)$product->category_id]
			)
			: [];

		$companyTip = (int)($cprice['tip'] ?? 0); // <- ключ tip может отсутствовать
		$znachenie  = $uprice['znachenie'] ?? '';

		if ($companyTip === 2) {
			if ($znachenie === '' || $znachenie === null) {
				$price = (float)$product->opt_price;
			} else {
				// оставляю твою формулу как есть, только привожу типы
				$zn = (float)$znachenie;
				$price_nds = round((float)$product->price - ((float)$product->price / 1.2), 0) * 6;
				$price_opt = $price_nds - (($price_nds / 100) * $zn);
				$price     = ceil($price_opt / 6) * 6;
			}
		}

		// --- write to cart ---
		$priceInCurrency = (float)$price * (float)($_SESSION['cart.currency']['value'] ?? 1);
		$priceInCurrency = (float)$priceInCurrency;

		// ✅ если промокод уже активен — применяем к добавляемому товару (только если нет акции)
		// (акцию мы тут точно не проверяем второй раз — просто применим, а на промо-apply всё равно всё выровняется)
		if (!empty($_SESSION['promocart'])) {
			$priceInCurrency = $priceInCurrency * 0.98;
		}

		if (isset($_SESSION['cart'][$ID])) {
			$_SESSION['cart'][$ID]['qty'] += $qty;
		} else {
			$_SESSION['cart'][$ID] = [
				'qty'    => $qty,
				'unit'   => $unit,
				'weight' => $weight,
				'volume' => $volume,
				'max'    => $max,
				'name'   => $name,
				'article'=> $article,
				'alias'  => $product->alias,
				'price'  => $priceInCurrency,
				'img'    => $product->img,
				'product_id' => (int)$product->id,
				'base_price' => (float)$priceInCurrency, // база для clearpromo (в рамках текущего состояния)

			];
		}

		$_SESSION['cart.qty']    = isset($_SESSION['cart.qty']) ? $_SESSION['cart.qty'] + $qty : $qty;
		$_SESSION['cart.sum']    = isset($_SESSION['cart.sum']) ? $_SESSION['cart.sum'] + $qty * $priceInCurrency : $qty * $priceInCurrency;
		$_SESSION['cart.weight'] = isset($_SESSION['cart.weight']) ? $_SESSION['cart.weight'] + $qty * (float)$weight : $qty * (float)$weight;
		$_SESSION['cart.volume'] = isset($_SESSION['cart.volume']) ? $_SESSION['cart.volume'] + $qty * (float)$volume : $qty * (float)$volume;
	}

	private function makeCompleteRowKey(int $completeId, int $productId, $mod = null): string
	{
		$key = 's' . $completeId . '_' . $productId;
		if (!empty($mod) && !empty($mod->id)) {
			$key .= '-' . (int)$mod->id;
		}
		return $key;
	}

	public function addToCartComplete($product, $qty, $itemsPerComplete, $mod = null, $completeId = 0, $withCompleteDiscount = false)
	{
		if (!isset($_SESSION['cart.currency'])) {
			$_SESSION['cart.currency'] = App::$app->getProperty('currency');
		}

		$qty = (int)$qty;
		if ($qty < 1) $qty = 1;

		$completeId = (int)$completeId;
		$itemsPerComplete = max(1, (int)$itemsPerComplete);
		$curValue = (float)($_SESSION['cart.currency']['value'] ?? 1);

		if (!empty($mod)) {
			$rowKey  = $this->makeCompleteRowKey($completeId, (int)$product->id, $mod);
			$name    = "{$product->name} ({$mod->name_modification})";
			$article = $mod->article;
			$unit    = $mod->unit;
			$weight  = (float)($product->weight ?? 0);
			$volume  = (float)($product->volume ?? 0);
		} else {
			$rowKey  = $this->makeCompleteRowKey($completeId, (int)$product->id);
			$name    = $product->name;
			$article = $product->article;
			$unit    = $product->unit;
			$weight  = (float)($product->weight ?? 0);
			$volume  = (float)($product->volume ?? 0);
		}

		$basePrice = (float)($product->price ?? 0) * $curValue;

		$priceComplete = isset($product->price_complete)
			? (float)$product->price_complete * $curValue
			: $basePrice;

		$priceDiscount = isset($product->price_discount)
			? (float)$product->price_discount * $curValue
			: 0.0;

		$max = (int)($product->quantity ?? PHP_INT_MAX);
		if ($max < 1) $max = PHP_INT_MAX;

		if (isset($_SESSION['cart'][$rowKey])) {
			$_SESSION['cart'][$rowKey]['qty'] += $qty;
		} else {
			$_SESSION['cart'][$rowKey] = [
				'qty' => $qty,
				'unit' => $unit,
				'weight' => $weight,
				'volume' => $volume,
				'max' => $max,
				'name' => $name,
				'article' => $article,
				'alias' => $product->alias,
				'img' => $product->img,

				'product_id' => (int)$product->id,

				'price' => $withCompleteDiscount ? max(0, $priceComplete - $priceDiscount) : $basePrice,
				'base_price' => $basePrice,
				'price_complete' => $priceComplete,
				'price_discount' => $priceDiscount,

				'set' => (string)$completeId,
				'complete_required_qty' => $itemsPerComplete,
				'complete_discount_active' => $withCompleteDiscount ? 1 : 0,
				'allow_promo' => $withCompleteDiscount ? 0 : 1,
			];
		}

		$this->refreshCompleteState($completeId);
	}

	private function reapplyPromocodeAfterCartChange(): void
{
    $code = trim((string)($_SESSION['promocart'] ?? ''));
    if ($code === '') {
        return;
    }

    $factor = 0.98;
    $dateNow = date("Y-m-d H:i:s");

    foreach ($_SESSION['cart'] as $k => $item) {
        // сначала сбрасываем следы промокода
        $_SESSION['cart'][$k]['promo_applied'] = 0;
        $_SESSION['cart'][$k]['promo_discount'] = 0.0;

        $productId = (int)($_SESSION['cart'][$k]['product_id'] ?? $this->getProductIdFromKey((string)$k));
        if ($productId <= 0) {
            continue;
        }

        // базовая цена без промокода, но с текущей логикой корзины
        if (!empty($item['set']) && !empty($item['complete_discount_active'])) {
            $priceComplete = (float)($_SESSION['cart'][$k]['price_complete'] ?? 0);
            $priceDiscount = (float)($_SESSION['cart'][$k]['price_discount'] ?? 0);
            $_SESSION['cart'][$k]['price'] = max(0, $priceComplete - $priceDiscount);
            continue; // комплект со скидкой — промокод нельзя
        }

        $base = (float)($_SESSION['cart'][$k]['base_price'] ?? 0);
        if ($base <= 0) {
            $base = (float)($_SESSION['cart'][$k]['price'] ?? 0);
            $_SESSION['cart'][$k]['base_price'] = $base;
        }

        // сначала возвращаем базовую цену
        $_SESSION['cart'][$k]['price'] = $base;

        // акция — промокод нельзя
        $action = \R::findOne(
            'actions',
            "product_id = ? AND hide = 'show' AND date_end > ?",
            [$productId, $dateNow]
        );
        if ($action) {
            continue;
        }

        // если явно запрещён промокод — не применяем
        if (isset($item['allow_promo']) && (int)$item['allow_promo'] === 0) {
            continue;
        }

        // применяем промокод
        $new = ceil(($base * $factor) / 10) * 10;

        $_SESSION['cart'][$k]['price'] = $new;
        $_SESSION['cart'][$k]['promo_applied'] = 1;
        $_SESSION['cart'][$k]['promo_discount'] = max(0.0, $base - $new);
    }

    $this->recalcTotalsFromCart();
}

	public function refreshCompleteState($completeId): void
{
    $completeId = (int)$completeId;
    $set = (string)$completeId;

    if ($completeId <= 0 || empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $this->recalcTotalsFromCart();
        return;
    }

    // 1. Берём состав комплекта ИЗ БД
    $rows = \R::getAll(
        "SELECT product_id, qty
         FROM plagins_complete_product
         WHERE complete_id = ?",
        [$completeId]
    );

    if (empty($rows)) {
        $this->recalcTotalsFromCart();
        return;
    }

    $requiredMap = [];
    foreach ($rows as $row) {
        $pid = (int)($row['product_id'] ?? 0);
        $req = max(1, (int)($row['qty'] ?? 1));
        if ($pid > 0) {
            $requiredMap[$pid] = $req;
        }
    }

    if (empty($requiredMap)) {
        $this->recalcTotalsFromCart();
        return;
    }

    // 2. Собираем фактические qty по этому комплекту из корзины
    $cartSetRows = [];
    $cartQtyMap = [];

    foreach ($_SESSION['cart'] as $rowKey => $item) {
        if (empty($item['set']) || (string)$item['set'] !== $set) {
            continue;
        }

        $cartSetRows[$rowKey] = $item;

        $pid = (int)($item['product_id'] ?? 0);
        if ($pid <= 0) {
            continue;
        }

        $cartQtyMap[$pid] = ($cartQtyMap[$pid] ?? 0) + (int)($item['qty'] ?? 0);
    }

    if (empty($cartSetRows)) {
        $this->recalcTotalsFromCart();
        return;
    }

    // 3. Проверяем, собран ли комплект полностью
    $fullSet = true;

	foreach ($requiredMap as $pid => $requiredQty) {
		$actualQty = (int)($cartQtyMap[$pid] ?? 0);

		if ($actualQty < $requiredQty) {
			$fullSet = false;
			break;
		}
	}

	// Если в корзине вдруг есть лишние товары в этом set, которых нет в составе комплекта — считаем комплект некорректным
	foreach ($cartQtyMap as $pid => $actualQty) {
		if (!isset($requiredMap[$pid])) {
			$fullSet = false;
			break;
		}
	}

    // 4. Применяем цену к строкам комплекта
    foreach ($cartSetRows as $rowKey => $item) {
        $basePrice     = (float)($_SESSION['cart'][$rowKey]['base_price'] ?? 0);
        $priceComplete = (float)($_SESSION['cart'][$rowKey]['price_complete'] ?? $basePrice);
        $priceDiscount = (float)($_SESSION['cart'][$rowKey]['price_discount'] ?? 0);

        if ($fullSet && $priceDiscount > 0) {
            $_SESSION['cart'][$rowKey]['price'] = max(0, $priceComplete - $priceDiscount);
            $_SESSION['cart'][$rowKey]['complete_discount_active'] = 1;
            $_SESSION['cart'][$rowKey]['allow_promo'] = 0;
            $_SESSION['cart'][$rowKey]['promo_applied'] = 0;
            $_SESSION['cart'][$rowKey]['promo_discount'] = 0.0;
        } else {
            $_SESSION['cart'][$rowKey]['price'] = $basePrice;
            $_SESSION['cart'][$rowKey]['complete_discount_active'] = 0;
            $_SESSION['cart'][$rowKey]['allow_promo'] = 1;
        }
    }

    // 5. После пересчёта комплекта переоцениваем промокод по всей корзине
    if (!empty($_SESSION['promocart'])) {
        $this->reapplyPromocodeAfterCartChange();
    } else {
        $this->recalcTotalsFromCart();
    }
}

    public function deleteItem($id): void
	{
		$id = (string)$id;
		if (empty($_SESSION['cart'][$id])) return;

		$qty    = (int)($_SESSION['cart'][$id]['qty'] ?? 0);
		$price  = (float)($_SESSION['cart'][$id]['price'] ?? 0);
		$weight = (float)($_SESSION['cart'][$id]['weight'] ?? 0);
		$volume = (float)($_SESSION['cart'][$id]['volume'] ?? 0);

		$_SESSION['cart.qty']    = (int)($_SESSION['cart.qty'] ?? 0) - $qty;
		$_SESSION['cart.sum']    = (float)($_SESSION['cart.sum'] ?? 0) - ($qty * $price);
		$_SESSION['cart.weight'] = (float)($_SESSION['cart.weight'] ?? 0) - ($qty * $weight);
		$_SESSION['cart.volume'] = (float)($_SESSION['cart.volume'] ?? 0) - ($qty * $volume);

		unset($_SESSION['cart'][$id]);

		// ✅ clamp
		if (($_SESSION['cart.qty'] ?? 0) < 0) $_SESSION['cart.qty'] = 0;
		if (($_SESSION['cart.sum'] ?? 0) < 0) $_SESSION['cart.sum'] = 0;
		if (($_SESSION['cart.weight'] ?? 0) < 0) $_SESSION['cart.weight'] = 0;
		if (($_SESSION['cart.volume'] ?? 0) < 0) $_SESSION['cart.volume'] = 0;

		// ✅ если это был последний — чистим всё
		if (empty($_SESSION['cart'])) {
			$this->normalizeCartIfEmpty();
			return;
		}

		// ✅ если промокод активен — пересчитаем сумму заново, чтобы не было рассинхрона
		if (!empty($_SESSION['promocart'])) {
			$this->recalcTotalsFromCart();
		}
	}
	
	public function deletecompleteItem($id, $completeId)
	{
		if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) return;

		$id = (string)$id;
		$completeId = (int)$completeId;

		if (!isset($_SESSION['cart'][$id])) return;

		unset($_SESSION['cart'][$id]);
		$this->refreshCompleteState($completeId);
	}
	
	public function pluscartItem($id)
	{
		if (empty($_SESSION['cart'][$id])) return;

		$price  = (float)($_SESSION['cart'][$id]['price'] ?? 0);
		$weight = (float)($_SESSION['cart'][$id]['weight'] ?? 0);
		$volume = (float)($_SESSION['cart'][$id]['volume'] ?? 0);

		$_SESSION['cart.qty']    = (int)($_SESSION['cart.qty'] ?? 0) + 1;
		$_SESSION['cart.sum']    = (float)($_SESSION['cart.sum'] ?? 0) + $price;
		$_SESSION['cart.weight'] = (float)($_SESSION['cart.weight'] ?? 0) + $weight;
		$_SESSION['cart.volume'] = (float)($_SESSION['cart.volume'] ?? 0) + $volume;

		$_SESSION['cart'][$id]['qty'] = (int)($_SESSION['cart'][$id]['qty'] ?? 0) + 1;
	}
		
	public function promocartItem($val): array
{
    $code = trim((string)$val);

    if ($code === '') {
        unset($_SESSION['promocart'], $_SESSION['promo_state']);
        $_SESSION['promo_state'] = ['ok' => 0, 'msg' => 'Введите промокод'];
        return $_SESSION['promo_state'];
    }

    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        unset($_SESSION['promocart'], $_SESSION['promo_state']);
        $_SESSION['promo_state'] = ['ok' => 0, 'msg' => 'Корзина пуста'];
        return $_SESSION['promo_state'];
    }

    $promo = \R::getRow('SELECT * FROM plagins_promocode WHERE promocode = ?', [$code]);
    if (!$promo) {
        unset($_SESSION['promocart']);
        $_SESSION['promo_state'] = ['ok' => 0, 'msg' => '❌ Промокод не найден или недействителен'];
        $_SESSION['cart.notice'] = 'Промокод не найден или недействителен.';
        return $_SESSION['promo_state'];
    }

    if (!empty($_SESSION['promocart']) && (string)$_SESSION['promocart'] === $code) {
        $_SESSION['promo_state'] = ['ok' => 1, 'msg' => '✅ Промокод уже применён'];
        return $_SESSION['promo_state'];
    }

    // Сначала сбрасываем старый промокод
    $this->clearpromoItem(true);

    $factor = 0.98; // -2%
    $dateNow = date("Y-m-d H:i:s");

    $_SESSION['promocart'] = $code;
	$this->reapplyPromocodeAfterCartChange();

	$_SESSION['promo_state'] = ['ok' => 1, 'msg' => '✅ Промокод применён'];
	$_SESSION['cart.notice'] = 'Промокод применён только к товарам без скидки по акции и без скидки на комплект.';

	return $_SESSION['promo_state'];

}

	public function clearpromoItem(bool $silent = false): void
	{
		// сначала всегда убираем старые promo-сообщения
		unset($_SESSION['cart.notice']);

		if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
			unset($_SESSION['promocart'], $_SESSION['promo_state'], $_SESSION['cart.notice']);
			return;
		}

		foreach ($_SESSION['cart'] as $k => $item) {
			$base = (float)($_SESSION['cart'][$k]['base_price'] ?? 0);

			// комплект со скидкой на комплект
			if (!empty($item['set']) && !empty($item['complete_discount_active'])) {
				$priceComplete = (float)($_SESSION['cart'][$k]['price_complete'] ?? $base);
				$priceDiscount = (float)($_SESSION['cart'][$k]['price_discount'] ?? 0);
				$_SESSION['cart'][$k]['price'] = max(0, $priceComplete - $priceDiscount);
			} else {
				// для обычных товаров возвращаем базовую цену
				if ($base > 0) {
					$_SESSION['cart'][$k]['price'] = $base;
				}
			}

			$_SESSION['cart'][$k]['promo_applied'] = 0;
			$_SESSION['cart'][$k]['promo_discount'] = 0.0;
		}

		unset($_SESSION['promocart']);
		$_SESSION['promo_state'] = ['ok' => 0, 'msg' => ''];

		$this->recalcTotalsFromCart();

		if (!$silent) {
			$_SESSION['cart.notice'] = 'Промокод сброшен.';
		}
	}
	
	public function pluscartcompleteItem($id, $completeId)
	{
		if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) return;

		$id = (string)$id;
		$completeId = (int)$completeId;

		if (empty($_SESSION['cart'][$id])) return;

		$currentQty = (int)($_SESSION['cart'][$id]['qty'] ?? 0);
		$max = (int)($_SESSION['cart'][$id]['max'] ?? PHP_INT_MAX);

		if ($currentQty >= $max) return;

		$_SESSION['cart'][$id]['qty'] = $currentQty + 1;

		$this->refreshCompleteState($completeId);
	}

	public function minuscartItem($id)
	{
		if (empty($_SESSION['cart'][$id])) return;

		$price  = (float)($_SESSION['cart'][$id]['price'] ?? 0);
		$weight = (float)($_SESSION['cart'][$id]['weight'] ?? 0);
		$volume = (float)($_SESSION['cart'][$id]['volume'] ?? 0);

		$_SESSION['cart.qty']    = (int)($_SESSION['cart.qty'] ?? 0) - 1;
		$_SESSION['cart.sum']    = (float)($_SESSION['cart.sum'] ?? 0) - $price;
		$_SESSION['cart.weight'] = (float)($_SESSION['cart.weight'] ?? 0) - $weight;
		$_SESSION['cart.volume'] = (float)($_SESSION['cart.volume'] ?? 0) - $volume;

		$_SESSION['cart'][$id]['qty'] = (int)($_SESSION['cart'][$id]['qty'] ?? 0) - 1;
	}
	
	public function minuscartcompleteItem($id, $completeId)
	{
		if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) return;

		$id = (string)$id;
		$completeId = (int)$completeId;

		if (empty($_SESSION['cart'][$id])) return;

		$currentQty = (int)($_SESSION['cart'][$id]['qty'] ?? 0);

		if ($currentQty <= 1) {
			unset($_SESSION['cart'][$id]);
			$this->refreshCompleteState($completeId);
			return;
		}

		$_SESSION['cart'][$id]['qty'] = $currentQty - 1;
		$this->refreshCompleteState($completeId);
	}

private function recalcTotalsFromCart(): void
{
    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart.qty'] = 0;
        $_SESSION['cart.sum'] = 0;
        $_SESSION['cart.weight'] = 0;
        $_SESSION['cart.volume'] = 0;
        return;
    }

    $qty = 0;
    $sum = 0.0;
    $w   = 0.0;
    $v   = 0.0;

    foreach ($_SESSION['cart'] as $item) {
        $q = (int)($item['qty'] ?? 0);
        $qty += $q;

        $sum += (float)($item['price'] ?? 0) * $q;
        $w   += (float)($item['weight'] ?? 0) * $q;
        $v   += (float)($item['volume'] ?? 0) * $q;
    }

    $_SESSION['cart.qty'] = $qty;
    $_SESSION['cart.sum'] = $sum;
    $_SESSION['cart.weight'] = $w;
    $_SESSION['cart.volume'] = $v;
}
	
	private function normalizeCartIfEmpty(): void
	{
		if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
			unset($_SESSION['cart']);
			$_SESSION['cart.qty'] = 0;
			$_SESSION['cart.sum'] = 0;
			$_SESSION['cart.weight'] = 0;
			$_SESSION['cart.volume'] = 0;

			unset($_SESSION['promocart']);
			unset($_SESSION['promo_state']);
		}
	}

	/** Возвращает product_id из ключа корзины: "123" или "123-5" */
	private function getProductIdFromKey(string $key): int
	{
		if ($key === '') return 0;

		// ключ комплекта: s17_93 или s17_93-5
		if (preg_match('/^s\d+_(\d+)/', $key, $m)) {
			return (int)$m[1];
		}

		if (strpos($key, '-') !== false) {
			$parts = explode('-', $key, 2);
			return (int)$parts[0];
		}

		return (int)$key;
	}

    public static function recalc($curr)
	{
		if (!isset($_SESSION['cart.currency']) || empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) return;

		$oldValue = (float)($_SESSION['cart.currency']['value'] ?? 1);
		$newValue = (float)($curr->value ?? 1);
		$oldBase  = !empty($_SESSION['cart.currency']['base']);

		if ($oldBase) {
			$_SESSION['cart.sum'] = (float)($_SESSION['cart.sum'] ?? 0) * $newValue;
		} else {
			$_SESSION['cart.sum'] = (float)($_SESSION['cart.sum'] ?? 0) / $oldValue * $newValue;
		}

		foreach ($_SESSION['cart'] as $k => $v) {
			// price
			if ($oldBase) {
				$_SESSION['cart'][$k]['price'] = (float)($_SESSION['cart'][$k]['price'] ?? 0) * $newValue;
				if (isset($_SESSION['cart'][$k]['price_complete'])) {
					$_SESSION['cart'][$k]['price_complete'] = (float)$_SESSION['cart'][$k]['price_complete'] * $newValue;
				}
				if (isset($_SESSION['cart'][$k]['price_discount'])) {
					$_SESSION['cart'][$k]['price_discount'] = (float)$_SESSION['cart'][$k]['price_discount'] * $newValue;
				}
			} else {
				$_SESSION['cart'][$k]['price'] = (float)($_SESSION['cart'][$k]['price'] ?? 0) / $oldValue * $newValue;
				if (isset($_SESSION['cart'][$k]['price_complete'])) {
					$_SESSION['cart'][$k]['price_complete'] = (float)$_SESSION['cart'][$k]['price_complete'] / $oldValue * $newValue;
				}
				if (isset($_SESSION['cart'][$k]['price_discount'])) {
					$_SESSION['cart'][$k]['price_discount'] = (float)$_SESSION['cart'][$k]['price_discount'] / $oldValue * $newValue;
				}
			}
		}

		// обновляем валюту в сессии (явно, без foreach по объекту)
		$_SESSION['cart.currency']['value'] = $newValue;
		$_SESSION['cart.currency']['base']  = (int)($curr->base ?? 0);
		$_SESSION['cart.currency']['symbol_left']  = $curr->symbol_left ?? ($_SESSION['cart.currency']['symbol_left'] ?? '');
		$_SESSION['cart.currency']['symbol_right'] = $curr->symbol_right ?? ($_SESSION['cart.currency']['symbol_right'] ?? '');
		$_SESSION['cart.currency']['code']  = $curr->code ?? ($_SESSION['cart.currency']['code'] ?? '');
	}

}