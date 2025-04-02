<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Товары по категориям</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/product">Список товаров</a></li>
              <li class="breadcrumb-item active">Товары по категориям</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
			<div class="menu_btn">
                <a href="<?=ADMIN;?>/product/add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить товар</a>
            </div>
            <div class="card">
				<div class="card-header d-flex p-0">
                    <h3 class="card-title p-3">Товары по категориям</h3>
					<ul class="nav nav-pills ml-auto p-2">
						<li class="nav-item"><a class="nav-link active" href="<?=ADMIN;?>/product">Все товары</a></li>					    			  
					</ul>
                </div>
                <!-- /.card-header -->
                <div class="card-body">					
					<div class="table-responsive">						
						<!-- Аккордеон -->
							<table class="table table-hover">
								<tbody>									
									<?php foreach($price as $key => $item): ?>
										<?php if(count($item) > 1): // если это родительская категория ?>										
										<tr data-widget="expandable-table" aria-expanded="false">
											<td>
												<i class="expandable-table-caret fas fa-caret-right fa-fw"></i>
												<label><?=$item[0]?></label>
											</td>
										</tr>
										<tr class="expandable-body">
											<td>
												<div class="p-0" style="">
													<table class="table table-hover">
														<tbody>														
														<?php foreach($item['sub'] as $key => $sub): ?>
															<?php 
																$countParent = \R::count('product', "category_id IN(SELECT id FROM `category` WHERE parent_id = '".$key."')");
																$podc3 = \R::getAll("SELECT id, name FROM category WHERE parent_id = ?", [$key]);
																if($podc3) {
															?>
															<tr data-widget="expandable-table" aria-expanded="false">
																<td>
																	<i class="expandable-table-caret fas fa-caret-right fa-fw"></i>
																	<label><?=$sub?></label><span class="float-right badge bg-primary"><?=$countParent?></span></td>
															</tr>
															
															<tr class="expandable-body">
																<td>
																	<div class="p-0" style="">
																		<table class="table table-hover">
																			<tbody>														
																			<?php foreach($podc3 as $pc): ?>
																				<?php $countPc = \R::count('product', "category_id = '".$pc["id"]."'"); ?>
																				<tr>
																					<td> - <a href="<?php echo "".ADMIN."/product?category_id=".$pc["id"]."";?>"><?=$pc["name"]?></a><span class="float-right badge bg-primary"><?=$countPc?></span></td>
																				</tr>
																			<?php endforeach; ?>
																			</tbody>
																		</table>
																	</div>
																</td>
															</tr>
															<?php }else{ ?>
															<?php $countProd = \R::count('product', "category_id = '".$key."'"); ?>
															<tr>
																<td> - <a href="<?php echo "".ADMIN."/product?category_id=".$key."";?>"><?=$sub?></a><span class="float-right badge bg-primary"><?=$countProd?></span></td>
															</tr>
															<?php } ?>
														<?php endforeach; ?>
														</tbody>
													</table>
												</div>
											</td>
										</tr>
										<?php elseif($item[0]): // если самостоятельная категория ?>
										<?php $countProd = \R::count('product', "category_id = '".$key."'"); ?>
										<tr>
											<td>
												<a href="<?php echo "".ADMIN."/product?category_id=".$key."";?>"><?=$item[0]?></a><span class="float-right badge bg-primary"><?=$countProd?></span>
											</td>
										</tr>
										<?php endif; ?>
									<?php endforeach; ?>
								</tbody>
							</table>                    
							<!-- Аккордеон -->                    
					</div>						                                    
				</div>
			</div>
		</div>
	</div>
</section>
