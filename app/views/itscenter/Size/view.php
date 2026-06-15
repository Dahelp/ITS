<!--prdt-starts-->
<div class="prdt">
    <div class="container">
        <!--start-breadcrumbs-->
        <nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active"><a href="<?=h($params->url_params);?>"><?=h($params->title);?></a></li>
                <li class="breadcrumb-item active"><?=h($find->value);?></li>
            </ol>
        </nav>
        <!--end-breadcrumbs-->

        <section class="align-items-center">
            <h1 class="h2 mb-3 mb-md-0 me-3">
                <?php
                if (!empty($find->seo_h1)) {
                    echo $find->seo_h1;
                } elseif (!empty($inseo->name)) {
                    echo \ishop\App::seoreplacefilter($inseo->name, $find->id);
                } else {
                    echo h($find->value);
                }
                ?>
            </h1>
        </section>

        <?php if (!empty($find->top_content)): ?>
            <?php $alt = 'Шина размером ' . $find->value . ', установленная на колесной технике'; ?>
            <div class="catalog-top-block mb-4">
                <?php if (!empty($find->img)): ?>
                    <div class="catalog-top-image">
                        <img
                            src="/images/filtrs/baseimg/<?=h($find->img);?>"
                            alt="<?=h($alt);?>"
                            loading="lazy">
                    </div>
                <?php endif; ?>

                <div class="catalog-top-text">
                    <?=$find->top_content;?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($technicsLinks)): ?>
            <div class="tech-links mb-4">
                <div class="h4 mb-2">Подходит для техники</div>
                <div class="tech-links__items">
                    <?php foreach ($technicsLinks as $t): ?>
                        <a class="tech-links__item" href="/technics/type/<?=h($t['alias']);?>">
                            <?=h($t['name']);?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="prdt-top">
            <div class="col-md-12">
                <?php if (!empty($products)): ?>
                    <div class="row g-0 mx-n2 product-one">
                        <?php $curr = \ishop\App::$app->getProperty('currency'); ?>
                        <?php foreach ($products as $product): ?>
                            <div class="col-xl-3 col-lg-6 col-md-4 col-sm-6 mb-3">
                                <?php new \app\widgets\product\Product($product, $curr, 'product_tpl.php'); ?>
                            </div>
                        <?php endforeach; ?>

                        <div class="clearfix"></div>

                        <div class="text-center">
                            <?php if ($pagination && $pagination->countPages > 1): ?>
                                <?=$pagination;?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($relatedSizes)): ?>
                        <div class="related-sizes">
                            <div class="h4">Также смотрят типоразмеры</div>
                            <div class="related-sizes__items">
                                <?php foreach ($relatedSizes as $r): ?>
                                    <a class="related-sizes__item" href="<?=h(\app\services\filters\FilterUrlHelper::buildBestCategoryFilterPath((int)$r['id'], (string)$r['alias'], (string)$r['url_params']));?>">
                                        <?=h($r['value']);?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($faqRows)): ?>
                        <div class="faq-block mb-4">
                            <div class="h4 mb-2">Вопросы и ответы</div>

                            <?php foreach ($faqRows as $f): ?>
                                <div class="faq-item mb-2">
                                    <div class="faq-q"><strong><?=h($f['question']);?></strong></div>
                                    <div class="faq-a"><?=nl2br(h($f['answer']));?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="alert alert-warning product-note">
                        <?php if (empty($params->notproduct)): ?>
                            В этой категории товаров пока нет...
                        <?php else: ?>
                            <?=$params->notproduct;?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($products)): ?>
                    <?php
                    $ids = [];
                    foreach ($products as $p) {
                        if (is_object($p) && isset($p->id)) {
                            $ids[] = (int)$p->id;
                        } elseif (is_array($p) && isset($p['id'])) {
                            $ids[] = (int)$p['id'];
                        }
                    }
                    $ids = array_values(array_filter($ids));
                    $values = implode(',', $ids);
                    ?>

                    <div class="catalog_text">
                        <?php
                        if (!empty($find->content)) {
                            echo $find->content;
                        } elseif (!empty($inseo->content) && $values !== '') {
                            echo \ishop\App::seoreplacetiposize($inseo->content, $values);
                        }
                        ?>
                    </div>
                <?php endif; ?>

            </div>
            <div class="clearfix"></div>
        </div>
    </div>
</div>
<!--product-end-->
