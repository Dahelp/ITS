<?php if($_SESSION['user']['groups'] == 1) { ?>
<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Настройки</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item active">Основные настройки</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

<!-- Main content -->
<section class="content">
	<div class="row">
        <div class="col-12">
		    <form action="<?=ADMIN;?>/options" method="post">
            <!-- Custom Tabs -->
            <div class="card">
				<div class="card-header d-flex p-0">
					<h3 class="card-title p-3">Настройки сайта</h3>
					<ul class="nav nav-pills ml-auto p-2">
						<?php $j=0; foreach($options as $opts) { $opid = $j+1; ?>
							<li class="nav-item"><a class="nav-link <?php if($opid==1) { echo "active"; } ?>" href="#tab_<?=$opid?>" data-toggle="tab"><?=$opts['tip']?></a></li>
						<?php $j++;} ?>
					</ul>
				</div><!-- /.card-header -->
				<div class="card-body">
					<div class="tab-content">					
						<?php $i=0; foreach($options as $opts) { $opid = $i+1; ?>
							<div class="tab-pane <?php if($opid==1) { echo "active"; } ?>" id="tab_<?=$opid?>">
								<div class="box-body">
									<?php 
									$nastrs = \R::getAll("SELECT*FROM options WHERE tip = '".$opts['tip']."'");
									foreach($nastrs as $nst) {
									?>
										<?php if($nst['tip'] == "SEO") { ?>
											<div class="form-group row">
												<label class="col-sm-3 col-form-label"><?=$nst['name_op']?></label>
												<div class="col-sm-9">
													<?php if($nst['tag'] == "input") { ?>													
															<input type="text" class="form-control" placeholder="<?=$nst['placeholder']?>" name="altname[<?=$nst['option_id']?>][znachenie]" value="<?=$nst['znachenie']?>">												
													<?php } ?>
												</div>
											</div>
										<?php } ?>
										
										<?php if($nst['tip'] == "Основные") { ?>
											<div class="form-group row">
											<label class="col-sm-3 col-form-label"><?=$nst['name_op']?></label>
											<div class="col-sm-9">
												<?php if($nst['tag'] == "input") { ?>
													<?php if($nst['alt_name'] == "option_telefon") { ?>																									
														<input type="text" class="form-control phonez" placeholder="<?=$nst['placeholder']?>" name="altname[<?=$nst['option_id']?>][znachenie]" value="<?=$nst['znachenie']?>">
													<?php }else{ ?>
														<input type="text" class="form-control" placeholder="<?=$nst['placeholder']?>" name="altname[<?=$nst['option_id']?>][znachenie]" value="<?=$nst['znachenie']?>">														
													<?php } ?>													
												<?php } 
													if($nst['tag'] == "textarea") { ?>													
															<textarea name="altname[<?=$nst['option_id']?>][znachenie]" id="editor" class="form-control" cols="80" rows="10"><?=$nst['znachenie']?></textarea>															
												<?php } ?>
											</div>
										</div>
										<?php } ?>
										
										<?php if($nst['tip'] == "Оформление") { ?>
											<div class="form-group row">
												<label class="col-sm-3 col-form-label"><?=$nst['name_op']?></label>
												<div class="col-sm-9 input-group my-colorpicker<?=$nst['option_id']?> colorpicker-element">
													<input type="text" class="form-control my-colorpicker<?=$nst['option_id']?> colorpicker-element" data-colorpicker-id="<?=$nst['option_id']?>" data-original-title="" title="" placeholder="<?=$nst['placeholder']?>" name="altname[<?=$nst['option_id']?>][znachenie]" value="<?=$nst['znachenie']?>">
													<div class="input-group-append">
														<span class="input-group-text"><i class="fas fa-square" style="color:<?=$nst['znachenie']?>"></i></span>
													</div>
												</div>
											</div>
										<?php } ?>
										
										<?php if($nst['tip'] == "Товары") { ?>
											<div class="form-group row">
												<label class="col-sm-3 col-form-label"><?=$nst['name_op']?></label>
												<div class="col-sm-9">
													<?php if($nst['tag'] == "input") { ?>													
															<input type="text" class="form-control" placeholder="<?=$nst['placeholder']?>" name="altname[<?=$nst['option_id']?>][znachenie]" value="<?=$nst['znachenie']?>">													
													<?php } 
															if($nst['tag'] == "textarea") { ?>													
															<textarea name="altname[<?=$nst['option_id']?>][znachenie]" id="editor" class="form-control" cols="80" rows="10"><?=$nst['znachenie']?></textarea>															
													<?php } ?>
												</div>
											</div>
										<?php } ?>

										<?php if($nst['tip'] == "Заказы") { ?>
											<div class="form-group row">
												<label class="col-sm-3 col-form-label"><?=$nst['name_op']?></label>
												<div class="col-sm-9 input-group my-colorpicker<?=$nst['option_id']?> colorpicker-element">
													<input type="text" class="form-control my-colorpicker<?=$nst['option_id']?> colorpicker-element" data-colorpicker-id="<?=$nst['option_id']?>" data-original-title="" title="" placeholder="<?=$nst['placeholder']?>" name="altname[<?=$nst['option_id']?>][znachenie]" value="<?=$nst['znachenie']?>">
													<div class="input-group-append">
														<span class="input-group-text">+ID</span>
													</div>
												</div>
											</div>
										<?php } ?>
										
									<?php } ?>
									
								</div>
							</div>
						<?php $i++; } ?>						
					</div>
					<!-- /.tab-content -->				
				</div><!-- /.card-body -->		  
            </div>
			<div class="box-footer">
                <button type="submit" class="btn btn-primary btn_save">Сохранить</button>
            </div>
            <!-- ./card -->
			</form>
        </div>
          <!-- /.col -->
    </div>
    <!-- /.row -->
    <!-- END CUSTOM TABS -->		
</section>
<!-- /.content -->
<?php }else{ ?>
<div class="alert alert-warning alert-dismissible">
	<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
	<h5><i class="icon fas fa-exclamation-triangle"></i> Доступ закрыт!</h5>
		На этой странице есть ограничения доступа. Обратитесь к администратору.
</div>
<?php } ?>