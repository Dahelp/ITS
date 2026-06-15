<?php $order_prefix = \ishop\App::options('order_prefix'); ?>
<?php $curr = \ishop\App::$app->getProperty('currency'); ?>

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
                <li class="breadcrumb-item">
                    <a href="<?= PATH ?>/user/orders">История заказов</a>
                </li>
                <li class="breadcrumb-item active">
                    Заказ №<?= htmlspecialchars($order_prefix . $id) ?>
                </li>
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
                        <h5 class="mb-0 h6">
                            Заказ №<?= htmlspecialchars($order_prefix . $id) ?>
                            <?php if (!empty($status['status_name'])): ?>
                                (<?= htmlspecialchars($status['status_name']) ?>)
                            <?php endif; ?>
                        </h5>
                    </div>

                    <div class="card-body">
                        <?php if (!empty($order) && $order_info): ?>

                            <?php
                            $summa_sum = 0;
                            $total_qty = 0;
                            ?>

                            <div class="table-responsive">
                                <table class="table aiz-table mb-0 footable footable-1 breakpoint-xl">
                                    <thead>
                                    <tr>
                                        <th style="width: 8%">Фото</th>
                                        <th style="width: 52%">Наименование</th>
                                        <th style="width: 20%">Количество</th>
                                        <th style="width: 20%">Цена</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($order as $item): ?>
                                        <?php
                                        $itemQty = (float)$item['qty'];
                                        $itemPrice = (float)$item['price'];
                                        $summa_sum += $itemPrice * $itemQty;
                                        $total_qty += $itemQty;
                                        ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($item['img'])): ?>
                                                    <img src="<?= PATH ?>/images/product/mini/<?= htmlspecialchars($item['img']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="max-width:60px; height:auto;">
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($item['alias'])): ?>
                                                    <a href="<?= PATH ?>/product/<?= htmlspecialchars($item['alias']) ?>">
                                                        <?= htmlspecialchars($item['name']) ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($item['name']) ?>
                                                <?php endif; ?>

                                                <?php if (!empty($item['article'])): ?>
                                                    <div class="text-muted small">
                                                        Артикул: <?= htmlspecialchars($item['article']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $itemQty ?></td>
                                            <td>
                                                <?= $curr['symbol_left']; ?>
                                                <?= number_format($itemPrice, 2, '.', ' '); ?>
                                                <?= $curr['symbol_right']; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                    <tr style="background:#fff">
                                        <td colspan="2"><strong>Итого:</strong></td>
                                        <td><strong><?= $total_qty ?> шт.</strong></td>
                                        <td>
                                            <strong>
                                                <?= $curr['symbol_left']; ?>
                                                <?= number_format($summa_sum, 2, '.', ' '); ?>
                                                <?= $curr['symbol_right']; ?>
                                            </strong>
                                        </td>
                                    </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <h4 class="mt-4">Дополнительная информация по заказу:</h4>
                            <p>
                                Дата создания: <?= htmlspecialchars($order_info['date']) ?><br />
                                Дата изменения: <?= !empty($order_info['update_at']) ? htmlspecialchars($order_info['update_at']) : '—' ?><br />
                                Статус заказа: <?= !empty($status['status_name']) ? htmlspecialchars($status['status_name']) : 'Неизвестно' ?><br />

                                <?php
                                $dostavka = null;
                                if (!empty($order_info['dostavka_id'])) {
                                    $dostavka = \R::findOne('dostavka', 'id = ?', [$order_info['dostavka_id']]);
                                }
                                if ($dostavka) {
                                    echo 'Способ доставки: ' . htmlspecialchars($dostavka['name']) . '<br />';
                                }
                                ?>

                                <?php
                                $transport = null;
                                if (!empty($order_info['transport_id'])) {
                                    $transport = \R::findOne('transport_company', 'id = ?', [$order_info['transport_id']]);
                                }
                                if ($transport) {
                                    echo 'Транспортная компания: ' . htmlspecialchars($transport['name']) . '<br />';
                                }
                                ?>

                                <?php
                                $branch = null;
                                if (!empty($order_info['branch_id'])) {
                                    $branch = \R::findOne('branch_office', 'branch_id = ?', [$order_info['branch_id']]);
                                }
                                if ($branch) {
                                    echo 'Адрес самовывоза: ' . htmlspecialchars($branch['name']) . '<br />';
                                }
                                ?>

                                <?php
                                $city = null;
                                if (!empty($order_info['city_id'])) {
                                    $city = \R::findOne('cities', 'id = ?', [$order_info['city_id']]);
                                }
                                if ($city) {
                                    echo 'Город: ' . htmlspecialchars($city['city_name']) . '<br />';
                                }
                                ?>

                                <?php if (!empty($order_info['address'])): ?>
                                    Адрес: <?= nl2br(htmlspecialchars($order_info['address'])) ?><br />
                                <?php endif; ?>

                                <?php if (!empty($order_info['note'])): ?>
                                    Комментарий: <?= nl2br(htmlspecialchars($order_info['note'])) ?><br />
                                <?php endif; ?>
                            </p>

                        <?php else: ?>
                            <p class="text-danger">Возможно заказ удалён или не существует.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>