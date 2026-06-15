<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Заказы</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= ADMIN; ?>">Главная</a></li>
                    <li class="breadcrumb-item"><a href="<?= ADMIN; ?>/order">Список заказов</a></li>
                    <li class="breadcrumb-item active">Создать заказ</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-12">
            <form action="<?= ADMIN; ?>/order/add" method="post" data-toggle="validator">
                <div class="card">
                    <div class="card-header d-flex p-0">
                        <h3 class="card-title p-3">Создать заказ</h3>
                    </div>

                    <div class="card-body">
                        <div class="box-body">
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label" for="client">Клиент</label>
                                <div class="col-sm-9">
                                    <select name="client" class="form-control client" onclick="tipClient(this)">
                                        <option value="" selected="selected">Выберите тип клиента</option>
                                        <option value="1">Новый</option>
                                        <option value="2">Зарегистрированный</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label" for="vid">Вид</label>
                                <div class="col-sm-9">
                                    <select name="vid" id="vid" class="form-control vid" onchange="changeAllRowsPrices()" onclick="vidUrlface(this)">
                                        <option value="" selected="selected">Выберите вид клиента</option>
                                        <option value="3">Физическое лицо</option>
                                        <option value="4">Юридическое лицо</option>
                                    </select>
                                </div>
                            </div>

                            <div id="user_contact" style="display:none">
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label" for="uname">Контакт</label>
                                    <div class="col-sm-9">
                                        <div id="tip_user_reg" style="display:none">
                                            <select name="user_id" class="form-control usercontact" id="user_id" data-placeholder="Выберите ФИО клиента"></select>
                                        </div>
                                        <div id="tip_user_new" style="display:none">
                                            <input name="user_name" class="form-control" placeholder="ФИО клиента" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script>
                                function tipClient(el) {
                                    var u = el.options[el.selectedIndex].value;
                                    document.getElementById("user_contact").style.display = (u > 0) ? "block" : "none";
                                    document.getElementById("tip_user_reg").style.display = (u == 2) ? "block" : "none";
                                    document.getElementById("tip_user_new").style.display = (u == 1) ? "block" : "none";
                                    document.getElementById("tip_comp_reg").style.display = (u == 1) ? "none" : "block";
                                    document.getElementById("tip_comp_new").style.display = (u == 1) ? "block" : "none";
                                }
                            </script>

                            <div id="vid_urlface" style="display:none">
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label" for="comp_id">Компания</label>
                                    <div class="col-sm-9">
                                        <div id="tip_comp_reg" style="display:none">
                                            <select name="comp_id" class="form-control companys" id="comp_id" data-placeholder="Выберите компанию по ИНН или названию"></select>
                                        </div>
                                        <div id="tip_comp_new" style="display:none">
                                            <input name="comp_name" id="comp_name" class="form-control" placeholder="Название компании" />
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label" for="tip">Тип взаимодействия <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <select class="form-control" id="tip" name="tip">
                                            <option value="">Выберите тип</option>
                                            <option value="1">Розничная торговля</option>
                                            <option value="2">Оптовая торговля</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label" for="url_address">Юр. адрес</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="url_address" id="url_address" value="<?= isset($_SESSION['form_data']['url_address']) ? h($_SESSION['form_data']['url_address']) : '' ?>">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label" for="postal_address">Почтовый адрес</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="postal_address" id="postal_address" value="<?= isset($_SESSION['form_data']['postal_address']) ? h($_SESSION['form_data']['postal_address']) : '' ?>">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label" for="ogrn">ОГРН, ОГРНИП <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="ogrn" id="ogrn" value="<?= isset($_SESSION['form_data']['ogrn']) ? h($_SESSION['form_data']['ogrn']) : '' ?>" placeholder="ОГРН 13 цифр, ОГРНИП 15 цифр" maxlength="15">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label" for="inn">ИНН <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="inn" id="inn" value="<?= isset($_SESSION['form_data']['inn']) ? h($_SESSION['form_data']['inn']) : '' ?>" placeholder="Юр.лицо 10 цифр, ИП 12 цифр" maxlength="12">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label" for="kpp">КПП</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="kpp" id="kpp" value="<?= isset($_SESSION['form_data']['kpp']) ? h($_SESSION['form_data']['kpp']) : '' ?>" placeholder="9 цифр" maxlength="9" data-error="КПП состоит из 9 цифр" data-minlength="9">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label" for="bik">БИК</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="bik" id="bik" value="<?= isset($_SESSION['form_data']['bik']) ? h($_SESSION['form_data']['bik']) : '' ?>" placeholder="9 цифр" maxlength="9" data-error="БИК состоит из 9 цифр" data-minlength="9">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label" for="raschet">Расч. счёт</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="raschet" id="raschet" value="<?= isset($_SESSION['form_data']['raschet']) ? h($_SESSION['form_data']['raschet']) : '' ?>">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label" for="korschet">Кор. счёт</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="korschet" id="korschet" value="<?= isset($_SESSION['form_data']['korschet']) ? h($_SESSION['form_data']['korschet']) : '' ?>">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label" for="bank">Наименование банка</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="bank" id="bank" value="<?= isset($_SESSION['form_data']['bank']) ? h($_SESSION['form_data']['bank']) : '' ?>">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label" for="dir_name">Генеральный директор</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" name="dir_name" id="dir_name" value="<?= isset($_SESSION['form_data']['dir_name']) ? h($_SESSION['form_data']['dir_name']) : '' ?>">
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label" for="nds">Система налогообложения</label>
                                    <div class="col-sm-9">
                                        <select class="form-control" id="cnds" onchange="changeAllRowsPrices()" name="nds">
                                            <option value="">Выберите статус</option>
                                            <option value="1">с НДС</option>
                                            <option value="2">без НДС</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label" for="dogovor">Условия поставки</label>
                                    <div class="col-sm-9">
                                        <select class="form-control" id="dogovor" name="dogovor">
                                            <option value="">Выберите статус</option>
                                            <option value="1">Договор</option>
                                            <option value="2">Счёт-договор</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label" for="hide">Статус</label>
                                    <div class="col-sm-9">
                                        <select class="form-control" id="hide" name="hide">
                                            <option value="">Выберите статус</option>
                                            <option value="show">Активный</option>
                                            <option value="hide">Не активный</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <script>
                                function vidUrlface(el) {
                                    var u = el.options[el.selectedIndex].value;
                                    document.getElementById("vid_urlface").style.display = (u > 3) ? "block" : "none";
                                }
                            </script>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label" for="telefon">Телефон</label>
                                <div class="col-sm-9">
                                    <input type="text" name="telefon" class="form-control phonez" id="utelefon" placeholder="Телефон для связи" value="<?= isset($_SESSION['form_data']['telefon']) ? h($_SESSION['form_data']['telefon']) : '' ?>">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label" for="email">Email</label>
                                <div class="col-sm-9">
                                    <input type="text" name="email" class="form-control" id="uemail" placeholder="Email клиента" value="<?= isset($_SESSION['form_data']['email']) ? h($_SESSION['form_data']['email']) : '' ?>">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label" for="dostavka_id">Способ получения</label>
                                <div class="col-sm-9">
                                    <select name="dostavka_id" class="form-control" onclick="anotherCity(this)">
                                        <option value="" selected="selected">Выберите способ получения</option>
                                        <?php $dostavka = \R::getAll("SELECT * FROM dostavka WHERE hide='show'"); ?>
                                        <?php foreach ($dostavka as $ds): ?>
                                            <option value="<?= $ds["id"] ?>"><?= $ds["name"] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div id="another_transport" style="display:none">
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label" for="transport_id">Транспортная компания</label>
                                    <div class="col-sm-9">
                                        <select name="transport_id" class="form-control">
                                            <option value="" selected="selected">Выберите транспортную компанию</option>
                                            <?php $transport = \R::getAll("SELECT * FROM transport_company WHERE hide='show'"); ?>
                                            <?php foreach ($transport as $ts): ?>
                                                <option value="<?= $ts["id"] ?>"><?= $ts["name"] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div id="another_sklad" style="display:none">
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label" for="branch_id">Самовывоз</label>
                                    <div class="col-sm-9">
                                        <select name="branch_id" class="form-control">
                                            <option value="" selected="selected">Выберите место самовывоза</option>
                                            <?php $branch = \R::getAll("SELECT * FROM branch_office WHERE hide='show'"); ?>
                                            <?php foreach ($branch as $br): ?>
                                                <option value="<?= $br["branch_id"] ?>"><?= $br["branch_name"] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div id="another_city" style="display:none">
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label" for="city_id">Город доставки</label>
                                    <div class="col-sm-9">
                                        <select name="city_id" class="form-control">
                                            <option value="" selected="selected">Выберите город доставки</option>
                                            <?php $cities = \R::getAll("SELECT * FROM cities ORDER BY city_name"); ?>
                                            <?php foreach ($cities as $st): ?>
                                                <option value="<?= $st["city_id"] ?>"><?= $st["city_name"] ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div id="another_adress" style="display:none">
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label" for="address">Адрес доставки</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="address" class="form-control" id="address" placeholder="Адрес доставки товаров" value="<?= isset($_SESSION['form_data']['address']) ? h($_SESSION['form_data']['address']) : '' ?>">
                                    </div>
                                </div>
                            </div>

                            <script>
                                function anotherCity(el) {
                                    var v = el.options[el.selectedIndex].value;
                                    document.getElementById("another_city").style.display = (v > 1) ? "block" : "none";
                                    document.getElementById("another_sklad").style.display = (v == 1) ? "block" : "none";
                                    document.getElementById("another_transport").style.display = (v == 2) ? "block" : "none";
                                    document.getElementById("another_adress").style.display = (v == 3) ? "block" : "none";
                                }
                            </script>

                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label" for="note">Комментарий к заказу</label>
                                <div class="col-sm-9">
                                    <textarea class="form-control" name="note"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex p-0">
                        <h3 class="card-title p-3">Товары</h3>
                    </div>

                    <div class="card-body">
                        <div class="box-body table-responsive">
                            <table id="table_container" class="table_order">
                                <tr>
                                    <td style="width:2%;"><strong>#</strong></td>
                                    <td style="width:26%;padding: 0 0 0 10px;"><strong>Наименование</strong></td>
                                    <td style="width:5%;padding: 0 0 0 10px;"><strong>Артикул</strong></td>
                                    <td style="width:8%;padding: 0 0 0 10px;"><strong>Цена</strong></td>
                                    <td style="width:6%;padding: 0 0 0 10px;"><strong>Количество</strong></td>
                                    <td style="width:6%;padding: 0 0 0 10px;"><strong>Наличие</strong></td>
                                    <td style="width:6%;padding: 0 0 0 10px;"><strong>Резерв</strong></td>
                                    <td style="width:5%;padding: 0 0 0 10px;"><strong>Значение</strong></td>
                                    <td style="width:5%;padding: 0 0 0 10px;"></td>
                                    <td style="width:8%;padding: 0 0 0 10px;"><strong>Скидка</strong></td>
                                    <td style="width:8%;padding: 0 0 0 10px;"><strong>Цена со скидкой</strong></td>
                                    <td style="width:8%;padding: 0 0 0 10px;"><strong>Сумма скидки</strong></td>
                                    <td style="width:10%;padding: 0 0 0 10px;"><strong>Сумма</strong></td>
                                    <td style="width:6%;"></td>
                                </tr>
                            </table>

                            <br/>
                            <div style="float:right;padding:10px 10px 0 0">
                                <input type="button" value="Добавить позицию" class="btn btn-success" id="add_prod">
                            </div>

                            <div class="order-content">
                                <div class="order-container">
                                    <table class="order-table">
                                        <tbody>
                                            <tr class="table-row">
                                                <td>Количество позиций:</td>
                                                <td class="itogs-cont">
                                                    <span class="itogqty">0</span>
                                                </td>
                                            </tr>
                                            <tr class="table-row">
                                                <td>Сумма без скидки и налогов:</td>
                                                <td class="itogs-cont">
                                                    <span data-total="totalWithoutDiscount" class="sum_beznalog_skidki">0</span>
                                                    <span data-role="currency-wrapper" class="item-currency-symbol"><?= $curr['symbol_right']; ?></span>
                                                </td>
                                            </tr>
                                            <tr class="table-row">
                                                <td>Сумма доставки:</td>
                                                <td class="itogs-cont">
                                                    <span data-total="totalDelivery">0</span>
                                                    <span data-role="currency-wrapper" class="item-currency-symbol"><?= $curr['symbol_right']; ?></span>
                                                </td>
                                            </tr>
                                            <tr class="table-row">
                                                <td>Сумма скидки:</td>
                                                <td class="itogs-cont">
                                                    <span data-total="totalDiscount" id="amount" class="amount">0</span>
                                                    <span data-role="currency-wrapper" class="item-currency-symbol"><?= $curr['symbol_right']; ?></span>
                                                </td>
                                            </tr>
                                            <tr class="table-row">
                                                <td>Сумма без налога:</td>
                                                <td class="itogs-cont">
                                                    <span data-total="totalWithoutTax" class="sum_beznalog_itogo">0</span>
                                                    <span data-role="currency-wrapper" class="item-currency-symbol"><?= $curr['symbol_right']; ?></span>
                                                </td>
                                            </tr>
                                            <tr class="table-row">
                                                <td class="border-bottom pb-3">Сумма налога:</td>
                                                <td class="itogs-cont border-bottom pb-3">
                                                    <span data-total="totalTax" class="sum_nalog_itogo">0</span>
                                                    <span data-role="currency-wrapper" class="item-currency-symbol"><?= $curr['symbol_right']; ?></span>
                                                </td>
                                            </tr>
                                            <tr class="table-row">
                                                <td class="pt-3 total-itogs-cont">Общая сумма:</td>
                                                <td class="pt-3 itogs-cont total-itogs-cont">
                                                    <span data-total="totalCost" class="sum">0</span>
                                                    <span data-role="currency-wrapper" class="item-currency-symbol"><?= $curr['symbol_right']; ?></span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="order-container-info">
                                    <table class="order-table">
                                        <tbody>
                                            <tr class="table-row">
                                                <td>Вес, кг:</td>
                                                <td class="itogs-weight">
                                                    <span class="sum_weight">0</span>
                                                </td>
                                            </tr>
                                            <tr class="table-row">
                                                <td>Объём, м3:</td>
                                                <td class="itogs-volume">
                                                    <span class="sum_volume">0</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="box-footer">
                    <button type="submit" class="btn btn-primary btn_save">Добавить</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script type="text/javascript">
