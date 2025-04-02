<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Компании</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/company">Список компаний</a></li>
              <li class="breadcrumb-item active">Редактирование компании</li>
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
			<div class="menu_btn">               
				<a class="btn btn-primary" href="<?=ADMIN?>/company/cardcompanyword?id=<?=$company->id?>"><i class='fa fa-file-word' style="color:#fff"></i> карточка компании</a>
            </div>
                <form method="post" action="<?=ADMIN;?>/company/edit" role="form" data-toggle="validator">
				
					<div class="card">
              <div class="card-header d-flex p-0">
                <h3 class="card-title p-3">Редактирование компании <?=$company->comp_name;?></h3>
				<ul class="nav nav-pills ml-auto p-2">
					<li class="nav-item"><a class="nav-link active" href="#tab_1" data-toggle="tab">Основное</a></li>
					<li id="vid_tip" <?php if($company->tip == "2") { ?>style="display:block"<?php }else{ ?>style="display:none"<?php } ?> class="nav-item"><a class="nav-link" href="#tab_2" data-toggle="tab">Оптовый</a></li>									  
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">
                  <div class="tab-pane active" id="tab_1">
                    <div class="box-body">
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="comp_name">Название компании <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="comp_name" id="comp_name" value="<?=h($company->comp_name);?>" required>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="comp_short_name">Краткое название компании <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="comp_short_name" id="comp_short_name" value="<?=h($company->comp_short_name);?>">
                            </div>
                        </div>
						<div class="form-group row">
							<label class="col-sm-3 col-form-label" for="user_id">Контакт</label>
							<div class="col-sm-9">
								<select name="user_id" class="form-control usercontact" id="user_id" data-placeholder="Выберите контактное лицо">
								<?php if(!empty($usercontact)): ?>                                    
                                        <option value="<?=$usercontact['id'];?>" selected><?=$usercontact['name'];?></option>                                    
                                <?php endif; ?>
								</select>
							</div>
						</div>						
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="tip">Тип взаимодействия <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<select class="form-control" id="tip" name="tip" onclick="vidTip(this)">									
									<option value="1" <?php if($company->tip == "1") { echo "selected=\"selected\""; } ?>>Розничная торговля</option>
									<option value="2" <?php if($company->tip == "2") { echo "selected=\"selected\""; } ?>>Оптовая торговля</option>
									<option value="0" <?php if($company->tip == "0") { echo "selected=\"selected\""; } ?>>Выставление счетов</option>
								</select>
                            </div>
                        </div>						
						<script>
							function vidTip(el) {
								var u = el.options[el.selectedIndex].value;    
								document.getElementById("vid_tip").style.display = (u==2)? "block":"none";																
							}
						</script>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="url_address">Юр. адрес</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="url_address" id="url_address" value="<?=h($company->url_address);?>">
                            </div>
                        </div>				
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="postal_address">Почтовый адрес</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="postal_address" id="postal_address" value="<?=h($company->postal_address);?>">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="ogrn">ОГРН, ОГРНИП <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="ogrn" id="ogrn" value="<?=h($company->ogrn);?>" placeholder="ОГРН 13 цифр, ОГРНИП 15 цифр" maxlength="15" required>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="inn">ИНН <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="inn" id="inn" value="<?=h($company->inn);?>" placeholder="Юр.лицо 10 цифр, ИП 12 цифр" maxlength="12" required>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="kpp">КПП</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="kpp" id="kpp" value="<?=h($company->kpp);?>" placeholder="9 цифр" maxlength="9" data-error="КПП состоит из 9 цифр" data-minlength="9">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="bik">БИК</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="bik" id="bik" value="<?=h($company->bik);?>" placeholder="9 цифр" maxlength="9" data-error="БИК состоит из 9 цифр" data-minlength="9">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="raschet">Расч. счёт</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="raschet" id="raschet" value="<?=h($company->raschet);?>">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="korschet">Кор. счёт</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="korschet" id="korschet" value="<?=h($company->korschet);?>">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="bank">Наименование банка</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="bank" id="bank" value="<?=h($company->bank);?>">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="dir_name">Генеральный директор</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="dir_name" id="dir_name" value="<?=h($company->dir_name);?>">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="nds">Система налогообложения</label>
							<div class="col-sm-9">
								<select class="form-control" id="nds" name="nds">
									<option value="1" <?php if($company->nds == "1") { echo "selected=\"selected\""; } ?>>с НДС</option>
									<option value="2" <?php if($company->nds == "2") { echo "selected=\"selected\""; } ?>>без НДС</option>									
								</select>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="dogovor">Условия поставки</label>
							<div class="col-sm-9">
								<select class="form-control" id="dogovor" name="dogovor">
									<option value="1" <?php if($company->dogovor == "1") { echo "selected=\"selected\""; } ?>>Договор</option>
									<option value="2" <?php if($company->dogovor == "2") { echo "selected=\"selected\""; } ?>>Счёт-договор</option>									
								</select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="hide">Статус</label>
							<div class="col-sm-9">
								<select class="form-control" id="hide" name="hide">
									<option value="show" <?php if($company->dogovor == "show") { echo "selected=\"selected\""; } ?>>Активный</option>
									<option value="hide" <?php if($company->dogovor == "hide") { echo "selected=\"selected\""; } ?>>Не активный</option>
								</select>
							</div>
                        </div>
						
                    </div>				
				</div>
					<!-- /.tab-pane -->
					<div class="tab-pane" id="tab_2">                  
						<div class="box-body">
							<table id="table_container">
								<tr>
									<td style="width:40%;padding: 0 0 0 10px;"><strong>Категория</strong></td><td style="width:60%;padding: 0 0 0 10px;"><strong>Скидка от розницы</strong></td><td></td>
								</tr>
								<?php $k = 1; foreach($cat_priceopt as $cat_item): ?>
								
								<tr id="tr_image_<?=$k?>" style="line-height: 20px;">			
									<td id="td_name_<?=$k?>" style="padding: 5px 10px; width: 30%;">
										<select style="padding: 5px 10px; width: 80%;" name="company_priceopt[<?=$k?>][category_id]" class="form-control">
											<option value="<?=$cat_item["category_id"]?>" /><?=$cat_item["category_name"]?></option>
										</select>
									</td>
									<td id="td_text_<?=$k?>" style="padding: 5px 10px; width: 40%;">
										<input style="padding: 5px 10px; width: 80%;" id="category_text_<?=$k?>" name="company_priceopt[<?=$k?>][znachenie]" class="form-control" value="<?=$cat_item["znachenie"]?>">									
									</td>
									<td style="padding: 5px 10px; width: 15%;">
										<span id="progress_<?=$k?>"><a href="javascript:void(0)" onclick="$('#tr_image_<?=$k?>').remove();" class="btn btn-default float-right">Удалить</a></span>
									</td>
								</tr>
								<?php $k++; endforeach; ?>
							</table>
							<br/>
							<div style="float:right;padding:0 30px 0 0"><input type="button" value="Добавить категорию" class="btn btn-success" onclick="return add_new_category();"></div>
						</div>
					</div>
					<!-- /.tab-pane -->
                </div>
                <!-- /.tab-content -->
				
              </div><!-- /.card-body -->
			  
            </div>                   

                    <div class="box-footer">
                        <input type="hidden" name="id" value="<?=$company->id;?>">
						<button type="submit" class="btn btn-success">Сохранить</button>
                    </div>
                </form>
                <?php if(isset($_SESSION['form_data'])) unset($_SESSION['form_data']); ?>
            </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
        <!-- END CUSTOM TABS -->
		
