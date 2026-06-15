<!-- panel bottom -->
<div class="aiz-mobile-bottom-nav d-xl-none fixed-bottom bg-white shadow-lg border-top rounded-top" style="box-shadow: 0px -1px 10px rgb(0 0 0 / 15%)!important; ">
    <div class="row align-items-center gutters-5">
        <div class="col">
            <a href="/" class="text-reset d-block text-center pb-2 pt-3">
                <i class="fas fa-home-alt fs-20 opacity-60 "></i>
                <span class="d-block fs-10 fw-600 opacity-60 ">Главная</span>
            </a>
        </div>
        <div class="col">
            <a href="catalog" class="text-reset d-block text-center pb-2 pt-3">
                <i class="fas fa-list fs-20 opacity-60 opacity-100 text-danger"></i>
                <span class="d-block fs-10 fw-600 opacity-60 opacity-100 fw-600">Категории</span>
            </a>
        </div>
        <div class="col-auto">
            <a href="cart/show" onclick="getCart(); return false;" class="text-reset d-block text-center pb-2 pt-3">
                <span class="align-items-center bg-danger border border-white border-width-4 d-flex justify-content-center position-relative rounded-circle size-50px" style="margin-top: -33px;box-shadow: 0px -5px 10px rgb(0 0 0 / 15%);border-color: #fff !important;">
                    <i class="fas fa-shopping-bag la-2x text-white"></i>
                </span>
                <span class="d-block mt-1 fs-10 fw-600 opacity-60 ">
                    <?php if(!empty($_SESSION['cart'])): ?>								
						Корзина (<span class="simpleCart_qty" id="cart-total"><?=$_SESSION['cart.qty']?></span>)						
					<?php else: ?>
						Корзина (<span class="simpleCart_qty" id="cart-total">0</span>)
					<?php endif; ?>
                </span>
            </a>
        </div>
        <div class="col">
            <a href="user/notifications" class="text-reset d-block text-center pb-2 pt-3">
                <span class="d-inline-block position-relative px-2">
                    <i class="fas fa-bell fs-20 opacity-60 "></i>
                                    </span>
                <span class="d-block fs-10 fw-600 opacity-60 ">Сообщения</span>
            </a>
        </div>
        <div class="col">
			<?php if(!empty($_SESSION['user']['id'])): ?>
				<a href="javascript:void(0)" class="text-reset d-block text-center pb-2 pt-3 mobile-side-nav-thumb" data-toggle="class-toggle" data-backdrop="static" data-target=".aiz-mobile-side-nav">
			<?php else: ?>
				<a data-toggle="modal" data-target="#Modallogin" href="javascript:;" class="text-reset d-block text-center pb-2 pt-3">		
			<?php endif; ?>
                <span class="d-block mx-auto">
                    <img src="images/avatar-place.png" class="rounded-circle size-20px">
                </span>
                <span class="d-block fs-10 fw-600 opacity-60">Кабинет</span>
            </a>
        </div>
    </div>
</div>
<!-- /panel bottom -->