$(document).on('change', 'select.companys', function () {
    var id = $(".companys").val();

    $.ajax({
        url: adminpath + "/order/compinfo",
        data: {id: id},
        type: 'GET',
        dataType: 'json',
        success: function(res){
            $("#user_id").html("<option value=\"" + (res.uid || '') + "\" data-id=\"" + (res.uid || '') + "\" selected=\"selected\">" + (res.uname || '') + "</option>");
            document.getElementById("utelefon").value = res.utelefon || '';
            document.getElementById("uemail").value = res.uemail || '';

            if (res.cnds == 1) {
                $("#cnds").html("<option value=\"1\" selected=\"selected\">с НДС</option><option value=\"2\">без НДС</option>");
            } else if (res.cnds == 2) {
                $("#cnds").html("<option value=\"2\" selected=\"selected\">без НДС</option><option value=\"1\">с НДС</option>");
            } else {
                $("#cnds").html("<option value=\"\">Выберите статус</option><option value=\"1\">с НДС</option><option value=\"2\">без НДС</option>");
            }

            if (res.tip == 1) {
                $("#tip").html("<option value=\"1\" selected=\"selected\">Розничная торговля</option><option value=\"2\">Оптовая торговля</option>");
            } else if (res.tip == 2) {
                $("#tip").html("<option value=\"2\" selected=\"selected\">Оптовая торговля</option><option value=\"1\">Розничная торговля</option>");
            } else {
                $("#tip").html("<option value=\"\">Выберите тип</option><option value=\"1\">Розничная торговля</option><option value=\"2\">Оптовая торговля</option>");
            }

            document.getElementById("url_address").value = res.url_address || '';
            document.getElementById("postal_address").value = res.postal_address || '';
            document.getElementById("ogrn").value = res.ogrn || '';
            document.getElementById("inn").value = res.inn || '';
            document.getElementById("kpp").value = res.kpp || '';
            document.getElementById("bik").value = res.bik || '';
            document.getElementById("raschet").value = res.raschet || '';
            document.getElementById("korschet").value = res.korschet || '';
            document.getElementById("bank").value = res.bank || '';
            document.getElementById("dir_name").value = res.dir_name || '';
            document.getElementById("dogovor").value = res.dogovor || '';

            if (res.hide == "show") {
                $("#hide").html("<option value=\"show\" selected=\"selected\">Активный</option><option value=\"hide\">Не активный</option>");
            } else if (res.hide == "hide") {
                $("#hide").html("<option value=\"hide\" selected=\"selected\">Не активный</option><option value=\"show\">Активный</option>");
            } else {
                $("#hide").html("<option value=\"\">Выберите статус</option><option value=\"show\">Активный</option><option value=\"hide\">Не активный</option>");
            }

            changeAllRowsPrices();
        },
        error: function(){
            alert('Ошибка');
        }
    });
});

