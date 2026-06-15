<div class="breadcrumbs">
    <div class="container">
        <nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active"><?=h($type->page_name);?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="contents">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <?php if (!empty($groups)): ?>
                    <div class="register-top heading">
                        <h1><?=h($type->page_name);?></h1>
                    </div>

                    <div class="cont-inner">
                        <div class="group-filtr">
                            <?php foreach ($groups as $group): ?>
                                <?php
                                $groupUrl = \app\services\filters\FilterUrlHelper::buildBestCategoryFilterPath(
                                    (int)$group['id'],
                                    (string)$group['alias'],
                                    (string)$type->url_params
                                );
                                ?>
                                <div class="filtr-one">
                                    <a href="<?=h($groupUrl);?>" title="<?=h($group['value']);?>">
                                        <?php if (!empty($group['img'])): ?>
                                            <div class="filtrs-img">
                                                <img
                                                    src="images/filtrs/baseimg/<?=h($group['img']);?>"
                                                    alt="<?=h($group['value']);?>"
                                                    title="<?=h($group['value']);?>"
                                                    width="150"
                                                    height="120">
                                            </div>
                                        <?php endif; ?>
                                        <div class="filtrs-value"><?=h($group['value']);?></div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if (!empty($type->seo_content)): ?>
                        <div class="catalog_text col-md-12">
                            <?=$type->seo_content;?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
