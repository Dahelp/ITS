<?php
$csrfToken = $_SESSION['signup_token'] ?? '';
$formData = $_SESSION['form_data'] ?? [];

$nameValue  = isset($formData['name']) ? h($formData['name']) : '';
$emailValue = isset($formData['email']) ? h($formData['email']) : '';
?>

<style>
.register-shell {
    background: #fff;
    border-radius: 24px;
    padding: 32px 28px;
    box-shadow: 0 10px 30px rgba(44, 62, 80, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.04);
    max-width: 1440px;
    margin: 0 auto 30px;
}

@media (min-width: 992px) {
    .register-shell {
        padding: 40px 44px 44px;
    }
}

.register-title {
    margin-bottom: 28px;
    font-size: 2.2rem;
    font-weight: 700;
    color: #111;
}

.register-form-wrap {
    width: 100%;
    max-width: 680px;
}

.register-form-wrap .form-label {
    display: inline-block;
    margin-bottom: 10px;
    font-weight: 700;
    color: #243b5a;
}

.register-form-wrap .form-control {
    min-height: 52px;
    border-radius: 14px;
    border: 1px solid #d9e0e8;
    box-shadow: none;
    padding: 0 16px;
    font-size: 16px;
    background: #fff;
}

.register-form-wrap .form-control:focus {
    border-color: #c0392b;
    box-shadow: 0 0 0 0.2rem rgba(192, 57, 43, 0.12);
}

.register-form-wrap .form-text {
    margin-top: 8px;
    color: #6b7785;
    font-size: 14px;
}

.register-agree {
    font-size: 15px;
    line-height: 1.55;
    color: #243b5a;
}

.register-agree a {
    color: #d6402b;
    text-decoration: none;
}

.register-agree a:hover {
    text-decoration: underline;
}

.register-submit {
    min-height: 48px;
    padding: 0 22px;
    border: 0;
    border-radius: 14px;
    background: linear-gradient(135deg, #c0392b 0%, #e74c3c 100%);
    font-weight: 700;
    font-size: 16px;
}

.register-submit:hover,
.register-submit:focus {
    background: linear-gradient(135deg, #b33426 0%, #d94333 100%);
}

.hp-field-wrap {
    position: absolute;
    left: -9999px;
    top: -9999px;
    width: 1px;
    height: 1px;
    overflow: hidden;
}

@media (max-width: 767px) {
    .register-shell {
        padding: 24px 18px;
        border-radius: 18px;
    }

    .register-title {
        font-size: 1.8rem;
        margin-bottom: 22px;
    }

    .register-form-wrap {
        max-width: 100%;
    }
}

.register-submit:disabled,
.register-submit.disabled {
    opacity: .6;
    cursor: not-allowed;
    pointer-events: none;
}
</style>

<div class="prdt">
    <div class="container">
        <nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item">
                    <a href="<?= PATH ?>"><i class="fas fa-home"></i></a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Регистрация</li>
            </ol>
        </nav>

        <div class="register-shell">
            <h1 class="register-title">Регистрация нового клиента</h1>

            <div class="register-form-wrap">
                <form method="post" action="<?= PATH ?>/user/signup" id="signup" role="form">
                    <input type="hidden" name="signup_token" value="<?= h($csrfToken) ?>">

                    <div class="hp-field-wrap" aria-hidden="true">
                        <label for="hp_field">Ваш сайт</label>
                        <input type="text" name="hp_field" id="hp_field" autocomplete="off" tabindex="-1">
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label" for="name">Имя</label>
                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            id="name"
                            placeholder="Введите имя"
                            value="<?= $nameValue ?>"
                            maxlength="100"
                            autocomplete="name"
                            required
                        >
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label" for="email">Email</label>
                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            id="email"
                            placeholder="Введите email"
                            value="<?= $emailValue ?>"
                            maxlength="150"
                            autocomplete="email"
                            inputmode="email"
                            required
                        >
                    </div>

                    <div class="form-group mb-4">
                        <label class="form-label" for="password">Пароль</label>
                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            id="password"
                            placeholder="Введите пароль"
                            minlength="6"
                            maxlength="255"
                            autocomplete="new-password"
                            required
                        >
                        <div class="form-text">Минимум 6 символов.</div>
                    </div>

                    <div class="form-group mb-4">
                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="agree_personal_data"
                                id="agree_personal_data"
                                value="1"
                                required
                            >
                            <label class="form-check-label register-agree" for="agree_personal_data">
                                Я согласен(на) с
                                <a href="<?= PATH ?>/pages/privacy" target="_blank" rel="noopener noreferrer">
                                    Политикой конфиденциальности
                                </a>
                                и даю согласие на обработку персональных данных
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary register-submit">
                        Зарегистрироваться
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('signup');
    if (!form) return;

    const submitBtn = form.querySelector('button[type="submit"]');
    const requiredFields = form.querySelectorAll('input[required]');

    function updateButtonState() {
        submitBtn.disabled = !form.checkValidity();
    }

    requiredFields.forEach(function(field) {
        field.addEventListener('input', updateButtonState);
        field.addEventListener('change', updateButtonState);
    });

    updateButtonState();
});
</script>
<?php unset($_SESSION['form_data']); ?>