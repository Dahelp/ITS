<?php
defined('WWW') or die('Access denied');
?>

<!-- Modal korzina -->
<div class="modal fade" id="exampleModalLive" tabindex="-1" aria-labelledby="exampleModalLiveLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <div class="modal-title" id="exampleModalLiveLabel">Корзина</div>

                <button type="button"
                        class="btn-close js-cart-modal-close"
                        data-bs-dismiss="modal"
                        aria-label="Закрыть"></button>
            </div>

            <div class="modal-body"></div>

            <div class="modal-footer">
                <button type="button"
                        class="btn btn-default js-cart-modal-close"
                        data-bs-dismiss="modal">
                    Продолжить покупки
                </button>

                <a href="/cart/view" class="btn btn-danger">
                    Оформить заказ
                </a>

                <button type="button" class="btn btn-primary js-clear-cart">
                    Очистить корзину
                </button>
            </div>

        </div>
    </div>
</div>


<!-- Modal katalog -->
<div class="modal fade" id="exampleModalCatalog" tabindex="-1" aria-labelledby="exampleModalCatalogLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content callback-modal">

            <div class="modal-header">
                <div class="modal-title" id="exampleModalCatalogLabel">Скачать каталог</div>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Закрыть"></button>
            </div>

            <form action="<?= PATH ?>/user/catalog" method="post" class="callback-form js-modal-validate" novalidate>
                <div class="modal-body">

                    <div class="mb-3">
                        <label for="catalog-name" class="form-label">Имя</label>
                        <input type="text" name="name" id="catalog-name" class="form-control" value="">
                    </div>

                    <div class="mb-3">
                        <label for="catalog-phone" class="form-label">
                            Телефон <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="phone"
                               id="catalog-phone"
                               class="form-control"
                               required
                               placeholder="+7 (___) ___-__-__">
                        <div class="invalid-feedback">Укажите телефон.</div>
                    </div>

                    <div class="mb-3">
                        <label for="catalog-email" class="form-label">
                            Эл. почта <span class="text-danger">*</span>
                        </label>
                        <input type="email"
                               name="email"
                               id="catalog-email"
                               class="form-control"
                               required>
                        <div class="invalid-feedback">Укажите корректный email.</div>
                    </div>

                    <div class="mb-3">
                        <label for="catalog-comment" class="form-label">Комментарий</label>
                        <textarea name="comment"
                                  id="catalog-comment"
                                  class="form-control"
                                  rows="4"
                                  placeholder="Напишите комментарий"></textarea>
                    </div>

                    <div class="form-check callback-agree">
                        <input class="form-check-input"
                               type="checkbox"
                               value="1"
                               id="catalog-agree"
                               name="agree"
                               required>

                        <label class="form-check-label" for="catalog-agree">
                            Я согласен(на) с
                            <a href="<?= PATH ?>/pages/privacy" target="_blank" rel="noopener">
                                Политикой конфиденциальности
                            </a>
                            и даю согласие на обработку персональных данных
                        </label>

                        <div class="invalid-feedback d-block">Нужно подтвердить согласие.</div>
                    </div>

                    <input type="hidden" name="title" value="Запрос каталога">

                </div>

                <div class="modal-footer justify-content-start">
                    <button type="submit" class="btn btn-danger callback-submit" disabled>
                        Отправить
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>


