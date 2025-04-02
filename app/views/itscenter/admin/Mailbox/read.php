<?php
	$email = $mailbox->getMail(
		$_GET["id"], // ID of the email, you want to get
		false // Do NOT mark emails as seen (optional)
	);
?>
<!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Входящее письмо</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
              <li class="breadcrumb-item active">Входящее письмо</li>
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
			<?php new \app\widgets\mailbox\Mailbox('mailbox_tpl.php'); ?>
		</div>
		<div class="col-md-10">
			<?php if($message) { ?>
			<div class="card card-primary card-outline">
<div class="card-header">
	<h3 class="card-title"><?=h($message->subject)?></h3>
	<div class="card-tools">
		<div class="btn-group">
			<button type="button" class="btn btn-default btn-sm" data-container="body" title="Удалить">
			<i class="far fa-trash-alt"></i>
			</button>
			<button type="button" class="btn btn-default btn-sm" data-container="body" title="Предыдущее письмо">
			<i class="fas fa-reply"></i>
			</button>
			<button type="button" class="btn btn-default btn-sm" data-container="body" title="Следующее">
			<i class="fas fa-share"></i>
			</button>
			<button type="button" class="btn btn-default btn-sm" data-container="body" title="Печать">
			<i class="fas fa-print"></i>
			</button>
		</div>
	</div>
</div>

<div class="card-body p-0">
<div class="mailbox-read-info">
	<span style="font-weight:600;font-size:18px;padding:0 10px 0 0"><?=$message->from_name?></span> <span style="padding:0 10px 0 0"><?=$message->from_mail?></span> <span class="mailbox-read-time"><?php echo \ishop\App::abbreviateddate(date('Y-m-d', strtotime($message->date_dispatch))); ?></span>
	<span class="float-right"><a href="<?=ADMIN?>/mailbox/answer?id=<?=$_GET["id"];?>" class="btn btn-primary"><i class="far fa-envelope"></i> Ответить</a></span>
</div>

<div class="mailbox-read-message">
<?=base64_decode($message["content"])?>
</div>

</div>
<?php            
// Save attachments one by one
if (!$mailbox->getAttachmentsIgnore()) {
    $attachments = $email->getAttachments();            
?>
<div class="card-footer bg-white">
	<ul class="mailbox-attachments d-flex align-items-stretch clearfix">
	<?php 
		foreach ($attachments as $attachment) {

                // Set individually filePath for each single attachment
                // In this case, every file will get the current Unix timestamp
                $attachment->setFilePath('../app/views/'.TEMPLATE.'/admin/Mailbox/files/'.$attachment->name.'');
				$attachment->saveToDisk();
	?>
		<li>
			<span class="mailbox-attachment-icon">
				<?php if($attachment->Extension=="doc") { ?><i class="far fa-file-word"></i><?php } ?>
				<?php if($attachment->Extension=="pdf") { ?><i class="far fa-file-pdf"></i><?php } ?>
				<?php if($attachment->Extension=="xls" OR $attachment->Extension=="xlsx") { ?><i class="far fa-file-spreadsheet"></i><?php } ?>
				<?php if($attachment->Extension=="jpeg" OR $attachment->Extension=="jpg" OR $attachment->Extension=="gif" OR $attachment->Extension=="png") { ?><img src="../app/views/<?=TEMPLATE?>/admin/Mailbox/files/<?=$attachment->name?>" style="width:178px" /><?php } ?>
			</span>
			<div class="mailbox-attachment-info">
				<a href="../app/views/<?=TEMPLATE?>/admin/Mailbox/files/<?=$attachment->name?>" class="mailbox-attachment-name"><i class="fas fa-paperclip"></i> <?=$attachment->name?></a>
				<span class="mailbox-attachment-size clearfix mt-1">
					<span><?=$attachment->sizeInBytes?> KB</span>
					<a href="#" class="btn btn-default btn-sm float-right"><i class="fas fa-cloud-download-alt"></i></a>
				</span>
			</div>
		</li>
			<?php } ?>
	</ul>
</div>
<?php } ?>
<div class="card-footer">
<div class="float-right">
<button type="button" class="btn btn-default"><i class="fas fa-reply"></i> Предыдущее письмо</button>
<button type="button" class="btn btn-default"><i class="fas fa-share"></i> Следующее письмо</button>
</div>
<button type="button" class="btn btn-default"><i class="far fa-trash-alt"></i> Удалить</button>
<button type="button" class="btn btn-default"><i class="fas fa-print"></i> Печать</button>
</div>

</div>
			<?php } ?>
		</div>
	</div>
</section>
<!-- /.content -->

<?php $mailbox->disconnect(); ?>