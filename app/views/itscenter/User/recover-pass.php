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
			<?php if($_GET["key"]) { ?>
			<div class="prdt-top">
				<div class="col-md-12">
                    <div class="register-main">
                        <div class="col-md-6 account-left">
                            <form method="post" action="user/recover-pass" id="recover" role="form" data-toggle="validator">                                
								<div class="form-group has-feedback mb-3">
                                    <label class="form-label" for="new_pass">Новый пароль</label>
									<input type="password" name="password" class="form-control" placeholder="Введите новый пароль" required>
                                    <span class="glyphicon form-control-feedback" aria-hidden="true"></span>
                                </div>
								<input type="hidden" name="hash" value="<?=$_GET["key"] ?? ''?>">
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