<?php
$folderStats = $folderStats ?? [];
$currentFolder = $currentFolder ?? 'inbox';
$folderUrl = static fn(string $folder) => $folder === 'inbox' ? ADMIN . '/mailbox' : ADMIN . '/mailbox?folder=' . rawurlencode($folder);
$folderCount = static fn(string $folder, string $key = 'total') => (int)($folderStats[$folder][$key] ?? 0);
$active = static fn(string $folder) => $currentFolder === $folder ? ' active' : '';
?>
<div class="d-flex mb-3">
    <a href="<?= ADMIN ?>/mailbox/compose" class="btn btn-primary btn-block mr-2 mb-0">Написать</a>
    <a href="<?= ADMIN ?>/mailbox" class="btn btn-primary mb-0" title="Обновить список">
        <i class="fas fa-sync-alt"></i>
    </a>
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
            <li class="nav-item">
                <a href="<?= $folderUrl('inbox') ?>" class="nav-link<?= $active('inbox') ?>">
                    <i class="fas fa-inbox"></i> Входящие
                    <?php if ($folderCount('inbox', 'unseen') > 0): ?>
                        <span class="badge bg-primary float-right"><?= $folderCount('inbox', 'unseen') ?></span>
                    <?php endif; ?>
                    <span class="float-right mr-2"><?= $folderCount('inbox') ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= $folderUrl('Sent') ?>" class="nav-link<?= $active('Sent') ?>">
                    <i class="far fa-envelope"></i> Отправленные
                    <span class="float-right"><?= $folderCount('Sent') ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= $folderUrl('Drafts') ?>" class="nav-link<?= $active('Drafts') ?>">
                    <i class="far fa-file-alt"></i> Черновики
                    <span class="float-right"><?= $folderCount('Drafts') ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= $folderUrl('Junk') ?>" class="nav-link<?= $active('Junk') ?>">
                    <i class="fas fa-filter"></i> Спам
                    <span class="float-right"><?= $folderCount('Junk') ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= $folderUrl('Trash') ?>" class="nav-link<?= $active('Trash') ?>">
                    <i class="far fa-trash-alt"></i> Удалённые
                    <span class="float-right"><?= $folderCount('Trash') ?></span>
                </a>
            </li>
        </ul>
    </div>
</div>
