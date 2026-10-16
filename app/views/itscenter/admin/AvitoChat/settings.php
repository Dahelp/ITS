<?php $escape = static function ($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }; ?>
<section class="content-header"><h1>Доступ к чату Авито</h1></section>
<section class="content"><div class="card"><div class="card-body">
    <p>Один аккаунт Авито. Отметьте сотрудников, которым разрешено читать переписку и отвечать покупателям. Список включает пользователей с доступом в админку.</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= $escape($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= $escape($csrf) ?>">
        <?php foreach ($managers as $manager): ?>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="managers[]" id="avito-manager-<?= (int)$manager['id'] ?>" value="<?= (int)$manager['id'] ?>" <?= $manager['enabled'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="avito-manager-<?= (int)$manager['id'] ?>"><?= $escape($manager['name']) ?> (<?= $escape($manager['email']) ?>)</label>
            </div>
        <?php endforeach; ?>
        <button class="btn btn-primary" type="submit">Сохранить доступ</button>
        <a class="btn btn-default" href="<?= ADMIN ?>/avito-chat">Перейти к чату</a>
    </form>
</div></div></section>
