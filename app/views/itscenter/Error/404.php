<section class="error404-page py-4 py-lg-5">
    <div class="container">
        <div class="error404-card">
            <div class="error404-code">404</div>

            <h1 class="error404-title">Страница не найдена</h1>

            <p class="error404-text">
                К сожалению, запрашиваемой вами страницы не существует на нашем сайте.
            </p>

            <p class="error404-text error404-text--mb">
                Возможные причины:
            </p>

            <ul class="error404-list">
                <li>Вы ошиблись при наборе адреса страницы</li>
                <li>Перешли по устаревшей или битой ссылке</li>
                <li>Страница была удалена или перемещена</li>
            </ul>

            <p class="error404-text error404-note">
                Проверьте адрес страницы или воспользуйтесь навигацией сайта.
            </p>

            <div class="error404-actions">
                <a href="<?= PATH ?>/" class="btn btn-danger">На главную</a>
                <a href="<?= PATH ?>/catalog" class="btn btn-outline-secondary">В каталог</a>
            </div>
        </div>
    </div>
</section>

<style>
.error404-page{
    background:#f7f8fa;
    min-height: calc(100vh - 140px);
    display:flex;
    align-items:center;
}

.error404-card{
    max-width:900px;
    margin:0 auto;
    background:#fff;
    border-radius:20px;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
    padding:40px 24px;
    text-align:center;
}

.error404-code{
    font-size:110px;
    line-height:1;
    font-weight:800;
    color:#dc3545;
    margin-bottom:12px;
}

.error404-title{
    margin:0 0 18px;
    font-size:40px;
    line-height:1.15;
    font-weight:700;
    color:#1f2937;
}

.error404-text{
    margin:0 0 12px;
    font-size:18px;
    line-height:1.6;
    color:#4b5563;
}

.error404-text--mb{
    margin-bottom:10px;
}

.error404-list{
    max-width:560px;
    margin:0 auto 24px;
    padding-left:20px;
    text-align:left;
    color:#374151;
}

.error404-list li{
    margin-bottom:10px;
    font-size:17px;
    line-height:1.55;
}

.error404-note{
    margin-bottom:28px;
}

.error404-actions{
    display:flex;
    flex-wrap:wrap;
    justify-content:center;
    gap:12px;
}

@media (max-width: 767px){
    .error404-page{
        min-height:auto;
        align-items:flex-start;
    }

    .error404-card{
        padding:28px 18px;
        border-radius:16px;
    }

    .error404-code{
        font-size:72px;
    }

    .error404-title{
        font-size:30px;
    }

    .error404-text,
    .error404-list li{
        font-size:16px;
    }
}
</style>