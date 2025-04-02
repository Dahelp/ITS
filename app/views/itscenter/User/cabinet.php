<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">      
		<nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
			<ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>				
                <li class="breadcrumb-item active">Личный кабинет</li>
            </ol>
		</nav>
    </div>
</div>
<!--end-breadcrumbs-->
<!--prdt-starts-->
<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-start cab-inner">
            <div class="aiz-user-sidenav-wrap position-relative z-1 shadow-sm">				
				<?php new \app\widgets\cabinet\Cabinet('cabinet_tpl.php'); ?>
			</div>
			<div class="aiz-user-panel">
				<div class="register-top heading">
					<h3>Личный кабинет</h3>
				</div>
				<div class="row gutters-10">
					<div class="col-md-4">
						<div class="bg-grad-1 text-white rounded-lg mb-4 overflow-hidden">
							<div class="px-3 pt-3">
								<div class="h3 fw-700">
									<?php if(!empty($_SESSION['cart'])): ?><?=$_SESSION['cart.qty']?><?php else: ?>0<?php endif; ?> Товаров
								</div>
								<div class="opacity-50"><a href="cart/show" onclick="getCart(); return false;" title="Корзина">Товаров в корзине</a></div>
							</div>
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
								<path fill="rgba(255,255,255,0.3)" fill-opacity="1" d="M0,192L30,208C60,224,120,256,180,245.3C240,235,300,181,360,144C420,107,480,85,540,96C600,107,660,149,720,154.7C780,160,840,128,900,117.3C960,107,1020,117,1080,112C1140,107,1200,85,1260,74.7C1320,64,1380,64,1410,64L1440,64L1440,320L1410,320C1380,320,1320,320,1260,320C1200,320,1140,320,1080,320C1020,320,960,320,900,320C840,320,780,320,720,320C660,320,600,320,540,320C480,320,420,320,360,320C300,320,240,320,180,320C120,320,60,320,30,320L0,320Z"></path>
							</svg>
						</div>
					</div>
					<div class="col-md-4">
						<div class="bg-grad-2 text-white rounded-lg mb-4 overflow-hidden">
							<div class="px-3 pt-3">
								<div class="h3 fw-700">
									<?php $zcount = \R::count('product_bookmarks', 'user_id = ?', [$_SESSION['user']['id']]); echo $zcount; ?> Товаров
								</div>
								<div class="opacity-50"><a href="user/bookmarks" title="Ваши закладки">Ваши закладки</a></div>
							</div>
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
								<path fill="rgba(255,255,255,0.3)" fill-opacity="1" d="M0,128L34.3,112C68.6,96,137,64,206,96C274.3,128,343,224,411,250.7C480,277,549,235,617,213.3C685.7,192,754,192,823,181.3C891.4,171,960,149,1029,117.3C1097.1,85,1166,43,1234,58.7C1302.9,75,1371,149,1406,186.7L1440,224L1440,320L1405.7,320C1371.4,320,1303,320,1234,320C1165.7,320,1097,320,1029,320C960,320,891,320,823,320C754.3,320,686,320,617,320C548.6,320,480,320,411,320C342.9,320,274,320,206,320C137.1,320,69,320,34,320L0,320Z"></path>
							</svg>
						</div>
					</div>
					<div class="col-md-4">
						<div class="bg-grad-3 text-white rounded-lg mb-4 overflow-hidden">
							<div class="px-3 pt-3">
								<div class="h3 fw-700"><?php $ocount = \R::count('order', 'user_id = ?', [$_SESSION['user']['id']]); echo $ocount; ?> Заказов</div>
								<div class="opacity-50"><a href="user/orders" title="Ваши заказы">Ваши заказы</a></div>
							</div>
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
								<path fill="rgba(255,255,255,0.3)" fill-opacity="1" d="M0,192L26.7,192C53.3,192,107,192,160,202.7C213.3,213,267,235,320,218.7C373.3,203,427,149,480,117.3C533.3,85,587,75,640,90.7C693.3,107,747,149,800,149.3C853.3,149,907,107,960,112C1013.3,117,1067,171,1120,202.7C1173.3,235,1227,245,1280,213.3C1333.3,181,1387,107,1413,69.3L1440,32L1440,320L1413.3,320C1386.7,320,1333,320,1280,320C1226.7,320,1173,320,1120,320C1066.7,320,1013,320,960,320C906.7,320,853,320,800,320C746.7,320,693,320,640,320C586.7,320,533,320,480,320C426.7,320,373,320,320,320C266.7,320,213,320,160,320C106.7,320,53,320,27,320L0,320Z"></path>
							</svg>
						</div>
					</div>
				</div>                    
			</div>
            </div>
        </div>
 </section>
