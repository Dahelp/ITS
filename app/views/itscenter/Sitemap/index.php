<div class="breadcrumbs">
    <div class="container">
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>				
                <li class="breadcrumb-item active">Карта сайта</li>
            </ol>
		</nav>
    </div>
</div>
<div class="contents">
    <div class="container">
		<div class="row">		
			<div class="col-md-12">
				<div class="bg-light rounded-3">
					<div class="register-top heading">
						<h1>Карта сайта</h1>
					</div>
					<div class="cont-inner">
						<div class="cont-desc">
							<ul>
								<li><a href="pages/kak-kupit" title="Как купить">Как купить</a></li>
								<li><a href="pages/sotrudnichestvo" title="Сотрудничество">Сотрудничество</a></li>
								<li><a href="services" title="Услуги">Услуги</a></li>
								<li><a href="services/dostavka" title="Доставка">Доставка</a></li>

							</ul>
							<ul>
								<li><a href="pages/about-us" title="О компании">О компании</a></li>
								<li><a href="news" title="Новости">Новости</a></li>
								<li><a href="pages/privacy" title="Политика конфиденциальности">Политика конфиденциальности</a></li>
								<li><a href="pages/contacts" title="Контакты">Контакты</a></li>								
							</ul>
							<?php if($sm_category){ ?>
								<ul>
							<?php foreach($sm_category as $smc) { 
									echo "<li><a href=\"".PATH."/category/".$smc['alias']."\">".$smc['name']."</a>
									<ul>";
									$sm_category_parent = \R::getAll("SELECT alias, name, parent_id FROM `category` WHERE hide='show' AND parent_id = '".$smc["id"]."'");
									foreach($sm_category_parent as $par) {
										echo "<li><a href=\"".PATH."/category/".$par['alias']."\" title=\"".$par['name']."\">".$par['name']."</a></li>";
									}
									echo "</ul>
									</li>";    
							} ?> 
								</ul>
							<?php }	?>
							<?php if($sm_category){ ?>
								<ul>
							<?php if($sm_atgroup){
								foreach($sm_atgroup as $smatg) {  
								
									echo "<li><a href=\"".PATH."/".$smatg["url_params"]."\" title=\"".$smatg["title"]."\">".$smatg["title"]."</a></li>";
									       
								}	 
							} ?> 
								</ul>
							<?php }	?>	
						</div>
					</div>
				</div>					
			</div>	
		</div>
	</div>	
</div>