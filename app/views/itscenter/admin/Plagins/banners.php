<!-- Header -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1 class="m-0">Баннеры акций</h1></div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="<?=ADMIN;?>">Главная</a></li>
          <li class="breadcrumb-item"><a href="<?=ADMIN;?>/plagins">Компоненты</a></li>
          <li class="breadcrumb-item active">Баннеры</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<section class="content">
  <div class="row">
    <div class="col-md-12">
      <div class="menu_btn">
        <a href="<?=ADMIN;?>/plagins/banners-add" class="btn btn-primary">
          <i class="fa fa-fw fa-plus"></i> Добавить баннер
        </a>
      </div>

      <div class="card">
        <div class="card-header d-flex p-0">
          <h3 class="card-title p-3">Список баннеров</h3>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>ID</th>
                <th>Превью</th>
                <th>Название</th>
                <th>Ссылка</th>
                <th>Период</th>
                <th>Статус</th>
                <th>Позиция</th>
                <th>Действия</th>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($banners as $b): ?>
                <tr>
                  <td><?=$b['id']?></td>
                  <td style="min-width:140px">
                    <?php if(!empty($b['img'])): ?>
                      <img src="/images/banners/baseimg/<?=$b['img']?>" style="max-height:60px">
                    <?php endif; ?>
                    <?php if(!empty($b['img2'])): ?>
                      <img src="/images/banners/baseimg/<?=$b['img2']?>" style="max-height:60px;margin-left:6px">
                    <?php endif; ?>
                  </td>
                  <td><?=h($b['name']);?><br><small><?=h($b['description']);?></small></td>
                  <td><a href="<?=h($b['link_url']);?>" target="_blank"><?=h($b['link_url']);?></a></td>
                  <td>
                    <small>
                      c <?=h($b['start_at'] ?: '—')?> <br>
                      по <?=h($b['end_at']   ?: '—')?>
                    </small>
                  </td>
                  <td>
                    <?php
                      $badge = $b['hide']=='show' ? 'success' : ($b['hide']=='hide' ? 'secondary' : 'warning');
                      $text  = $b['hide']=='show' ? 'Активный' : ($b['hide']=='hide' ? 'Неактивный' : 'Закрыт от индексации');
                    ?>
                    <span class="badge badge-<?=$badge?>"><?=$text?></span>
                  </td>
                  <td><?=$b['position']?></td>
                  <td>
                    <a href="<?=ADMIN?>/plagins/banners-edit?id=<?=$b['id']?>"><i class="fas fa-pencil-alt"></i></a>
                    &nbsp;
                    <a class="text-danger" onclick="return confirm('Удалить баннер #<?=$b['id']?>?')"
                       href="<?=ADMIN?>/plagins/banners-delete?id=<?=$b['id']?>"><i class="fas fa-times-circle"></i></a>
                    &nbsp;
                    <a target="_blank" href="<?=h($b['link_url']);?>"><i class="fas fa-eye"></i></a>
                  </td>
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
