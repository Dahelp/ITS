<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">
        <nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item">
                    <a href="<?= PATH ?>"><i class="fas fa-home"></i><span class="visually-hidden">Главная</span></a>
                </li>
                <li class="breadcrumb-item active">Каталог техники</li>
            </ol>
        </nav>
    </div>
</div>
<!--end-breadcrumbs-->

<!--start-single-->
<div class="single contact technics-page">
    <div class="container">
        <div class="register-top heading">
            <h1>Каталог техники</h1>
        </div>
        <div class="technics-description mb-5">
            <h2 class="h3 mb-3">Как подобрать запчасти по типу техники</h2>
            <p>
                На этой странице вы можете подобрать <strong>шины, диски, камеры, ободные ленты и фильтры</strong> по типу вашей техники.
                Просто выберите нужную модель из списка — и вы перейдёте в каталог, где собраны все подходящие позиции.
                Вам <strong>не нужно знать точные размеры или артикулы</strong>, достаточно кликнуть по картинке с нужной машиной.
            </p>
            <p>
                Каталог охватывает широкий спектр машин: <strong>вилочные и фронтальные погрузчики</strong>,
                <strong>экскаваторы‑погрузчики</strong>, <strong>мини‑погрузчики</strong>, <strong>автогрейдеры</strong>,
                <strong>грунтовые катки</strong>, <strong>колёсные экскаваторы</strong>, <strong>квадроциклы</strong>,
                <strong>сельскохозяйственную, крановую, карьерную, портовую технику</strong>, <strong>садовые тракторы</strong>
                и многое другое.
            </p>
            <p>
                Подбор выполнен с учётом официальных рекомендаций производителей техники и проверен на совместимость,
                чтобы вы могли быть уверены в правильном выборе комплектующих. Мы постоянно обновляем ассортимент
                и добавляем новые позиции, поэтому здесь всегда актуальные предложения для вашей машины.
            </p>
        </div>
        <?php if (!empty($technics)): ?>
            <div class="row technics-grid g-4">
                <?php foreach ($technics as $tech): ?>
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                        <a href="<?= PATH ?>/technics/type/<?= h($tech['alias']) ?>"
                           title="<?= h($tech['name']) ?>"
                           class="technics-card-link">
                            <div class="technics-card technics-card--type">
                                <div class="technics-card__img">
                                    <img src="<?= PATH ?>/images/technics_type/baseimg/<?= h($tech['img']) ?>"
                                         alt="<?= h($tech['name']) ?>"
                                         title="<?= h($tech['name']) ?>">
                                </div>
                                <div class="technics-card__body">
                                    <div class="technics-card__title"><?= h($tech['name']) ?></div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<!--end-single-->
