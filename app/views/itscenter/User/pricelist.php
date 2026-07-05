<?php
function urlimagesbase64($path) {
    if (!is_file($path)) {
        return '';
    }
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);
    return 'data:image/' . $type . ';base64,' . base64_encode($data);
}

$logo = urlimagesbase64(ROOT . '/public/images/Logo_round.jpg');
$logos = urlimagesbase64(ROOT . '/public/images/logo-2.png');
$pdfDate = \ishop\App::contdate(date('Y-m-d'));

$pdfRows = [];
$pdfWidths = [];

if (!empty($product)) {
    if (($company['tip'] ?? '') == '2') {
        $pdfRows[] = ['Артикул', 'Производитель', 'Модель', 'Наименование', 'Наличие', 'Опт', 'Розница'];

        foreach ($product as $prod) {
            $ucompany = \R::getRow(
                'SELECT company.tip, company_typeprice.znachenie
                 FROM company, company_typeprice
                 WHERE company.id = company_typeprice.company_id
                   AND company.user_id = ?
                   AND company_typeprice.category_id = ?',
                [$_SESSION['user']['id'], $prod['category_id']]
            );

            $optValue = '';
            if (($ucompany['tip'] ?? '') === '2') {
                if (($ucompany['znachenie'] ?? '') === '') {
                    $optValue = $prod['opt_price'];
                } else {
                    $price_nds = round($prod['price'] - ($prod['price'] / 1.2), 0) * 6;
                    $price_opt = $price_nds - (($price_nds / 100) * $ucompany['znachenie']);
                    $optValue = round($price_opt / 6) * 6;
                }
            }

            $pdfRows[] = [
                (string)($prod['article'] ?? ''),
                (string)($prod['vendor'] ?? ''),
                (string)($prod['model'] ?? ''),
                (string)($prod['name'] ?? ''),
                (string)($prod['quantity'] ?? ''),
                (string)$optValue,
                (string)($prod['price'] ?? ''),
            ];
        }

        $pdfWidths = [45, 75, 45, '*', 45, 45, 55];
    } else {
        $pdfRows[] = ['Артикул', 'Производитель', 'Модель', 'Наименование', 'Наличие', 'Розница'];

        foreach ($product as $prod) {
            $pdfRows[] = [
                (string)($prod['article'] ?? ''),
                (string)($prod['vendor'] ?? ''),
                (string)($prod['model'] ?? ''),
                (string)($prod['name'] ?? ''),
                (string)($prod['quantity'] ?? ''),
                (string)($prod['price'] ?? ''),
            ];
        }

        $pdfWidths = [45, 75, 45, '*', 45, 60];
    }
}
?>

<script>
function Selected(a) {
    var label = a.value;
    if (label == 1) {
        document.getElementById("Block1").style.display = 'block';
        document.getElementById("Block2").style.display = 'none';
        document.getElementById("Block3").style.display = 'none';
    } else if (label == 2) {
        document.getElementById("Block1").style.display = 'none';
        document.getElementById("Block2").style.display = 'block';
        document.getElementById("Block3").style.display = 'none';
    } else if (label == 3) {
        document.getElementById("Block1").style.display = 'none';
        document.getElementById("Block2").style.display = 'none';
        document.getElementById("Block3").style.display = 'block';
    } else if (label == 4) {
        document.getElementById("Block1").style.display = 'block';
        document.getElementById("Block2").style.display = 'block';
        document.getElementById("Block3").style.display = 'none';
    } else {
        document.getElementById("Block1").style.display = 'none';
        document.getElementById("Block2").style.display = 'none';
        document.getElementById("Block3").style.display = 'none';
    }
}
</script>

