<?php

$userId    = (int)($_SESSION['user']['id'] ?? 0);
$compusers = $userId ? \R::findOne('company', 'user_id = ?', [$userId]) : null;

// валюта/итоги можно НЕ считать тут вообще — всё синхронизирует JS из cart_table
$curL = (string)($_SESSION['cart.currency']['symbol_left'] ?? '');
$curR = (string)($_SESSION['cart.currency']['symbol_right'] ?? '');
?>

<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">
        <!--start-breadcrumbs-->
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class='fas fa-home'></i></a><span class="visually-hidden">Главная</span></li>
                <li class="breadcrumb-item active">Корзина</li>
            </ol>
		</nav>
    </div>
</div>
<!--end-breadcrumbs-->
<!--prdt-starts-->
<div class="prdt">
    <div class="container">
		<div class="col-md-12 cart-block" <?php if(!empty($_SESSION['cart'])){ ?>style="display:block"<?php }else{ ?>style="display:none"<?php } ?>>
			<form method="post" action="<?= PATH ?>/cart/checkout" role="form" enctype="multipart/form-data" id="checkoutForm">
				<div class="checkout-steps mb-3">
					<div class="d-flex gap-2 flex-wrap">
						<span class="checkout-step is-active" data-step-badge="1">1. Корзина</span>
						<span class="checkout-step" data-step-badge="2">2. Получение</span>
						<span class="checkout-step" data-step-badge="3">3. Контакты</span>
					</div>
				</div>
				<div class="product-cart">
					<div id="cartContent">

						<!-- STEP 1 -->
						<section id="step1_cart" data-step="1">

						<div class="checkout-grid">
							<!-- LEFT -->
							<div class="checkout-main">

							<div class="register-top heading">
								<h2>Корзина</h2>
							</div>

							<?php if (!empty($_SESSION['cart.notice'])): ?>
								<div class="alert alert-warning" style="margin:10px 0;">
								<?= htmlspecialchars((string)$_SESSION['cart.notice'], ENT_QUOTES, 'UTF-8'); ?>
								</div>
								<?php unset($_SESSION['cart.notice']); ?>
							<?php endif; ?>

							<!-- сюда будет подмена HTML при +/- -->
							<div id="cartTableWrap" class="bg-light rounded-3 py-4 px-4">
								<?php require __DIR__ . '/cart_table.php'; ?>
							</div>

							</div>

							<!-- RIGHT -->
							<aside class="checkout-side">
							<div class="checkout-sticky">

								<div class="bg-light rounded-3 py-4 px-4">

								<button type="button" class="btn btn-success w-100 btn-checkout" id="btnToStep2">
									Перейти к оформлению
								</button>

								<div class="small text-muted mt-2">
									Доступные способы и условия доставки можно выбрать при оформлении заказа
								</div>

								<hr>

								<div class="register-top heading">
									<h2>Ваш заказ</h2>
								</div>

								<div class="d-flex justify-content-between align-items-center">
									<span class="text-muted">Товары (<span class="js-qty">0</span>)</span>
									<b class="js-subtotal"><?= htmlspecialchars($curL . '0' . $curR, ENT_QUOTES, 'UTF-8') ?></b>
								</div>

								<div class="d-flex d-none justify-content-between align-items-center mt-2 js-discount-row" style="display:none">
									<span class="text-muted">Скидка</span>
									<b class="js-discount" style="color:#ef4444;"></b>
								</div>

								<div class="d-flex justify-content-between align-items-center mt-2">
									<span class="text-muted">Вес, кг:</span>
									<b class="js-weight">0</b>
								</div>

								<div class="d-flex justify-content-between align-items-center mt-2">
									<span class="text-muted">Объём, м3:</span>
									<b class="js-volume">0</b>
								</div>

								<hr>

								<div class="d-flex justify-content-between align-items-center" style="font-size:18px;">
									<span><b>Итого</b></span>
									<b class="js-total"><?= htmlspecialchars($curL . '0' . $curR, ENT_QUOTES, 'UTF-8') ?></b>
								</div>

								</div>

							</div>
							</aside>

						</div>
						</section>



						<!-- STEP 2 -->
						<section id="step2_delivery" data-step="2" style="display:none">

						<div class="checkout-grid">

							<!-- LEFT -->
							<div class="checkout-main">
							<div class="register-top heading">
								<h2>Получение</h2>
							</div>

							<div class="bg-light rounded-3 py-4 px-4">

								<div class="register-top heading">
								<h2>Способы получения</h2>
								</div>

								<input type="hidden" name="checkout_token" value="<?= htmlspecialchars($_SESSION['checkout_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
								<div style="position:absolute; left:-9999px; top:-9999px;">
								<label>Ваш сайт
									<input type="text" name="hp_field" autocomplete="off">
								</label>
								</div>

								<div class="mb-3">
								<label class="form-label" for="dostavka_id">Способ получения <span class="text-danger">*</span></label>
								<select class="form-control" name="dostavka_id" id="dostavka_id" data-step-field="2">
									<option value="">Выберите способ получения</option>
									<?php
									$dostavka = \R::getAll("SELECT * FROM dostavka WHERE hide='show'");
									foreach($dostavka as $ds){
									$idv = (string)($ds["id"] ?? '');
									$namev = (string)($ds["name"] ?? '');
									echo '<option value="'.htmlspecialchars($idv, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($namev, ENT_QUOTES, 'UTF-8').'</option>';
									}
									?>
								</select>
								</div>

								<div class="mb-3 notauth" id="another_transport" style="display:none">
								<label class="form-label" for="transport_id">Транспортная компания <span class="text-danger">*</span></label>
								<select class="form-control" name="transport_id" id="transport_id" data-step-field="2">
									<option value="">Выберите транспортную компанию</option>
									<option value="">-------------------------</option>
									<?php
									$transport = \R::getAll("SELECT * FROM transport_company WHERE hide='show'");
									foreach($transport as $tr){
										$idv = (string)($tr["id"] ?? '');
										$namev = (string)($tr["name"] ?? '');
										echo '<option value="'.htmlspecialchars($idv, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($namev, ENT_QUOTES, 'UTF-8').'</option>';
									}
									?>
								</select>
								</div>

								<div class="mb-3 notauth" id="another_sklad" style="display:none">
								<label class="form-label" for="branch_id">Пункт самовывоза <span class="text-danger">*</span></label>
								<select class="form-control" name="branch_id" id="branch_id" data-step-field="2">
									<option value="">Выберите место самовывоза</option>
									<?php
									$branch = \R::getAll("SELECT * FROM branch_office WHERE hide='show'");
									foreach($branch as $br){
										$bid = (string)($br["branch_id"] ?? '');
										$bname = (string)($br["branch_name"] ?? '');
										echo '<option value="'.htmlspecialchars($bid, ENT_QUOTES, 'UTF-8').'">г. '.htmlspecialchars($bname, ENT_QUOTES, 'UTF-8').'</option>';
									}
									?>
								</select>
								</div>

								<div class="mb-3 zakaz-inpt" id="another_city" style="display:none">
								<label class="form-label" for="city_name">Город доставки <span class="text-danger">*</span></label>
								<input type="text" class="form-control" name="city_name" id="city_name"
										placeholder="Укажите город" data-step-field="2">
								<div class="invalid-feedback">Не указан город доставки</div>
								</div>

								<div class="mb-0" id="another_adress" style="display:none">
								<label class="form-label" for="address">Адрес доставки</label>
								<input type="text" name="address" class="form-control" id="address"
										placeholder="Адрес доставки товаров" data-step-field="2">
								</div>

							</div>

							<!-- READONLY ITEMS (STEP 2) -->
							<div class="bg-light rounded-3 py-4 px-4 mt-3" id="step2Items">
								<div class="d-flex justify-content-between align-items-center mb-3">
									<div class="register-top heading mb-0">
									<h2 style="margin:0;">Товары в заказе</h2>
									</div>
									<div class="text-muted small">
									<span class="js-qty">0</span> шт.
									</div>
								</div>

								<div id="orderItemsWrap">
									<?php require __DIR__ . '/cart_items_readonly.php'; ?>
								</div>
							</div>

							<div class="d-flex justify-content-between mt-3">
								<button type="button" class="btn btn-outline-secondary" id="btnBackToStep1">Назад</button>       
							</div>
							</div>

							<!-- RIGHT -->
							<aside class="checkout-side">
							<div class="checkout-sticky">

								<!-- summary тот же -->
								<div class="bg-light rounded-3 py-4 px-4">

									<button type="button" class="btn btn-primary w-100 btn-checkout" id="btnToStep3">
										Заполнить контакты
									</button>

									<div class="small text-muted mt-2">
										На следующем шаге укажете контактные данные — подтвердим заказ и согласуем получение.
									</div>

									<hr>

									<div class="register-top heading">
										<h2>Ваш заказ</h2>
									</div>

									<div class="d-flex justify-content-between align-items-center">
										<span class="text-muted">Товары (<span class="js-qty">0</span>)</span>
										<b class="js-subtotal"><?= htmlspecialchars($curL . '0' . $curR, ENT_QUOTES, 'UTF-8') ?></b>
									</div>

									<div class="d-flex d-none justify-content-between align-items-center mt-2 js-discount-row" style="display:none">
										<span class="text-muted">Скидка</span>
										<b class="js-discount" style="color:#ef4444;"></b>
									</div>

									<div class="d-flex justify-content-between align-items-center mt-2">
										<span class="text-muted">Вес, кг:</span>
										<b class="js-weight">0</b>
									</div>

									<div class="d-flex justify-content-between align-items-center mt-2">
										<span class="text-muted">Объём, м3:</span>
										<b class="js-volume">0</b>
									</div>

									<hr>

									<div class="d-flex justify-content-between align-items-center" style="font-size:18px;">
										<span><b>Итого</b></span>
										<b class="js-total"><?= htmlspecialchars($curL . '0' . $curR, ENT_QUOTES, 'UTF-8') ?></b>
									</div>

									</div>


								<!-- промокод ниже -->
								<div class="bg-light rounded-3 py-4 px-4 mt-3" id="promoBlock">
									<div class="fw-semibold mb-2">Промокод</div>

									<?php $promoApplied = trim((string)($_SESSION['promocart'] ?? '')); ?>

									<div class="input-group">
										<input
										type="text"
										class="form-control"
										id="promoCodeInput"
										placeholder="Введите промокод"
										value="<?= htmlspecialchars($promoApplied, ENT_QUOTES, 'UTF-8') ?>"
										<?= $promoApplied !== '' ? 'readonly' : '' ?>
										>

										<button
										class="btn btn-outline-secondary"
										type="button"
										id="btnApplyPromo"
										<?= $promoApplied !== '' ? 'disabled' : '' ?>
										>
										Применить
										</button>

										<button
										class="btn btn-outline-danger"
										type="button"
										id="btnClearPromo"
										style="<?= $promoApplied !== '' ? '' : 'display:none' ?>"
										>
										Сбросить
										</button>
									</div>

									<div class="small text-muted mt-2">
										Промокод не суммируется со скидкой на комплект.
									</div>

									<div
										class="small mt-2"
										id="promoMsg"
										style="<?= $promoApplied !== '' ? '' : 'display:none' ?>; color:#16a34a;"
									>
										✅ Промокод применён
									</div>
								</div>

							</div>
							</aside>

						</div>
						</section>

						<!-- STEP 3 -->
<section id="step3_contacts" data-step="3" style="display:none">

  <div class="checkout-grid">

    <!-- LEFT -->
    <div class="checkout-main">

      <!-- ✅ ТВОЙ БЛОК №3: "Информация для связи" -->
      <div class="bg-light rounded-3 py-5 px-4 px-xxl-5">
        <div class="register-top heading">
          <h2>Информация для связи</h2>
        </div>

        <div class="row gx-4 gy-3">

          <?php if (!$compusers): ?>
            <div class="col-sm-6">
              <label class="form-label" for="groups">Вид <span class="text-danger">*</span></label>
              <select name="groups" class="form-control" id="vidurlface" onchange="val()" data-step-field="3">
                <option value="" selected="selected">Выберите вид клиента</option>
                <option value="3">Физическое лицо</option>
                <option value="4">Юридическое лицо</option>
              </select>
            </div>

            <div id="vid_urlface" style="display:none">
              <div class="col-sm-12 rekvizity row">
                <div class="col-sm-5">
                  <label class="form-label" for="rekvizity">Прикрепить реквизиты <span class="text-danger">*</span></label>
                  <input class="btn btn-default" type="file" name="rekvizity" data-step-field="3">
                </div>
              </div>

              <div class="col-sm-6">
                <label class="form-label" for="nds">Система налогообложения <span class="text-danger">*</span></label>
                <select name="nds" class="form-control" data-step-field="3">
                  <option value="" selected="selected">Выберите систему налогообложения</option>
                  <option value="1">с НДС</option>
                  <option value="2">без НДС</option>
                </select>
              </div>

              <p></p>

              <div class="col-sm-6">
                <label class="form-label" for="dogovor">Условия поставки <span class="text-danger">*</span></label>
                <select name="dogovor" class="form-control" data-step-field="3">
                  <option value="" selected="selected">Выберите условия поставки</option>
                  <option value="1">Договор</option>
                  <option value="2">Счёт-договор</option>
                </select>
              </div>
            </div>

            <p></p>
          <?php endif; ?>

          <?php if(!isset($_SESSION['user'])): ?>
            <div class="col-sm-6">
              <label class="form-label" for="name">ФИО <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" id="name"
                     placeholder="ФИО необходимо для отправки заказов"
                     value="<?= htmlspecialchars((string)($_SESSION['form_data']['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                     data-step-field="3">
            </div>

            <div class="col-sm-6">
              <label class="form-label" for="telefon">Телефон <span class="text-danger">*</span></label>
              <input type="text" name="telefon" class="form-control" id="phone-input" data-step-field="3">
            </div>

            <div class="col-sm-6">
              <label class="form-label" for="email">Электронная почта <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" id="email" placeholder="Электронная почта"
                     value="<?= htmlspecialchars((string)($_SESSION['form_data']['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                     data-step-field="3">
            </div>
          <?php endif; ?>

		  <div class="col-sm-6">
			<label class="form-label" for="form_callback">
				Форма обратной связи <span class="text-danger">*</span>
			</label>

			<select class="form-control"
					id="form_callback"
					name="form_callback"
					data-step-field="3"
					required>
				<option value="">Выберите способ обратной связи</option>
				<?php $cb = (string)($_SESSION['form_data']['form_callback'] ?? ''); ?>

				<option value="MAX" <?= $cb==='MAX'?'selected':'' ?>>MAX</option>
				<option value="E-mail" <?= $cb==='E-mail'?'selected':'' ?>>Электронная почта</option>
				<option value="Обратный звонок" <?= $cb==='Обратный звонок'?'selected':'' ?>>Обратный звонок</option>
			</select>

			<div class="invalid-feedback">Выберите способ обратной связи</div>
			</div>

          <div class="col-sm-12">
            <label class="form-label" for="note">Комментарий</label>
            <textarea name="note" class="form-control"></textarea>
          </div>

          <div class="col-sm-12">
            <div class="form-check" style="margin-top:8px;">
              <input class="form-check-input" type="checkbox" id="checkout_legal_agree" name="checkout_legal_agree" value="1" required data-step-field="3">
              <label class="form-check-label small text-muted" for="checkout_legal_agree">
                Я принимаю
                <a href="/pages/terms" target="_blank" rel="noopener">условия продажи</a>,
                <a href="/pages/privacy" target="_blank" rel="noopener">Политику конфиденциальности</a>
                и даю
                <a href="/pages/personal-data-consent" target="_blank" rel="noopener">согласие на обработку персональных данных</a>.
              </label>
              <div class="invalid-feedback">Нужно подтвердить согласие.</div>
            </div>
          </div>

        </div>
      </div>

      <!-- ✅ READONLY: выбранная доставка / получение -->
		<div class="bg-light rounded-3 py-4 px-4 mt-3" id="deliveryReadonlyBlock" style="display:none">
		<div class="fw-semibold mb-2">Получение</div>

		<div class="small text-muted mb-1">Способ:</div>
		<div class="fw-semibold js-delivery-method">—</div>

		<div class="mt-2 js-delivery-details small text-muted"></div>
		</div>

      <!-- ✅ READONLY: товары (как на Step2) -->
      <div class="bg-light rounded-3 py-4 px-4 mt-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fw-semibold">Товары в заказе</div>
          <div class="text-muted small"><span class="js-qty">0</span> шт.</div>
        </div>

        <?php require __DIR__ . '/cart_items_readonly.php'; ?>
      </div>

      <div class="d-flex justify-content-between mt-3">
        <button type="button" class="btn btn-outline-secondary" id="btnBackToStep2">Назад</button>
      </div>

    </div>

    <!-- RIGHT -->
    <aside class="checkout-side">
      <div class="checkout-sticky">

        <div class="bg-light rounded-3 py-4 px-4">

          <!-- ✅ кнопка наверху правого блока -->
          <button class="btn btn-success w-100 btn-checkout" id="btnSubmitOrder" type="submit">
            Подтвердить заказ
          </button>

          <div class="small text-muted mt-2">
            Проверим данные и подтвердим заказ. При необходимости уточним условия получения.
          </div>

          <hr>

          <div class="register-top heading">
            <h2>Ваш заказ</h2>
          </div>

          <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted">Товары (<span class="js-qty">0</span>)</span>
            <b class="js-subtotal"><?= htmlspecialchars($curL . '0' . $curR, ENT_QUOTES, 'UTF-8') ?></b>
          </div>

          <div class="d-flex d-none justify-content-between align-items-center mt-2 js-discount-row" aria-hidden="true">
            <span class="text-muted">Скидка</span>
            <b class="js-discount" style="color:#ef4444;"></b>
          </div>

          <div class="d-flex justify-content-between align-items-center mt-2">
            <span class="text-muted">Вес, кг:</span>
            <b class="js-weight">0</b>
          </div>

          <div class="d-flex justify-content-between align-items-center mt-2">
            <span class="text-muted">Объём, м3:</span>
            <b class="js-volume">0</b>
          </div>

          <hr>

          <div class="d-flex justify-content-between align-items-center" style="font-size:18px;">
            <span><b>Итого</b></span>
            <b class="js-total"><?= htmlspecialchars($curL . '0' . $curR, ENT_QUOTES, 'UTF-8') ?></b>
          </div>

        </div>

      </div>
    </aside>

  </div>
</section>

					</div>
				</div>				
				
			</form>
        	<?php if(isset($_SESSION['form_data'])) unset($_SESSION['form_data']); ?>                   
		</div>
		<div class="col-md-12 cart-no-product text-center" <?php if(!empty($_SESSION['cart'])){ ?>style="display:none"<?php }else{ ?>style="display:block"<?php } ?>>
			<div class="cart-no-title">В корзине нет товаров</div>
			<div class="cart-no-info">Найдите то, что вам нужно в каталоге или при помощи поиска</div>
			<div class="cart-no-button">
				<a class="btn btn-outline-primary" href="/">Вернуться к покупкам</a>
			</div>
		</div> 
    </div>
</div>
<!--product-end-->