$(document).on('change', 'select.usercontact', function () {
    var id = $(".usercontact").val();

    $.ajax({
        url: adminpath + "/order/usercontact",
        data: {id: id},
        type: 'GET',
        dataType: 'json',
        success: function(res){
            document.getElementById("utelefon").value = res.utelefon || '';
            document.getElementById("uemail").value = res.uemail || '';
            document.getElementById("vid_urlface").style.cssText = "display: block;";
            document.getElementById("tip_comp_reg").style.cssText = "display: block;";
            $("#comp_id").html("<option value=\"" + (res.comp_id || '') + "\" data-id=\"" + (res.comp_id || '') + "\" selected=\"selected\">" + (res.comp_name || '') + "</option>");

            if (res.cnds == 1) {
                $("#cnds").html("<option value=\"1\" selected=\"selected\">с НДС</option><option value=\"2\">без НДС</option>");
            } else if (res.cnds == 2) {
                $("#cnds").html("<option value=\"2\" selected=\"selected\">без НДС</option><option value=\"1\">с НДС</option>");
            } else {
                $("#cnds").html("<option value=\"\">Выберите статус</option><option value=\"1\">с НДС</option><option value=\"2\">без НДС</option>");
            }

            if (res.tip == 1) {
                $("#tip").html("<option value=\"1\" selected=\"selected\">Розничная торговля</option><option value=\"2\">Оптовая торговля</option>");
            } else if (res.tip == 2) {
                $("#tip").html("<option value=\"2\" selected=\"selected\">Оптовая торговля</option><option value=\"1\">Розничная торговля</option>");
            } else {
                $("#tip").html("<option value=\"\">Выберите тип</option><option value=\"1\">Розничная торговля</option><option value=\"2\">Оптовая торговля</option>");
            }

            document.getElementById("url_address").value = res.url_address || '';
            document.getElementById("postal_address").value = res.postal_address || '';
            document.getElementById("ogrn").value = res.ogrn || '';
            document.getElementById("inn").value = res.inn || '';
            document.getElementById("kpp").value = res.kpp || '';
            document.getElementById("bik").value = res.bik || '';
            document.getElementById("raschet").value = res.raschet || '';
            document.getElementById("korschet").value = res.korschet || '';
            document.getElementById("bank").value = res.bank || '';
            document.getElementById("dir_name").value = res.dir_name || '';
            document.getElementById("dogovor").value = res.dogovor || '';

            if (res.hide == "show") {
                $("#hide").html("<option value=\"show\" selected=\"selected\">Активный</option><option value=\"hide\">Не активный</option>");
            } else if (res.hide == "hide") {
                $("#hide").html("<option value=\"hide\" selected=\"selected\">Не активный</option><option value=\"show\">Активный</option>");
            } else {
                $("#hide").html("<option value=\"\">Выберите статус</option><option value=\"show\">Активный</option><option value=\"hide\">Не активный</option>");
            }

            changeAllRowsPrices();
        },
        error: function(){
            alert('Ошибка');
        }
    });
});
</script>