</section>
<!-- /.content -->

<script type="text/javascript">
var total = <?=count($cat_priceopt)?>;
function add_new_category(){
    total++;
    $('<tr>')
    .attr('id','tr_image_'+total)
    .css({lineHeight:'20px'})
    .append (
        $('<td>')
        .attr('id','td_name_'+total)
        .css({padding:'5px 10px',width:'30%'})
        .append(
            $('<select>')
            .css({padding:'5px 10px',width:'80%'})
            .attr('name','company_priceopt['+total+'][category_id]')
		    .attr('class','form-control')
		   
		.append(
		    $('<option>')               
			.attr('value','')
			.append(
			    document.createTextNode("Выберите категорию")
			)
        )  
<?php $categors = \R::getAll('SELECT id, name FROM category');
	  
	$i = 0; foreach($categors as $cat):
	  
	    $category_name = $cat["name"];
	    $category_id = $cat["id"];
?>
		   .append(
		       $('<option>')               
			   .attr('value','<?=$category_id?>')			   
			   .append(
			      document.createTextNode("<?=$category_name?>")
				)
           )
<?php $i++; endforeach; ?>
       )                             
                              
    )
	.append (
       $('<td>')
       .attr('id','td_text_'+total)
       .css({padding:'5px 10px',width:'40%'})
       .append(
           $('<input>')
           .css({padding:'5px 10px',width:'80%'})
           .attr('id','category_text_'+total)
           .attr('name','company_priceopt['+total+'][znachenie]')
		   .attr('class','form-control')
       )                             
                              
    )
    .append(
        $('<td>')
        .css({padding:'5px 10px',width:'15%'})
        .append(
           $('<span id="progress_'+total+'"><a href="javascript:void(0)" onclick="$(\'#tr_image_'+total+'\').remove();" class="btn btn-default float-right">Удалить</a></span>')
         )
     )
     .appendTo('#table_container');                
}
$(document).ready(function() {
    add_new_category();
});
</script>