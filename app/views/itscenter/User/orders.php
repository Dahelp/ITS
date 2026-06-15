<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">
        <nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item"><a href="<?= PATH ?>/user/cabinet">Личный кабинет</a></li>
                <li class="breadcrumb-item active">История заказов</li>
            </ol>
        </nav>
    </div>
</div>
<!--end-breadcrumbs-->

<?php $curr = \ishop\App::$app->getProperty('currency'); ?>
<?php $order_prefix = \ishop\App::options('order_prefix'); ?>

<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-start cab-inner">
            <div class="aiz-user-sidenav-wrap position-relative z-1 shadow-sm">
                <?php new \app\widgets\cabinet\Cabinet('cabinet_tpl.php'); ?>
            </div>

            <div class="aiz-user-panel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0 h6">Заказы</h5>
                    </div>

                    <div class="card-body">
                        <?php if($orders): ?>
                            <div class="table-responsive">
                                <table class="table aiz-table mb-0 footable footable-1 breakpoint-xl">
                                    <thead>
                                        <tr class="footable-header">
                                            <th class="footable-first-visible" style="display: table-cell;">ID</th>
                                            <th data-breakpoints="md" style="display: table-cell;">Дата</th>
                                            <th data-breakpoints="md" style="display: table-cell;">Статус</th>
                                            <th style="display: table-cell;">Сумма</th>
                                            <th class="text-right footable-last-visible" style="display: table-cell;">Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach($orders as $order): ?>
                                        <?php
                                            $class = ((int)$order['status'] === 7) ? 'badge-danger' : 'badge-success';
                                        ?>
                                        <tr>
                                            <td class="footable-first-visible" style="display: table-cell;">
                                                <a href="<?= PATH ?>/user/order?id=<?= (int)$order['id']; ?>">
                                                    <?= htmlspecialchars($order['inv']); ?>
                                                </a>
                                            </td>
                                            <td style="display: table-cell;">
                                                <?= htmlspecialchars($order['date']); ?>
                                            </td>
                                            <td style="display: table-cell;">
                                                <span class="badge badge-inline <?= $class; ?>">
                                                    <?= htmlspecialchars($order['status_name'] ?? 'Неизвестно'); ?>
                                                </span>
                                            </td>
                                            <td style="display: table-cell;">
                                                <?= $curr['symbol_left']; ?> <?= number_format((float)$order['sum'], 2, '.', ' '); ?> <?= $curr['symbol_right']; ?>
                                            </td>
                                            <td class="text-right footable-last-visible" style="display: table-cell;">
                                                <a href="<?= PATH ?>/user/order?id=<?= (int)$order['id']; ?>" class="btn btn-soft-info btn-icon btn-circle btn-sm" title="Детали заказа">
                                                    <i class="far fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-center mt-4">
                                <?php if($pagination->countPages > 1): ?>
                                    <?= $pagination; ?>
                                <?php endif; ?>
                            </div>

                        <?php else: ?>
                            <p class="text-danger">Вы пока не совершали заказов.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>