<script type="text/javascript">
var total = 0;

function toNum(val) {
    var n = parseFloat(val);
    return isNaN(n) ? 0 : n;
}

function recalcAll() {
    var amount = 0;
    $('.td_amount').each(function () {
        amount += toNum($(this).val());
    });
    $(".amount").html(amount.toFixed(2));

    var sum = 0;
    $('.td_itog').each(function () {
        sum += toNum($(this).val());
    });
    $(".sum").html(sum.toFixed(2));

    var nalogitogo = 0;
    $('.itogsum_nalog').each(function () {
        nalogitogo += toNum($(this).val());
    });
    $(".sum_nalog_itogo").html(nalogitogo.toFixed(2));

    var beznalogSkidki = 0;
    $('.beznalog_skidki').each(function () {
        beznalogSkidki += toNum($(this).val());
    });
    $(".sum_beznalog_skidki").html(beznalogSkidki.toFixed(2));

    var beznalog = sum - nalogitogo;
    $(".sum_beznalog_itogo").html(beznalog.toFixed(2));

    var itogqty = 0;
    $('.itog_qty').each(function () {
        itogqty += toNum($(this).val());
    });
    $(".itogqty").html(itogqty);

    var itogsweight = 0;
    $('.td_itog_sumweight').each(function () {
        itogsweight += toNum($(this).val());
    });
    $(".sum_weight").html(itogsweight.toFixed(2));

    var itogsvolume = 0;
    $('.td_itog_sumvolume').each(function () {
        itogsvolume += toNum($(this).val());
    });
    $(".sum_volume").html(itogsvolume.toFixed(2));
}

