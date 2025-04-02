<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Отзывы</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Список отзывов</li>
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
                <a href="<?=ADMIN;?>/review/add" class="btn btn-primary"><i class="fa fa-fw fa-plus"></i> Добавить отзыв</a>
            </div>
            <div class="card">
				<div class="card-header">
                    <h3 class="card-title">Список отзывов</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
					<div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th style="width: 10px">ID</th>
								<th>Товар</th>
                                <th>Дата</th>
                                <th>Имя</th>
								<th>Отзыв</th>
                                <th>Оценка</th>
								<th>Статус</th>								
                                <th>Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach($reviews as $review): 
								$product = \R::findOne('product', 'id = ?', [$review["product_id"]]);
							?>
                                    <td><?=$review["review_id"];?></td>
									<td><?=$product["name"];?></td>
                                    <td><?=$review["date_post"];?></td>
                                    <td><?=$review["uname"];?></td>
									<td><?=$review["content"];?></td>
									<td><?=$review["point"];?></td>
									<td><?php if($review["hide"]=="show") { echo "Активный"; }else{ echo "Не активный"; } ?></td>									
                                    <td><a href="<?=ADMIN;?>/review/edit?id=<?=$review["review_id"];?>"><i class="fas fa-pencil-alt"></i></a> <a class="delete" href="<?=ADMIN;?>/review/delete?id=<?=$review["review_id"];?>"><i class="fas fa-times-circle text-danger"></i></a> <a target="_blank" href="/product/<?=$product["alias"]?>"><i class='fas fa-eye'></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                    </table>
					</div>
                </div>
                <div class="text-center">
                        <p>(<?=count($reviews);?> отзывов из <?=$count;?>)</p>
                        <?php if($pagination->countPages > 1): ?>
                            <?=$pagination;?>
                        <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->