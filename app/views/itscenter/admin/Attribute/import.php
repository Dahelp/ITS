<script>
function Selected(a) {
        var label = a.value;
        if (label==1) {
            document.getElementById("Block1").style.display='block';
            document.getElementById("Block2").style.display='none';
			document.getElementById("Block3").style.display='none';
        } else if (label==2) {
            document.getElementById("Block1").style.display='none';
            document.getElementById("Block2").style.display='block';  
			document.getElementById("Block3").style.display='none';			
        }
		else {
            document.getElementById("Block1").style.display='none';
            document.getElementById("Block2").style.display='none';
			document.getElementById("Block3").style.display='none';
        }
         
}
</script>
<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Импорт атрибутов</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
			  <li class="breadcrumb-item"><a href="<?=ADMIN;?>/attribute">Список атрибутов</a></li>
              <li class="breadcrumb-item active">Импорт атрибутов</li>
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
			<form method="post" action="<?=ADMIN;?>/attribute/import" enctype="multipart/form-data" role="form" data-toggle="validator">
				<div class="card">
					<div class="card-header">
						<h3 class="card-title">Импорт атрибутов</h3>
					</div>
					<!-- /.card-header -->
					<div class="card-body">
						<div class="box-body">
							<div class="form-group row">
								<label class="col-sm-3 col-form-label" for="format">Атрибуты</label>
								<div class="col-sm-9">
								<select id="format" class="form-control" name="format" aria-required="true" onChange="Selected(this)">
									<option value= "" selected="selected">Выберите атрибуты</option>
									<option value= "1">Вес и объём</option>									            			
								</select>
								</div>
							</div>
							<div id="Block1" style="display: none;" class="form-group row">
								<label class="col-sm-3 col-form-label" style="float: left;width: 25%;" for="fileprod">Файл</label>
								<div class="col-sm-9" style="float: left;">
									<input type="file" class="form-control" name="fileprod" class="form-control" id="fileprod" value="Выбрать">											
								</div>
							</div>									          
						</div>
					</div>                                   
				</div>
				<div class="box-footer">
					<button type="submit" class="btn btn-primary">Загрузить</button>
				</div>
			</form>
        </div>
    </div>
</section>
<!-- /.content -->