function add_new_orders() {
    total++;

    let row = '';
    row += '<tr id="tr_order_' + total + '" style="line-height:20px;">';
    row += '   <td>' + total + '</td>';

    row += '   <td id="td_product_' + total + '" style="padding: 5px 10px;">';
    row += '       <select class="form-control select_product searchproduct_' + total + '" name="order_zakaz[' + total + '][product_id]">';
    row += '           <option value=""></option>';
    row += '       </select>';
    row += '   </td>';

    row += '   <td id="td_article_' + total + '" style="padding: 5px 10px;">';
    row += '       <input id="article_text_' + total + '" name="order_zakaz[' + total + '][article]" type="text" value="" class="form-control" placeholder="артикул товара" readonly>';
    row += '   </td>';

    row += '   <td id="td_price_' + total + '" style="padding: 5px 10px;">';
    row += '       <input id="price_text_' + total + '" name="order_zakaz[' + total + '][price]" type="number" value="0" class="form-control orderprice_' + total + '" placeholder="0" oninput="change_price(' + total + ')">';
    row += '       <input type="hidden" id="base_price_text_' + total + '" value="0">';
    row += '       <input type="hidden" id="price_nalog_text_' + total + '" name="order_zakaz[' + total + '][price_nalog]" class="form-control prod_nalog" value="0">';
    row += '       <input type="hidden" id="sum_nalog_text_' + total + '" name="order_zakaz[' + total + '][sum_nalog]" class="form-control sum_nalog" value="0">';
    row += '       <input type="hidden" id="itogsum_nalog_text_' + total + '" name="order_zakaz[' + total + '][itogsum_nalog]" class="form-control itogsum_nalog" value="0">';
    row += '       <input type="hidden" id="sum_beznalog_text_' + total + '" name="order_zakaz[' + total + '][sum_beznalog]" class="form-control sum_beznalog" value="0">';
    row += '       <input type="hidden" id="sum_beznalog_skidki_text_' + total + '" name="order_zakaz[' + total + '][sum_beznalog_skidki]" class="form-control beznalog_skidki" value="0">';
    row += '       <input type="hidden" id="weight_text_' + total + '" value="0">';
    row += '       <input type="hidden" id="volume_text_' + total + '" value="0">';
    row += '   </td>';

    row += '   <td id="td_quantity_' + total + '" style="padding: 5px 10px;">';
    row += '       <input type="number" id="quantity_text_' + total + '" name="order_zakaz[' + total + '][quantity]" class="form-control itog_qty orderquantity_' + total + '" value="1" oninput="change_price(' + total + ')">';
    row += '       <input type="hidden" id="itog_quantity_text_' + total + '" name="order_zakaz[' + total + '][itogquantity]" class="form-control itogquantity_' + total + '" value="1">';
    row += '   </td>';

    row += '   <td id="td_nalichie_' + total + '" style="padding: 5px 10px;">';
    row += '       <input type="text" id="nalichie_text_' + total + '" name="order_zakaz[' + total + '][order_nalichie]" value="0 шт." class="form-control" readonly>';
    row += '   </td>';

    row += '   <td id="td_rezerv_' + total + '" style="padding: 5px 10px;">';
    row += '       <input type="text" id="rezerv_text_' + total + '" name="order_zakaz[' + total + '][order_rezerv]" value="0 шт." class="form-control" readonly>';
    row += '   </td>';

    row += '   <td id="td_discount_value_' + total + '" style="padding: 5px 10px;">';
    row += '       <input type="number" id="discount_text_value_' + total + '" name="order_zakaz[' + total + '][discount_value]" class="form-control orderdiscount_value_' + total + '" value="0" oninput="change_price(' + total + ')">';
    row += '   </td>';

    row += '   <td id="td_type_discount_' + total + '" style="padding: 5px 10px;">';
    row += '       <select id="type_discount_text_' + total + '" name="order_zakaz[' + total + '][type_discount]" class="form-control ordertypediscount_' + total + '" onchange="change_price(' + total + ')">';
    row += '           <option value="2">%</option>';
    row += '           <option value="1">₽</option>';
    row += '       </select>';
    row += '   </td>';

    row += '   <td id="td_discount_' + total + '" style="padding: 5px 10px;">';
    row += '       <input type="number" id="discount_text_' + total + '" name="order_zakaz[' + total + '][discount]" class="form-control orderdiscount_' + total + '" value="0" readonly>';
    row += '   </td>';

    row += '   <td id="td_price_discount_' + total + '" style="padding: 5px 10px;">';
    row += '       <input type="number" id="price_discount_text_' + total + '" name="order_zakaz[' + total + '][price_discount]" class="form-control orderpricediscount_' + total + '" value="0" readonly>';
    row += '   </td>';

    row += '   <td id="td_discount_amount_' + total + '" style="padding: 5px 10px;">';
    row += '       <input type="text" id="discount_amount_text_' + total + '" name="order_zakaz[' + total + '][discount_amount]" class="form-control td_amount orderdiscount_amount_' + total + '" value="0" readonly>';
    row += '   </td>';

    row += '   <td id="td_itog_' + total + '" style="padding: 5px 10px;">';
    row += '       <input id="itog_text_' + total + '" name="order_zakaz[' + total + '][itog]" value="0" class="form-control itog_price_' + total + ' td_itog" readonly>';
    row += '       <input id="sumweight_text_' + total + '" type="hidden" name="order_zakaz[' + total + '][sumweight]" value="0" class="form-control sumweight_' + total + ' td_sumweight" readonly>';
    row += '       <input id="itog_sumweight_text_' + total + '" type="hidden" name="order_zakaz[' + total + '][itog_sumweight]" value="0" class="form-control itog_sumweight_' + total + ' td_itog_sumweight" readonly>';
    row += '       <input id="sumvolume_text_' + total + '" type="hidden" name="order_zakaz[' + total + '][sumvolume]" value="0" class="form-control sumvolume_' + total + ' td_sumvolume" readonly>';
    row += '       <input id="itog_sumvolume_text_' + total + '" type="hidden" name="order_zakaz[' + total + '][itog_sumvolume]" value="0" class="form-control itog_sumvolume_' + total + ' td_itog_sumvolume" readonly>';
    row += '   </td>';

    row += '   <td style="padding: 5px 10px;">';
    row += '       <span id="progress_' + total + '"><a href="javascript:void(0)" onclick="$(\'#tr_order_' + total + '\').remove(); recalcAll();" class="btn btn-default float-right">Удалить</a></span>';
    row += '   </td>';

    row += '</tr>';

    $('#table_container').append(row);
    initailizeSelect2(String(total));
    change_price(total);
}

