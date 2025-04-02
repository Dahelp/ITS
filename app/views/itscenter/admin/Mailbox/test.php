<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Входящие письма</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Входящие письма</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

<!-- Main content -->
<section class="content">
	<div class="row">
		<div class="col-md-2">
			<?php new \app\widgets\mailbox\Mailbox('mailbox_tpl.php', $mailbox); ?>
		</div>
		<div class="col-md-10">
			<div class="card card-primary card-outline">
				<div class="card-header">
					
				</div>
				<div class="card-body p-4">
					<div class="table-responsive">
						<table id="example" class="table display" width="100%">
							<thead>
								<tr>
									<th></th>
									<th>Email</th>
									<th>Тема</th>
									<th></th>
									<th>Дата</th>
								</tr>
							</thead>
						</table>  
					</div>
				</div>

				<div class="card-footer p-0">
					<div class="mailbox-controls">
						
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<!-- /.content -->

<script>
	$(document).ready(function () {
		$('#example').DataTable({		
			"processing": true,
			"serverSide": true,			
			"stateSave": true,
			"lengthChange": true,
			"lengthMenu": [[20, 50, 100, -1], [20, 50, 100, "Все"]],
			"aoColumnDefs": [{ 'bSortable': false, 'aTargets': [ 2, 4 ] }],			
			"ajax": {
				url: adminpath + '/mailbox/server-processing<?php if($_GET["folder"]) { echo "?folder=".$_GET["folder"].""; } ?>',		
			}
		});
	})
</script>
