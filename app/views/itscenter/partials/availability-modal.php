<?php
$availabilityItem = (($this->route['controller'] ?? '') === 'Product' && isset($product))
    ? $product : (object)['id' => 0, 'alias' => '', 'name' => ''];
?>
<!-- Modal Сообщить о поступлении -->
<div class="modal fade" id="ModalAvailability" tabindex="-1" aria-labelledby="ModalAvailabilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content callback-modal">
            <div class="modal-header">
                <div class="modal-title" id="ModalAvailabilityLabel">Сообщить о поступлении товара на электронную почту</div>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Закрыть"></button>
            </div>

            <form action="<?= PATH ?>/product/<?= h($availabilityItem->alias) ?>" method="post" class="callback-form js-modal-validate" novalidate>
                <div class="modal-body">
                    <div class="availability-product mb-3">
                        <div class="availability-product__name">
                            <?= h($availabilityItem->name) ?>
                        </div>

                        <input type="hidden" name="name_tovar" value="<?= h($availabilityItem->name) ?>">
                        <input type="hidden" name="product_id" value="<?= (int)$availabilityItem->id ?>">
                    </div>

                    <div class="mb-3">
                        <label for="availability-email-<?= (int)$availabilityItem->id ?>" class="form-label">
                            Электронная почта <span class="text-danger">*</span>
                        </label>

                        <input
                            type="email"
                            name="email_modal"
                            id="availability-email-<?= (int)$availabilityItem->id ?>"
                            class="form-control"
                            required
                            placeholder="Укажите адрес электронной почты"
                        >

                        <div class="invalid-feedback">Укажите корректный адрес электронной почты.</div>
                    </div>

                    <div class="form-check callback-agree">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            value="pk"
                            id="availability-agree-<?= (int)$availabilityItem->id ?>"
                            name="politika"
                            required
                        >

                        <label class="form-check-label" for="availability-agree-<?= (int)$availabilityItem->id ?>">
                            Я принимаю
                            <a href="<?= PATH ?>/pages/privacy" target="_blank" rel="noopener">
                                Политику конфиденциальности
                            </a>
                            и даю
                            <a href="<?= PATH ?>/pages/personal-data-consent" target="_blank" rel="noopener">
                                согласие на обработку персональных данных
                            </a>
                        </label>

                        <div class="invalid-feedback d-block">Нужно подтвердить согласие.</div>
                    </div>

                    <input type="hidden" name="availability" value="<?= md5(date('Y-m-d')) ?>">
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