function initailizeSelect2(total) {
    $(".searchproduct_" + total).select2({
        placeholder: "Начните вводить название или артикул товара",
        minimumInputLength: 1,
        cache: true,
        ajax: {
            url: adminpath + "/order/searchproduct",
            delay: 250,
            dataType: 'json',
            data: function (params) {
                return {
                    q: params.term,
                    page: params.page
                };
            },
            processResults: function (data) {
                return {
                    results: data.items
                };
            }
        }
    });

    $(".searchproduct_" + total).on("change", function () {
        var id = $(this).val();
        var comp_id = $("#comp_id").val() || 0;
        var nalogSelect = $('#cnds').val() || '';

        $.ajax({
            url: adminpath + "/order/productprice",
            data: {id: id, comp_id: comp_id},
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                var basePrice = toNum(res.result2);
                var qty = 1;
                var discountPercent = toNum(res.result7);
                var weight = toNum(res.result5);
                var volume = toNum(res.result6);

                $("#article_text_" + total).val(res.result1 || '');
                $("#base_price_text_" + total).val(basePrice.toFixed(2));
                $("#price_text_" + total).val(basePrice.toFixed(2));
                $("#quantity_text_" + total).val(1);
                $("#itog_quantity_text_" + total).val(1);
                $("#nalichie_text_" + total).val((res.result3 || 0) + " шт.");
                $("#rezerv_text_" + total).val((res.result4 || 0) + " шт.");
                $("#discount_text_value_" + total).val(discountPercent);
                $("#type_discount_text_" + total).val(discountPercent > 0 ? '2' : '1');
                $("#weight_text_" + total).val(weight.toFixed(2));
                $("#volume_text_" + total).val(volume.toFixed(2));
                $("#sumweight_text_" + total).val(weight.toFixed(2));
                $("#sumvolume_text_" + total).val(volume.toFixed(2));
                $("#itog_sumweight_text_" + total).val((weight * qty).toFixed(2));
                $("#itog_sumvolume_text_" + total).val((volume * qty).toFixed(2));

                var priceNalog = 0;
                if ($('#vid').val() == '4' && nalogSelect == '1') {
                    priceNalog = 20;
                }
                $("#price_nalog_text_" + total).val(priceNalog);

                change_price(total);
            },
            error: function () {
                alert('Ошибка');
            }
        });
    });
}

