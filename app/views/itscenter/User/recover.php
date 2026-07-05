<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['recover_token'])) {
    $_SESSION['recover_token'] = bin2hex(random_bytes(32));
}
?>

<!--prdt-starts-->
<div class="prdt">
    <div class="container">

        <div class="register-main">
            <div class="col-md-6 account-left">
                <form method="post" action="user/recover" id="recover" role="form" data-toggle="validator">
                    <input type="hidden" name="recover_token"
                           value="<?= htmlspecialchars($_SESSION['recover_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <!-- honeypot -->
                    <div style="position:absolute; left:-9999px; top:-9999px;">
                        <label>Ваш сайт
                            <input type="text" name="hp_field" autocomplete="off">
                        </label>
                    </div>

                    <div class="form-group has-feedback mb-3">
                        <label class="form-label" for="email">Электронная почта</label>
                        <input type="email" name="email" class="form-control" id="email" placeholder="Электронная почта" required>
                        <span class="glyphicon form-control-feedback" aria-hidden="true"></span>
                    </div>
                    <button type="submit" class="btn btn-primary mb-3">Отправить</button>
                </form>
                <?php if(isset($_SESSION['form_data'])) unset($_SESSION['form_data']); ?>
            </div>
        </div>

    </div>
</div>
<!--product-end-->
