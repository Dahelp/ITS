								<div class="bg-light rounded-3 py-4 px-4 mt-3 js-promo-block">
									<div class="fw-semibold mb-2">Промокоды и скидки</div>

									<?php $promoApplied = trim((string)($_SESSION['promocart'] ?? '')); ?>

									<div class="input-group">
										<input
										type="text"
										class="form-control js-promo-code" aria-label="Промокод" autocomplete="off"
										maxlength="100"
										placeholder="Введите промокод"
										value="<?= htmlspecialchars($promoApplied, ENT_QUOTES, 'UTF-8') ?>"
										<?= $promoApplied !== '' ? 'readonly' : '' ?>
										>

										<button
										class="btn btn-outline-secondary js-apply-promo"
										type="button"
										
										<?= $promoApplied !== '' ? 'disabled' : '' ?>
										>
										Применить
										</button>

										<button
										class="btn btn-outline-danger js-clear-promo"
										type="button"
										
										style="<?= $promoApplied !== '' ? '' : 'display:none' ?>"
										>
										Убрать
										</button>
									</div>

									<div class="small text-muted mt-2">
										Промокод не суммируется со скидками по акциям и на комплекты. Итоговая скидка отображается после применения.
									</div>

									<div
										class="small mt-2 js-promo-message" role="status" aria-live="polite"
										
										style="<?= $promoApplied !== '' ? '' : 'display:none' ?>; color:#16a34a;"
									>
										Промокод применён
									</div>
                                    <div class="small text-success mt-2 js-promo-savings" hidden></div>
                                    <div class="small mt-2">
                                      <a href="<?= PATH ?>/promo/hochesh-2-skidki-na-ves-zakaz-zhmi" target="_blank" rel="noopener">Где взять промокод? Условия акции</a>
                                    </div>
                                    <?php if ($compusers && (int)$compusers->tip === 2): ?>
                                      <div class="small text-muted mt-2">Цены для вашей компании уже учтены в стоимости товаров.</div>
                                    <?php endif; ?>
                                </div>