function change_price(total) {
    if (!$("#price_text_" + total).length) {
        recalcAll();
        return;
    }

    var basePrice = toNum($("#base_price_text_" + total).val());
    var price = basePrice > 0 ? basePrice : toNum($("#price_text_" + total).val());
    var qty = toNum($("#quantity_text_" + total).val());
    var discountValue = toNum($("#discount_text_value_" + total).val());
    var typeDiscount = $("#type_discount_text_" + total).val();
    var vid = $("#vid").val();
    var cnds = $("#cnds").val();
    var weight = toNum($("#weight_text_" + total).val());
    var volume = toNum($("#volume_text_" + total).val());

    if (qty <= 0) {
        qty = 0;
    }

    var discountRub = 0;
    var priceDiscount = price;
    var nalog = 0;

    if (typeDiscount == '1') {
        if (vid == '4' && cnds == '1') {
            var priceNdsRub = Math.round((price - (price / 1.2)) * 6) / 6;
            var discountPrice = priceNdsRub - discountValue;
            priceDiscount = Math.round(discountPrice / 6) * 6;
            discountRub = price - priceDiscount;
            nalog = 20;
        } else {
            discountRub = discountValue;
            priceDiscount = price - discountRub;
            nalog = 0;
        }
    } else {
        if (vid == '4' && cnds == '1') {
            var priceNdsPercent = Math.round((price - (price / 1.2)) * 6) / 6;
            var discountPricePercent = priceNdsPercent - ((priceNdsPercent / 100) * discountValue);
            priceDiscount = Math.round(discountPricePercent / 6) * 6;
            discountRub = price - priceDiscount;
            nalog = 20;
        } else {
            discountRub = price * discountValue / 100;
            priceDiscount = price - discountRub;
            nalog = 0;
        }
    }

    if (priceDiscount < 0) {
        priceDiscount = 0;
    }

    if (discountRub < 0) {
        discountRub = 0;
    }

    var discountAmount = discountRub * qty;
    var itog = priceDiscount * qty;

    var sumNalog = 0;
    var itogSumNalog = 0;
    var sumBezNalog = 0;

    if (nalog == 20) {
        sumNalog = priceDiscount * 0.2 / 1.2;
        itogSumNalog = sumNalog * qty;
        sumBezNalog = itog - itogSumNalog;
    } else {
        sumNalog = 0;
        itogSumNalog = 0;
        sumBezNalog = itog;
    }

    var itogWeight = weight * qty;
    var itogVolume = volume * qty;

    $("#price_text_" + total).val(price.toFixed(2));
    $("#price_nalog_text_" + total).val(nalog);
    $("#sum_nalog_text_" + total).val(sumNalog.toFixed(2));
    $("#itogsum_nalog_text_" + total).val(itogSumNalog.toFixed(2));
    $("#sum_beznalog_text_" + total).val(sumBezNalog.toFixed(2));
    $("#sum_beznalog_skidki_text_" + total).val((price * qty).toFixed(2));

    $("#discount_text_" + total).val(discountRub.toFixed(2));
    $("#price_discount_text_" + total).val(priceDiscount.toFixed(2));
    $("#discount_amount_text_" + total).val(discountAmount.toFixed(2));
    $("#itog_text_" + total).val(itog.toFixed(2));

    $("#itog_quantity_text_" + total).val(qty);

    $("#sumweight_text_" + total).val(weight.toFixed(2));
    $("#itog_sumweight_text_" + total).val(itogWeight.toFixed(2));

    $("#sumvolume_text_" + total).val(volume.toFixed(2));
    $("#itog_sumvolume_text_" + total).val(itogVolume.toFixed(2));

    recalcAll();
}

function changeAllRowsPrices() {
    for (var i = 1; i <= total; i++) {
        if ($("#tr_order_" + i).length) {
            change_price(i);
        }
    }
}

$(function () {
    $("#add_prod").on("click", function () {
        add_new_orders();
    });

    $("#vid, #cnds").on("change", function () {
        changeAllRowsPrices();
    });

    recalcAll();
});
</script>