<?php
$tell_zv = $tell_zv ?? \ishop\App::options('option_telefon');
$telefon_href = str_replace(['(', ')', ' ', '-'], '', (string)$tell_zv);

if (empty($_SESSION['callback_token'])) {
    $_SESSION['callback_token'] = bin2hex(random_bytes(32));
}
?>
<div class="decide decide--pro">
		<div class="container">
			<div class="container-big decide-cont fix-title psr v1">

				<div class="decide-left">
					<div class="decide-card decide-card--info">
						<div class="decide-badge">
							<span class="decide-badge__dot"></span>
							Бесплатная консультация
						</div>

						<div class="title rel fw4 tal fade_in decide-title">
							<span class="fwb">Не можете<br>определиться?</span>
							<div class="fl-dot"></div>
						</div>

						<div class="text24 msm decide-subtitle">
							Подберём товар под Вашу технику, задачи и бюджет. Без навязывания — только практичные рекомендации.
						</div>

						<div class="decide-trust-list">
							<div class="decide-trust-item">
								<div class="decide-trust-item__icon">
									<i class="fas fa-check"></i>
								</div>
								<div class="decide-trust-item__text">
									Подбор с учётом техники и условий эксплуатации
								</div>
							</div>

							<div class="decide-trust-item">
								<div class="decide-trust-item__icon">
									<i class="fas fa-check"></i>
								</div>
								<div class="decide-trust-item__text">
									Помощь по комплекту, доставке и совместимости
								</div>
							</div>

							<div class="decide-trust-item">
								<div class="decide-trust-item__icon">
									<i class="fas fa-check"></i>
								</div>
								<div class="decide-trust-item__text">
									Ответим по наличию, срокам и вариантам под бюджет
								</div>
							</div>
						</div>

						<div class="decide-contact-box">
							<div class="decide-contact-box__grid">
								<div class="decide-worktime">
									<div class="kr-text t2">
										<div class="kr-text__cir mrm"></div>
										<div class="tsm12">
											<b class="fw7">Звоните Пн–Пт</b><br>с 9:00 до 17:00
										</div>
									</div>
								</div>

								<div class="decide-contacts">
									<div class="phone-block row-vcenter">
										<div class="phone-block__ico col-center">
											<i class="fas fa-phone fa-flip-horizontal"></i>
										</div>
									<a href="tel:<?= h($telefon_href) ?>" class="phone-block__text black text-md link-hover fw7"><?= h($tell_zv) ?></a>
                                </div>

                                <div class="phone-block row-vcenter">
                                    <div class="phone-block__ico col-center">
                                        <i class="fas fa-at"></i>
                                    </div>
                                    <a href="mailto:info@its-center.ru" class="phone-block__text black text-md link-hover fw7">info@its-center.ru</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="decide-right">
                <div class="decide-card decide-card--callback">
                    <div class="decide-topline">
                        <div class="decide-topline__badge">Перезвоним и подскажем</div>
                    </div>

                    <div class="text24 msm decide-right-title">
                        Оставьте номер телефона — <span class="fw4">менеджер свяжется с Вами и поможет с подбором</span>
                    </div>

                    <div class="decide-right__layout">
                        <div class="decide-right__block">
                            <div class="decide-advantages">
                                <div class="decide-adv-card">
                                    <span class="main__y-ico col-center">
                                        <img src="images/ar-ico.png" data-src="images/ar-ico.png" alt="Помощь в подборе товара" class="ls-is-cached lazyloaded">
                                    </span>
                                    <div class="decide-adv-card__text">
                                        <b>Поможем выбрать</b><br>
                                        <span>под Ваш бюджет и задачи</span>
                                    </div>
                                </div>

                                <div class="decide-adv-card">
                                    <span class="main__y-ico col-center">
                                        <img src="images/ar-ico.png" data-src="images/ar-ico.png" alt="Консультация по выбору товара" class="ls-is-cached lazyloaded">
                                    </span>
                                    <div class="decide-adv-card__text">
                                        <b>Подскажем,</b><br>
                                        <span>на чём можно сэкономить без потери качества</span>
                                    </div>
                                </div>

                                <div class="decide-adv-card">
                                    <span class="main__y-ico col-center">
                                        <img src="images/ar-ico.png" data-src="images/ar-ico.png" alt="Расчёт комплекта и стоимости заказа" class="ls-is-cached lazyloaded">
                                    </span>
                                    <div class="decide-adv-card__text">
                                        <b>Рассчитаем комплект</b><br>
                                        <span>и итоговую стоимость заказа</span>
                                    </div>
                                </div>

                                <div class="decide-adv-card">
                                    <span class="main__y-ico col-center">
                                        <img src="images/ar-ico.png" data-src="images/ar-ico.png" alt="Информация о доставке и сроках" class="ls-is-cached lazyloaded">
                                    </span>
                                    <div class="decide-adv-card__text">
                                        <b>Сориентируем</b><br>
                                        <span>по доставке, срокам и сервису</span>
                                    </div>
                                </div>
                            </div>

                            <form action="/callback" class="form decide-right-form" method="post" data-toggle="validator" novalidate="true">
                                <input type="hidden" name="title" value="Заказать звонок">

                                <input type="hidden" name="callback_token"
                                    value="<?= htmlspecialchars($_SESSION['callback_token'], ENT_QUOTES, 'UTF-8') ?>">

                                <div style="position:absolute; left:-9999px; top:-9999px;">
                                    <label>Ваш сайт
                                        <input type="text" name="hp_field" autocomplete="off">
                                    </label>
                                </div>

                                <div class="decide-form-head">
                                    <div class="decide-form-head__title">Заказать звонок</div>
                                    <div class="decide-form-head__text">Обычно связываемся в рабочее время достаточно быстро</div>
                                </div>

                                <div class="form__row">
                                    <label class="input">
                                        <span class="input__title">Введите номер Вашего телефона:*</span>
                                        <input type="text" id="phone-input1" name="phone" class="input__input">
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-danger max btn-decide" id="btn-decide" disabled>
                                    <span class="tsm13 white fw7">Заказать звонок</span>
                                </button>

                                <div class="decide-form-note">
                                    Нажимая кнопку, Вы соглашаетесь на обработку персональных данных
                                </div>
                            </form>
                        </div>

                        <div class="decide-figure">
                            <img src="images/w.png" data-src="images/w.png" alt="Менеджер ИТС-Центр поможет с подбором товара" class="decide-right-wm ls-is-cached lazyloaded">
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>