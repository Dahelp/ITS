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
              <li class="breadcrumb-item active">Новая компания</li>
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
                <form method="post" action="<?=ADMIN;?>/company/add" role="form" data-toggle="validator">				
					<div class="card">
              <div class="card-header d-flex p-0">
                <h3 class="card-title p-3">Добавить компанию</h3>
				<ul class="nav nav-pills ml-auto p-2">
					<li class="nav-item"><a class="nav-link active" href="#tab_1" data-toggle="tab">Основное</a></li>
					<li id="vid_tip" style="display:none" class="nav-item"><a class="nav-link" href="#tab_2" data-toggle="tab">Оптовый</a></li>									  
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">
                  <div class="tab-pane active" id="tab_1">
                    <div class="box-body">
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="comp_name">Название компании <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="comp_name" id="comp_name" value="<?= isset($_SESSION['form_data']['comp_name']) ? $_SESSION['form_data']['comp_name'] : '' ?>" required>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="comp_short_name">Краткое название компании <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="comp_short_name" id="comp_short_name" value="<?= isset($_SESSION['form_data']['comp_short_name']) ? $_SESSION['form_data']['comp_short_name'] : '' ?>">
                            </div>
                        </div>
						<div class="form-group row">
							<label class="col-sm-3 col-form-label" for="user_id">Контакт</label>
							<div class="col-sm-9">
								<select name="user_id" class="form-control usercontact" id="user_id" data-placeholder="Выберите контактное лицо"></select>
							</div>
						</div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="tip">Тип взаимодействия <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<select class="form-control" id="tip" name="tip" onclick="vidTip(this)">
									<option value="">Выберите тип</option>
									<option value="1">Розничная торговля</option>
									<option value="2">Оптовая торговля</option>
									<option value="0">Выставление счетов</option>
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
								<input type="text" class="form-control" name="url_address" id="url_address" value="<?= isset($_SESSION['form_data']['url_address']) ? $_SESSION['form_data']['url_address'] : '' ?>">
                            </div>
                        </div>				
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="postal_address">Почтовый адрес</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="postal_address" id="postal_address" value="<?= isset($_SESSION['form_data']['postal_address']) ? $_SESSION['form_data']['postal_address'] : '' ?>">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="ogrn">ОГРН, ОГРНИП <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="ogrn" id="ogrn" value="<?= isset($_SESSION['form_data']['ogrn']) ? $_SESSION['form_data']['ogrn'] : '' ?>" placeholder="ОГРН 13 цифр, ОГРНИП 15 цифр" maxlength="15" required>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="inn">ИНН <span class="text-danger">*</span></label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="inn" id="inn" value="<?= isset($_SESSION['form_data']['inn']) ? $_SESSION['form_data']['inn'] : '' ?>" placeholder="Юр.лицо 10 цифр, ИП 12 цифр" maxlength="12" required>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="kpp">КПП</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="kpp" id="kpp" value="<?= isset($_SESSION['form_data']['kpp']) ? $_SESSION['form_data']['kpp'] : '' ?>" placeholder="9 цифр" maxlength="9" data-error="КПП состоит из 9 цифр" data-minlength="9">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="bik">БИК</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="bik" id="bik" value="<?= isset($_SESSION['form_data']['bik']) ? $_SESSION['form_data']['bik'] : '' ?>" placeholder="9 цифр" maxlength="9" data-error="БИК состоит из 9 цифр" data-minlength="9">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="raschet">Расч. счёт</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="raschet" id="raschet" value="<?= isset($_SESSION['form_data']['raschet']) ? $_SESSION['form_data']['raschet'] : '' ?>">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="korschet">Кор. счёт</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="korschet" id="korschet" value="<?= isset($_SESSION['form_data']['korschet']) ? $_SESSION['form_data']['korschet'] : '' ?>">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="bank">Наименование банка</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="bank" id="bank" value="<?= isset($_SESSION['form_data']['bank']) ? $_SESSION['form_data']['bank'] : '' ?>">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="dir_name">Генеральный директор</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" name="dir_name" id="dir_name" value="<?= isset($_SESSION['form_data']['dir_name']) ? $_SESSION['form_data']['dir_name'] : '' ?>">
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="nds">Система налогообложения</label>
							<div class="col-sm-9">
								<select class="form-control" id="nds" name="nds">
									<option value="">Выберите статус</option>
									<option value = "1">с НДС</option>
									<option value = "2">без НДС</option>
								</select>
                            </div>
                        </div>
						<div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="dogovor">Условия поставки</label>
							<div class="col-sm-9">
								<select class="form-control" id="dogovor" name="dogovor">
									<option value="">Выберите статус</option>
									<option value = "1">Договор</option>
									<option value = "2">Счёт-договор</option>
								</select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label" for="hide">Статус</label>
							<div class="col-sm-9">
								<select class="form-control" id="hide" name="hide">
									<option value="">Выберите статус</option>
									<option value="show">Активный</option>
									<option value="hide">Не активный</option>									
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
                        <button type="submit" class="btn btn-primary">Добавить</button>
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
var total = 0;
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