<!--start-breadcrumbs-->
<div class="breadcrumbs">
    <div class="container">
        <nav class="mb-4 breadcrumb-blok" aria-label="breadcrumb">
            <ol class="breadcrumb flex-lg-nowrap">
                <li class="breadcrumb-item"><a href="<?= PATH ?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item"><a href="<?= PATH ?>/user/cabinet">Личный кабинет</a></li>
                <li class="breadcrumb-item active">Прайс-лист</li>
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
                        <h5 class="mb-0 h6">Прайс-лист</h5>
                    </div>

                    <div class="card-body">
                        <form action="<?= PATH ?>/user/pricelist" method="post" data-toggle="validator">
                            <div class="box-body">
                                <div class="form-group has-feedback mb-3">
                                    <label for="format">Формат</label>
                                    <select id="format" class="form-control" name="format">
                                        <option value="" selected="selected">Выберите формат</option>
                                        <option value="1">PDF</option>
                                    </select>
                                </div>

                                <div class="form-group has-feedback mb-3">
                                    <label for="actSelect">Вывод данных</label>
                                    <select id="actSelect" class="form-control" name="actSelect" aria-required="true" onChange="Selected(this)">
                                        <option value="" selected="selected">Выберите что выгружать</option>
                                        <option value="5">Все товары</option>
                                        <option value="1">Определённую категорию</option>
                                        <option value="2">По производителю</option>
                                        <option value="4">Категория и производитель</option>
                                        <option value="3">Артикул товара</option>
                                    </select>
                                </div>

                                <div id="Block1" style="display: none;" class="form-group has-feedback mb-3">
                                    <label for="category_id">Категория товаров</label>
                                    <select class="form-control" name="category_id" id="category_id">
                                        <option value="" selected="selected">Выберите категорию</option>
                                        <option value="1">Индустриальные шины</option>
                                        <option value="2">Шины для квадроциклов</option>
                                        <option value="25">Камеры, ободные ленты, уплотнительные кольца</option>
                                        <option value="3">Фильтры</option>
                                        <option value="4">Диски</option>
                                    </select>
                                </div>

                                <div id="Block2" style="display: none;" class="form-group has-feedback mb-3">
                                    <label for="brand_id">Производитель</label>
                                    <select id="brand_id" class="form-control" name="brand_id">
                                        <option value="" selected="selected">Выберите производителя</option>
                                        <option value="1">EKKA</option>
                                        <option value="2">CST</option>
                                        <option value="3">SUPERGUIDER</option>
                                        <option value="4">Forerunner</option>
                                        <option value="5">SUN.F</option>
                                    </select>
                                </div>

                                <div id="Block3" style="display: none;" class="form-group has-feedback mb-3">
                                    <label for="article">Артикул товара</label>
                                    <input class="form-control" type="text" name="article" placeholder="Артикул товара">
                                </div>
                            </div>

                            <div class="box-footer">
                                <button type="submit" class="btn btn-primary">Создать выгрузку товаров</button>
                            </div>
                        </form>

                        <?php if (!empty($product)): ?>
                            <div class="table-responsive mt-3">
                                <button class="btn-none" id="btnpdf" type="button">
                                    <i class="fad fa-file-pdf"></i>
                                    Прайс-лист PDF от <?= \ishop\App::contdate(date("Y-m-d")); ?>
                                </button>
                            </div>
                        <?php else: ?>
                            <p class="text-danger mt-3 mb-0">Прайс-лист пока не сформирован.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="/js/pdfmake.js"></script>
<script src="/js/vfs_fonts.js"></script>

<?php if (!empty($product)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('btnpdf');
    if (!btn) return;

    btn.addEventListener('click', function (e) {
        e.preventDefault();

        if (typeof pdfMake === 'undefined') {
            alert('pdfMake не подключён');
            return;
        }

        var content = [];

        var headerColumns = [];

        <?php if (!empty($logo)): ?>
        headerColumns.push({
            image: <?= json_encode($logo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            width: 70
        });
        <?php endif; ?>

        headerColumns.push([
            {
                text: 'Общество с ограниченной ответственностью «ИТС-Центр»',
                fontSize: 13,
                alignment: 'center',
                margin: [0, 0, 0, 8],
                bold: true
            },
            {
                text: '142117, Московская область, г. Подольск, деревня Коледино, ул. Троицкая, д.1Г, стр.1, помещение В-348/49\nтел. +7 (495) 424-98-90, электронная почта: info@its50.ru',
                fontSize: 8,
                alignment: 'center'
            }
        ]);

        content.push({
            columns: headerColumns,
            margin: [0, 0, 0, 20]
        });

        content.push({
            text: <?= json_encode('Прайс-лист от ' . $pdfDate, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            fontSize: 15,
            bold: true,
            alignment: 'center',
            margin: [0, 0, 0, 20]
        });

        var body = <?= json_encode($pdfRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

        var styledBody = body.map(function(row, rowIndex) {
            return row.map(function(cell) {
                return {
                    text: cell,
                    fontSize: 8,
                    bold: rowIndex === 0
                };
            });
        });

        content.push({
            layout: 'lightHorizontalLines',
            table: {
                headerRows: 1,
                widths: <?= json_encode($pdfWidths, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                body: styledBody
            }
        });

        var docDefinition = {
            pageSize: 'A4',
            pageOrientation: 'landscape',
            pageMargins: [20, 20, 20, 40],
            info: {
                title: 'Прайс-лист товаров',
                author: 'ИТС-Центр',
                subject: 'Прайс-лист',
                keywords: 'прайс-лист, товары'
            },
            content: content
        };

        <?php if (!empty($logos)): ?>
        docDefinition.footer = function() {
            return {
                margin: [20, 0, 20, 0],
                columns: [
                    {
                        image: <?= json_encode($logos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                        width: 90
                    },
                    {
                        text: 'its-center.ru',
                        alignment: 'right',
                        fontSize: 9,
                        margin: [0, 10, 0, 0]
                    }
                ]
            };
        };
        <?php endif; ?>

        try {
            pdfMake.createPdf(docDefinition).download('pricelist.pdf');
        } catch (err) {
            console.error(err);
            alert('Ошибка генерации PDF. Открой консоль F12.');
        }
    });
});
</script>
<?php endif; ?>