<!--product-end-->
<div class="aiz-mobile-side-nav collapse-sidebar-wrap sidebar-xl d-xl-none z-1035">
	<div class="overlay dark c-pointer overlay-fixed" data-toggle="class-toggle" data-backdrop="static" data-target=".aiz-mobile-side-nav" data-same=".mobile-side-nav-thumb"></div>
	<div class="collapse-sidebar bg-white">
		<div class="aiz-user-sidenav-wrap position-relative z-1 shadow-sm">
			<div class="aiz-user-sidenav rounded overflow-auto c-scrollbar-light pb-5 pb-xl-0">
				<div class="p-4 text-xl-center mb-4 border-bottom bg-primary text-white position-relative">
					<span class="avatar avatar-md mb-3">
							<img src="https://new.apco.ru/public/assets/img/avatar-place.png" class="image rounded-circle" onerror="this.onerror=null;this.src='https://new.apco.ru/public/assets/img/avatar-place.png';">
					</span>
					<h4 class="h5 fs-16 mb-1 fw-600">Дмитрий</h4>
					<div class="text-truncate opacity-60">+79671524825</div>
				</div>
				<div class="sidemnenu mb-3">
					<ul class="aiz-side-nav-list px-2 metismenu" data-toggle="aiz-side-menu">

						<li class="aiz-side-nav-item mm-active">
							<a href="https://new.apco.ru/dashboard" class="aiz-side-nav-link active" aria-expanded="true">
								<i class="las la-home aiz-side-nav-icon"></i>
								<span class="aiz-side-nav-text">Dashboard</span>
							</a>
						</li>

						
																		<li class="aiz-side-nav-item">
									<a href="https://new.apco.ru/purchase_history" class="aiz-side-nav-link ">
										<i class="las la-file-alt aiz-side-nav-icon"></i>
										<span class="aiz-side-nav-text">Purchase History</span>
																	</a>
								</li>

								<li class="aiz-side-nav-item">
									<a href="https://new.apco.ru/digital_purchase_history" class="aiz-side-nav-link ">
										<i class="las la-download aiz-side-nav-icon"></i>
										<span class="aiz-side-nav-text">Downloads</span>
									</a>
								</li>
							
															<li class="aiz-side-nav-item">
										<a href="https://new.apco.ru/sent-refund-request" class="aiz-side-nav-link ">
											<i class="las la-backward aiz-side-nav-icon"></i>
											<span class="aiz-side-nav-text">Sent Refund Request</span>
										</a>
									</li>
													
								<li class="aiz-side-nav-item">
									<a href="https://new.apco.ru/wishlists" class="aiz-side-nav-link ">
										<i class="la la-heart-o aiz-side-nav-icon"></i>
										<span class="aiz-side-nav-text">Wishlist</span>
									</a>
								</li>

								<li class="aiz-side-nav-item">
									<a href="https://new.apco.ru/compare" class="aiz-side-nav-link ">
										<i class="la la-refresh aiz-side-nav-icon"></i>
										<span class="aiz-side-nav-text">Compare</span>
									</a>
								</li>

													<li class="aiz-side-nav-item">
									<a href="https://new.apco.ru/customer_products" class="aiz-side-nav-link ">
										<i class="lab la-sketch aiz-side-nav-icon"></i>
										<span class="aiz-side-nav-text">Classified Products</span>
									</a>
								</li>
												
													<li class="aiz-side-nav-item">
									<a href="javascript:void(0);" class="aiz-side-nav-link">
										<i class="las la-gavel aiz-side-nav-icon"></i>
										<span class="aiz-side-nav-text">Auction</span>
										<span class="aiz-side-nav-arrow"></span>
									</a>
									<ul class="aiz-side-nav-list level-2 mm-collapse">
										<li class="aiz-side-nav-item">
											<a href="https://new.apco.ru/auction_product_bids" class="aiz-side-nav-link">
												<span class="aiz-side-nav-text">Bidded Products</span>
											</a>
										</li>
										<li class="aiz-side-nav-item">
											<a href="https://new.apco.ru/auction/purchase_history" class="aiz-side-nav-link">
												<span class="aiz-side-nav-text">Purchase History</span>
											</a>
										</li>
									</ul>
								</li>
							
																			<li class="aiz-side-nav-item">
									<a href="https://new.apco.ru/conversations" class="aiz-side-nav-link ">
										<i class="las la-comment aiz-side-nav-icon"></i>
										<span class="aiz-side-nav-text">Conversations</span>
																	</a>
								</li>
							

													<li class="aiz-side-nav-item">
									<a href="https://new.apco.ru/wallet" class="aiz-side-nav-link ">
										<i class="las la-dollar-sign aiz-side-nav-icon"></i>
										<span class="aiz-side-nav-text">My Wallet</span>
									</a>
								</li>
							
													<li class="aiz-side-nav-item">
									<a href="https://new.apco.ru/earning-points" class="aiz-side-nav-link ">
										<i class="las la-dollar-sign aiz-side-nav-icon"></i>
										<span class="aiz-side-nav-text">Earning Points</span>
									</a>
								</li>
							
							
							
							<li class="aiz-side-nav-item">
								<a href="https://new.apco.ru/support_ticket" class="aiz-side-nav-link ">
									<i class="las la-atom aiz-side-nav-icon"></i>
									<span class="aiz-side-nav-text">Support Ticket</span>
															</a>
							</li>
										<li class="aiz-side-nav-item">
							<a href="https://new.apco.ru/profile" class="aiz-side-nav-link ">
								<i class="las la-user aiz-side-nav-icon"></i>
								<span class="aiz-side-nav-text">Manage Profile</span>
							</a>
						</li>
					</ul>
				</div>

			</div>

			<div class="fixed-bottom d-xl-none bg-white border-top d-flex justify-content-between px-2" style="box-shadow: 0 -5px 10px rgb(0 0 0 / 10%);">
				<a class="btn btn-sm p-2 d-flex align-items-center" href="https://new.apco.ru/logout">
					<i class="las la-sign-out-alt fs-18 mr-2"></i>
					<span>Logout</span>
				</a>
				<button class="btn btn-sm p-2 " data-toggle="class-toggle" data-backdrop="static" data-target=".aiz-mobile-side-nav" data-same=".mobile-side-nav-thumb">
					<i class="las la-times la-2x"></i>
				</button>
			</div>
		</div>
	</div>
</div>