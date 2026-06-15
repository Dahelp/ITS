<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Производители</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Производители</li>
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
                <a href="<?=ADMIN;?>/brand/add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить производителя</a>
            </div>
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Список производителей</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
					<div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th style="width: 10px">ID</th>
								<th style="width: 60px;text-align:center">Фото</th>
                                <th>Производитель</th>
								<th style="width: 140px;text-align:center">Товары</th>
								<th style="width: 80px;text-align:center">SEO</th>
                                <th style="width: 100px">Действия</th>
                            </tr>
					    </thead>
                        <tbody>
                            <?php foreach($brands as $brand): ?>
                                <?php
                                $brandPreviewUrl = '';
                                if (!empty($brand['brand_attr_value_id'])) {
                                    $brandPreviewUrl = \app\services\filters\FilterUrlHelper::buildBestCategoryFilterPath(
                                        (int)$brand['brand_attr_value_id'],
                                        (string)$brand['alias'],
                                        'brand'
                                    );
                                }
                                $brandPreviewUrl = $brandPreviewUrl ?: '/brand/' . rawurlencode((string)$brand['alias']);
                                ?>
                                <tr class="cont_td_znach">
                                    <td><?=$brand['id'];?></td>
									<td style="text-align:center">
										<?php if(!empty($brand['img'])) { ?>
											<img src="/images/brand/baseimg/<?=$brand['img'];?>" alt="" style="max-height: 50px;">
										<?php }else{ ?>
											<img src="/images/nof.jpg" alt="" style="max-height: 50px;">
										<?php } ?>
									</td>
                                    <td><?=$brand['name'];?></td>
									<td style="text-align:center">
										<?php $productCount = (int)($brand['product_count'] ?? 0); ?>
										<?php $activeProductCount = (int)($brand['active_product_count'] ?? 0); ?>
										<span class="badge <?=$activeProductCount > 0 ? 'bg-success' : 'bg-secondary';?>" title="Активные товары"><?=$activeProductCount;?></span>
										<?php if ($productCount !== $activeProductCount): ?>
											<br><small title="Всего привязанных товаров">всего: <?=$productCount;?></small>
										<?php endif; ?>
									</td>
									<td><?php 
										$s1 = $s2 = $s3 = $s4 = $s5 = 0;
										if(!empty($brand['title'])) { $s1 = 20; }
										if(!empty($brand['description'])) { $s2 = 20; }
										if(!empty($brand['keywords'])) { $s3 = 20; }
										if(!empty($brand['content'])) { $s4 = 20; }
										if(!empty($brand['img'])) { $s5 = 20; }
										$seo = $s1+$s2+$s3+$s4+$s5; 
									?>
									<?php if($seo == 20) { ?><span class="badge bg-danger">20%</span><?php } ?>
									<?php if($seo == 40) { ?><span class="badge bg-danger">40%</span><?php } ?>
									<?php if($seo == 60) { ?><span class="badge bg-warning">60%</span><?php } ?>
									<?php if($seo == 80) { ?><span class="badge bg-warning">80%</span><?php } ?>
									<?php if($seo == 100) { ?><span class="badge bg-success">100%</span><?php } ?>
									</td>                                    
                                    <td><a href="<?=ADMIN;?>/brand/edit?id=<?=$brand['id'];?>"><i class="fas fa-pencil-alt"></i></a> <a class="delete" href="<?=ADMIN;?>/brand/delete?id=<?=$brand['id'];?>" onclick="return confirm('<?=$productCount > 0 ? 'У производителя есть товары: '.$productCount.'. Удалить производителя?' : 'Удалить производителя?';?>');"><i class="fas fa-times-circle text-danger"></i></a> <a target="_blank" href="<?=h($brandPreviewUrl);?>"><i class="fas fa-eye"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                    </table>
					</div>
                    </div>                    
                </div>
            </div>
        </div>
</section>
<!-- /.content -->
