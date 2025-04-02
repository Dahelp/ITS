<!--prdt-starts-->
<div class="prdt">
    <div class="container">
		<!--start-breadcrumbs-->
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active">Восстановление пароля</li>
            </ol>
		</nav>
		<!--end-breadcrumbs-->
		<section class="align-items-center">
            <h1 class="h2 mb-3 mb-md-0 me-3">Восстановление пароля</h1>			
        </section>		
			<div class="prdt-top">
				<div class="col-md-12">
                    <div class="register-main">
                        <div class="col-md-6 account-left">
                            <form method="post" action="user/recover" id="recover" role="form" data-toggle="validator">                                
								<div class="form-group has-feedback mb-3">
                                    <label class="form-label" for="email">Email</label>
                                    <input type="email" name="email" class="form-control" id="email" placeholder="Email" required>
                                    <span class="glyphicon form-control-feedback" aria-hidden="true"></span>
                                </div>								                                             
                                <button type="submit" class="btn btn-primary mb-3">Отправить</button>
                            </form>
                            <?php if(isset($_SESSION['form_data'])) unset($_SESSION['form_data']); ?>
                        </div>
                    </div>
                </div>            
			</div>
    </div>
</div>
<!--product-end-->