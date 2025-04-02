<div style="display:flex">
	<a href="<?=ADMIN?>/mailbox/compose" class="btn btn-primary btn-block mb-3" style="width:85%;margin: 0 10px 0 0;">Написать письмо</a>
	<a href="<?=ADMIN?>/mailbox" class="btn btn-primary btn-block mb-3"style="width:15%;margin:0"><i class="fas fa-sync-alt"></i></a>
</div>
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Папки</h3>
		<div class="card-tools">
			<button type="button" class="btn btn-tool" data-card-widget="collapse">
				<i class="fas fa-minus"></i>
			</button>
		</div>
	</div>
	<div class="card-body p-0">
		<ul class="nav nav-pills flex-column">
			<li class="nav-item active">
				<a href="<?=ADMIN?>/mailbox" class="nav-link">
					<i class="fas fa-inbox"></i> Входящие 
					<span class="badge bg-primary float-right mt-1 ml-1"><?=$msg_num_recent;?></span> <span class="float-right"><?=$msg_num;?></span>
				</a>
			</li>
			<li class="nav-item">
				<a href="<?=ADMIN?>/mailbox?folder=Sent" class="nav-link">
					<i class="far fa-envelope"></i> Отправленные
				</a>
			</li>
			<li class="nav-item">
				<a href="<?=ADMIN?>/mailbox?folder=Drafts" class="nav-link">
					<i class="far fa-file-alt"></i> Черновики
				</a>
			</li>
			<li class="nav-item">
				<a href="<?=ADMIN?>/mailbox?folder=Junk" class="nav-link">
					<i class="fas fa-filter"></i> Спам
					<span class="badge bg-warning float-right">65</span>
				</a>
			</li>
			<li class="nav-item">
				<a href="<?=ADMIN?>/mailbox?folder=Trash" class="nav-link">
					<i class="far fa-trash-alt"></i> Удалённые
				</a>
			</li>
		</ul>
	</div>
</div>
<div class="card">
	<div class="card-header">
		<h3 class="card-title">Labels</h3>
		<div class="card-tools">
			<button type="button" class="btn btn-tool" data-card-widget="collapse">
				<i class="fas fa-minus"></i>
			</button>
		</div>
	</div>
	<div class="card-body p-0">
		<ul class="nav nav-pills flex-column">
			<li class="nav-item">
				<a href="#" class="nav-link">
					<i class="far fa-circle text-danger"></i> Important
				</a>
			</li>
			<li class="nav-item">
				<a href="#" class="nav-link">
					<i class="far fa-circle text-warning"></i> Promotions
				</a>
			</li>
			<li class="nav-item">
				<a href="#" class="nav-link">
					<i class="far fa-circle text-primary"></i> Social
				</a>
			</li>
		</ul>
	</div>
</div>