<?php

header("Content-Type: text/html; charset=utf-8");
error_reporting(0);

if(!$connection){

  echo("Ошибка соединения с почтой - ".$mail_login);
    exit;
}else{
	
	$msg_num = imap_num_msg($connection);	
	$mails_data = array();
	
?>

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
		<div class="col-md-3">
			<?php new \app\widgets\mailbox\Mailbox('mailbox_tpl.php', $connection); ?>
		</div>
		<div class="col-md-9">
			<div class="card card-primary card-outline">
				<div class="card-header">
					<h3 class="card-title">Входящие</h3>
					<div class="card-tools">
						<div class="input-group input-group-sm">
							<input type="text" class="form-control" placeholder="Search Mail">
							<div class="input-group-append">
								<div class="btn btn-primary">
									<i class="fas fa-search"></i>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="card-body p-0">
					<div class="mailbox-controls">
						<button type="button" class="btn btn-default btn-sm checkbox-toggle"><i class="far fa-square"></i></button>
						<div class="btn-group">
							<button type="button" class="btn btn-default btn-sm">
								<i class="far fa-trash-alt"></i>
							</button>
							<button type="button" class="btn btn-default btn-sm">
								<i class="fas fa-reply"></i>
							</button>
							<button type="button" class="btn btn-default btn-sm">
								<i class="fas fa-share"></i>
							</button>
						</div>
						<button type="button" class="btn btn-default btn-sm">
							<i class="fas fa-sync-alt"></i>
						</button>
						<div class="float-right">
							1-50/200
							<div class="btn-group">
								<button type="button" class="btn btn-default btn-sm">
									<i class="fas fa-chevron-left"></i>
								</button>
								<button type="button" class="btn btn-default btn-sm">
									<i class="fas fa-chevron-right"></i>
								</button>
							</div>
						</div>
					</div>
					<div class="table-responsive mailbox-messages">
						<table class="table table-hover table-striped">
							<tbody>

								<?php 

									for($i = 1; $i <= $msg_num; $i++){
									  
										$mails_data[$i]["time"] = time($msg_header->MailDate);
										$mails_data[$i]["date"] = $msg_header->MailDate;

										foreach($msg_header->to as $data){
										  $mails_data[$i]["to"] = $data->mailbox."@".$data->host;
										}

										foreach($msg_header->from as $data){
										   $mails_data[$i]["from"] = $data->mailbox."@".$data->host;
										}
										
										$mails_data[$i]["title"] = $mailbox->get_imap_title($msg_header->subject);
										
										$body = "";

										// Тело письма
										$msg_structure = imap_fetchstructure($connection, $i);
										$msg_body      = imap_fetchbody($connection, $i, 1);
										
										//debug($msg_structure);
										//exit();
										
										$recursive_data = $mailbox->recursive_search($msg_structure);

										if($recursive_data["encoding"] == 0 ||
										   $recursive_data["encoding"] == 1){

											$body = $msg_body;
										}

										if($recursive_data["encoding"] == 4){

										  $body = $mailbox->structure_encoding($recursive_data["encoding"], $msg_body);
										}

										if($recursive_data["encoding"] == 3){

										   $body = $mailbox->structure_encoding($recursive_data["encoding"], $msg_body);
										}

										if($recursive_data["encoding"] == 2){

										   $body = $mailbox->structure_encoding($recursive_data["encoding"], $msg_body);
										}

										if(!$mailbox->check_utf8($recursive_data["charset"])){

											$body = $mailbox->convert_to_utf8($recursive_data["charset"], $msg_body);
										}
										
										$mails_data[$i]["body"] = base64_encode($body);
										
										// Вложенные файлы
										if(isset($msg_structure->parts)){

										  for($j = 1, $f = 2; $j < count($msg_structure->parts); $j++, $f++){

											   if(in_array($msg_structure->parts[$j]->subtype, $mail_filetypes)){

													$mails_data[$i]["attachs"][$j]["type"] = $msg_structure->parts[$j]->subtype;
												  $mails_data[$i]["attachs"][$j]["size"] = $msg_structure->parts[$j]->bytes;
													$mails_data[$i]["attachs"][$j]["name"] = $mailbox->get_imap_title($msg_structure->parts[$j]->parameters[0]->value);
												  $mails_data[$i]["attachs"][$j]["file"] = $mailbox->structure_encoding(
														$msg_structure->parts[$j]->encoding,
													  imap_fetchbody($connection, $i, $f)
												 );

												  file_put_contents("app/views/itscenter/admin/Mailbox/tmp/".iconv("utf-8", "cp1251", $mails_data[$i]["attachs"][$j]["name"]), $mails_data[$i]["attachs"][$j]["file"]);
											   }
										   }
										}
								?>
								<tr>
									<td>
										<div class="icheck-primary">
											<input type="checkbox" value="" id="check1">
											<label for="check1"></label>
										</div>
									</td>
									<td class="mailbox-star">
										<a href="#"><i class="fas fa-star text-warning"></i></a>
									</td>
									<td class="mailbox-name">
										<a href="read-mail.html">Alexander Pierce</a>
									</td>
									<td class="mailbox-subject">
										<b><?=$subject?></b> - Trying to find a solution to this problem...
									</td>
									<td class="mailbox-attachment"></td>
									<td class="mailbox-date">5 mins ago</td>
								</tr>								
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>

				<div class="card-footer p-0">
					<div class="mailbox-controls">
						<button type="button" class="btn btn-default btn-sm checkbox-toggle">
							<i class="far fa-square"></i>
						</button>
						<div class="btn-group">
							<button type="button" class="btn btn-default btn-sm">
								<i class="far fa-trash-alt"></i>
							</button>
							<button type="button" class="btn btn-default btn-sm">
								<i class="fas fa-reply"></i>
							</button>
							<button type="button" class="btn btn-default btn-sm">
								<i class="fas fa-share"></i>
							</button>
						</div>
						<button type="button" class="btn btn-default btn-sm">
							<i class="fas fa-sync-alt"></i>
						</button>
						<div class="float-right">
							1-50/200
							<div class="btn-group">
								<button type="button" class="btn btn-default btn-sm">
									<i class="fas fa-chevron-left"></i>
								</button>
								<button type="button" class="btn btn-default btn-sm">
									<i class="fas fa-chevron-right"></i>
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<!-- /.content -->
<?php } 
imap_close($connection); ?>