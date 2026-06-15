<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">
        <nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item">
                    <a href="<?= PATH ?>"><i class="fas fa-home"></i></a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= PATH ?>/user/cabinet">Личный кабинет</a>
                </li>
                <li class="breadcrumb-item active">Экспорт товаров</li>
            </ol>
        </nav>
    </div>
</div>
<!--end-breadcrumbs-->

<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-start cab-inner">
            <div class="aiz-user-sidenav-wrap position-relative z-1 shadow-sm">
                <?php new \app\widgets\cabinet\Cabinet('cabinet_tpl.php'); ?>
            </div>

            <div class="aiz-user-panel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0 h6">Выгрузка товаров для вашего сайта</h5>
                    </div>

                    <div class="card-body">
                        <div class="blk-alert blk-alert-danger">
                            Необходима помощь в выгрузке товаров на вашем сайте?
                            Пишите на почту
                            <a href="mailto:oooitscenter@yandex.ru">oooitscenter@yandex.ru</a>
                            (доп. услуга).
                        </div>

                        <?php
                        $exportGroups = [
                            'Все товары' => [
                                'excel' => 'export-excel',
                                'csv'   => 'export-csv',
                                'xml'   => 'export-yml',
                            ],
                            'Индустриальные шины' => [
                                'excel' => 'export-excel-vseshiny',
                                'csv'   => 'export-csv-vseshiny',
                                'xml'   => 'export-yml-vseshiny',
                            ],
                            'Шины для квадроциклов' => [
                                'excel' => 'export-excel-kvadroshiny',
                                'csv'   => 'export-csv-kvadroshiny',
                                'xml'   => 'export-yml-kvadroshiny',
                            ],
                            'Диски на технику' => [
                                'excel' => 'export-excel-diski',
                                'csv'   => 'export-csv-diski',
                                'xml'   => 'export-yml-diski',
                            ],
                            'Фильтры для спецтехники' => [
                                'excel' => 'export-excel-filtry',
                                'csv'   => 'export-csv-filtry',
                                'xml'   => 'export-yml-filtry',
                            ],
                            'Камеры и ободные ленты' => [
                                'excel' => 'export-excel-kamery',
                                'csv'   => 'export-csv-kamery',
                                'xml'   => 'export-yml-kamery',
                            ],
                        ];

                        $formatTitles = [
                            'excel' => 'Прайс Excel (.xlsx)',
                            'csv'   => 'Прайс CSV (.csv)',
                            'xml'   => 'Прайс YML (.xml)',
                        ];

                        $cronData = [];
                        foreach ($exportGroups as $groupItems) {
                            foreach ($groupItems as $type => $urlParam) {
                                if (!isset($cronData[$urlParam])) {
                                    $cronData[$urlParam] = \R::findOne('cron', 'url_params = ?', [$urlParam]);
                                }
                            }
                        }
                        ?>

                        <div class="price_excel">
                            <?php foreach ($exportGroups as $groupTitle => $groupItems): ?>
                                <div class="price_ex1">
                                    <h4><?= h($groupTitle) ?></h4>
                                    <ul>
                                        <?php foreach ($groupItems as $type => $urlParam): ?>
                                            <?php
                                            $cron = $cronData[$urlParam] ?? null;
                                            $dateUpdate = !empty($cron['date_update']) ? $cron['date_update'] : 'не сформирован';
                                            $urlDownload = !empty($cron['url_download']) ? PATH . '/cron/' . ltrim($cron['url_download'], '/') : '';
                                            ?>
                                            <li>
                                                <strong><?= $formatTitles[$type] ?> от <?= h($dateUpdate) ?></strong>
                                                <ol>
                                                    <?php if ($urlDownload): ?>
                                                        <li><a href="<?= h($urlDownload) ?>">Скачать</a></li>
                                                        <li>URL: <?= h($urlDownload) ?></li>
                                                    <?php else: ?>
                                                        <li>Файл пока недоступен</li>
                                                    <?php endif; ?>
                                                </ol>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>