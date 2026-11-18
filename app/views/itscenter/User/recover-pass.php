<!--prdt-starts-->
<div class="prdt">
    <div class="container">
		<!--start-breadcrumbs-->
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active">Создание нового пароля</li>
            </ol>
		</nav>
		<!--end-breadcrumbs-->
		<section class="align-items-center">
            <h1 class="h2 mb-3 mb-md-0 me-3">Создание нового пароля</h1>			
        </section>
			<?php if(!empty($_GET["key"])) { ?>
			<div class="prdt-top">
				<div class="col-md-12">
                    <div class="register-main">
                        <div class="col-md-6 account-left">
                            <form method="post" action="<?= PATH ?>/user/recover-pass" id="recover" role="form" data-toggle="validator">                                
								<div class="form-group has-feedback mb-3">
                                    <label class="form-label" for="new_pass">Новый пароль</label>
                                    <div data-password-field class="checkout-password">
                                      <div class="checkout-password__input"><input id="new_pass" type="password" name="password" autocomplete="new-password" class="form-control" placeholder="Введите новый пароль" minlength="6" maxlength="72" required><button type="button" class="checkout-password__toggle" data-password-toggle aria-controls="new_pass" aria-pressed="false">Показать</button></div>
                                      <button type="button" class="checkout-password__generate" data-password-generate>Сгенерировать пароль</button>
                                      <p data-password-status class="checkout-password__status" role="status"></p>
                                    </div>
                                    <span class="glyphicon form-control-feedback" aria-hidden="true"></span>
                                </div>
								<input type="hidden" name="hash" value="<?=htmlspecialchars((string)($_GET['key'] ?? ''), ENT_QUOTES, 'UTF-8')?>">
                                <input type="hidden" name="csrf" value="<?=htmlspecialchars($_SESSION['recover_pass_csrf'] ?? '', ENT_QUOTES, 'UTF-8')?>">
                                <button type="submit" name="new_pass" class="btn btn-primary mb-3">Отправить</button>
                            </form>
                            <?php if(isset($_SESSION['form_data'])) unset($_SESSION['form_data']); ?>
                        </div>
                    </div>
                </div>            
			</div>
			<?php } ?>
    </div>
</div>
<!--product-end-->
<script src="/js/account-password.js?v=<?= (int)filemtime(WWW.'/js/account-password.js') ?>" defer></script>
