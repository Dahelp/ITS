<!--prdt-starts-->
<div class="prdt">
    <div class="container">
		<!--start-breadcrumbs-->
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active">Вход</li>
            </ol>
		</nav>
		<!--end-breadcrumbs-->
		<section class="align-items-center">
            <h1 class="h2 mb-3 mb-md-0 me-3">Вход</h1>			
        </section>		
			<div class="prdt-top">
				<div class="col-md-12">
                    <div class="register-main">
                        <div class="col-md-6 account-left">
							<form method="post" action="user/login" id="email" role="form" data-toggle="validator">
                                <div class="form-group has-feedback mb-3">
                                    <label class="form-label" for="email">E-mail</label>
                                    <input type="text" name="email" class="form-control" id="email" placeholder="E-mail" required>
                                    <span class="glyphicon form-control-feedback" aria-hidden="true"></span>
                                </div>
                                <div class="form-group has-feedback mb-3">
                                    <label class="form-label" for="pasword">Password</label>
                                    <input type="password" name="password" class="form-control" id="pasword" placeholder="Password" required>
                                    <span class="glyphicon form-control-feedback" aria-hidden="true"></span>
                                </div>
                                <button type="submit" class="btn btn-primary mb-3">Вход</button>
                            </form>
                        </div>
                    </div>
                </div>            
			</div>
    </div>
</div>
<!--product-end-->