<!-- Modal zvonok callback -->
<div class="modal fade" id="exampleModalZvonok" tabindex="-1" aria-labelledby="exampleModalZvonokLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content callback-modal">

            <div class="modal-header">
                <div class="modal-title" id="exampleModalZvonokLabel">Заказать обратный звонок</div>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Закрыть"></button>
            </div>

            <form action="<?= PATH ?>/user/zvonok" method="post" class="callback-form js-modal-validate" novalidate>
                <div class="modal-body">

                    <div class="mb-3">
                        <label for="callback-name" class="form-label">Имя</label>
                        <input type="text" name="name" id="callback-name" class="form-control" value="">
                    </div>

                    <div class="mb-3">
                        <label for="callback-phone" class="form-label">
                            Телефон <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="phone"
                               id="callback-phone"
                               class="form-control"
                               required
                               placeholder="+7 (___) ___-__-__">
                        <div class="invalid-feedback">Укажите телефон.</div>
                    </div>

                    <div class="mb-3">
                        <label for="callback-email" class="form-label">Эл. почта</label>
                        <input type="email"
                               name="email"
                               id="callback-email"
                               class="form-control"
                               value="">
                        <div class="invalid-feedback">Укажите корректный email.</div>
                    </div>

                    <div class="mb-3">
                        <label for="callback-comment" class="form-label">Комментарий</label>
                        <textarea name="comment"
                                  id="callback-comment"
                                  class="form-control"
                                  rows="4"
                                  placeholder="Напишите тему звонка. Оставьте комментарий"></textarea>
                    </div>

                    <div class="form-check callback-agree">
                        <input class="form-check-input"
                               type="checkbox"
                               value="1"
                               id="callback-agree"
                               name="agree"
                               required>

                        <label class="form-check-label" for="callback-agree">
                            Я согласен(на) с
                            <a href="<?= PATH ?>/pages/privacy" target="_blank" rel="noopener">
                                Политикой конфиденциальности
                            </a>
                            и даю согласие на обработку персональных данных
                        </label>

                        <div class="invalid-feedback d-block">Нужно подтвердить согласие.</div>
                    </div>

                    <input type="hidden" name="title" value="Заказ обратного звонка">

                </div>

                <div class="modal-footer justify-content-start">
                    <button type="submit" class="btn btn-danger callback-submit" disabled>
                        Отправить
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>


<!-- Modal login -->
<div class="modal fade" id="Modallogin" tabindex="-1" aria-labelledby="ModalloginLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content callback-modal login-modal">

            <div class="modal-header">
                <div class="modal-title" id="ModalloginLabel">Вход</div>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Закрыть"></button>
            </div>

            <form action="/user/login" method="post" class="callback-form js-modal-validate" novalidate>
                <div class="modal-body">

                    <div style="position:absolute; left:-9999px; top:-9999px;">
                        <label>
                            Ваш сайт
                            <input type="text" name="hp_field" autocomplete="off">
                        </label>
                    </div>

                    <div class="mb-3">
                        <label for="login-email" class="form-label">
                            E-Mail <span class="text-danger">*</span>
                        </label>
                        <input type="email"
                               name="email"
                               id="login-email"
                               class="form-control"
                               placeholder="Укажите ваш e-mail"
                               required>
                        <div class="invalid-feedback">Укажите корректный email.</div>
                    </div>

                    <div class="mb-3">
                        <label for="login-password" class="form-label">
                            Пароль <span class="text-danger">*</span>
                        </label>
                        <input type="password"
                               name="password"
                               id="login-password"
                               class="form-control"
                               required>
                        <div class="invalid-feedback">Введите пароль.</div>
                    </div>

                    <div class="mb-3 login-modal__recover">
                        <a href="/user/recover">Забыли пароль?</a>
                    </div>

                </div>

                <div class="modal-footer justify-content-start">
                    <button type="submit"
                            name="loginok"
                            value="<?= md5(date('Y-m-d')) ?>"
                            class="btn btn-danger callback-submit"
                            disabled>
                        Войти
                    </button>
                </div>

                <div class="login-modal__bottom">
                    <div class="login-modal__reg">
                        <a href="/user/signup" class="btn btn-outline-success">Регистрация</a>
                    </div>

                    <div class="login-modal__text">
                        Вам будет доступен личный кабинет, дисконтная программа, отслеживание заказов,
                        персональные данные и много других полезных функций.
                    </div>
                </div>

            </form>

        </div>
    </div>
</div>