<div class="card">
  <div class="card-header">
    <h3 class="card-title">Пакетная конвертация изображений</h3>
  </div>
  <div class="card-body">
    <div class="row g-2">
      <div class="col-md-3 mb-2">
        <label class="form-label">Раздел</label>
        <select id="ci-section" class="form-select form-control">
          <option value="product">Товары</option>
          <option value="complete">Комплекты</option>
          <option value="review">Отзывы (галерея)</option>
          <option value="technics">Техника</option>
          <option value="technics_type">Типы техники</option>
          <option value="technics_brand">Бренды техники</option>
          <option value="category">Категории</option>
          <option value="brand">Бренды</option>
          <option value="contents">Контент</option>
          <option value="all">ВСЕ разделы</option>
        </select>
      </div>

      <div class="col-md-2 mb-2">
        <label class="form-label">Целевой формат</label>
        <select id="ci-target" class="form-select form-control">
          <option value="avif">AVIF</option>
          <option value="webp" selected>WEBP</option>
          <option value="jpg">JPG</option>
          <option value="png">PNG</option>
        </select>
      </div>

      <div class="col-md-2 mb-2">
        <label class="form-label">Размер батча</label>
        <input type="number" id="ci-limit" class="form-control" value="200" min="10" max="2000">
      </div>

      <div class="col-md-2 mb-2">
        <label class="form-label">Режим</label>
        <select id="ci-dry" class="form-select form-control">
          <option value="1" selected>Dry run (только проверить)</option>
          <option value="0">Боевой (конвертировать)</option>
        </select>
      </div>

      <div class="col-md-3 mb-2 align-self-end">
        <button id="ci-start" class="btn btn-success">Запустить</button>
        <button id="ci-stop" class="btn btn-outline-danger" disabled>Стоп</button>
        <button id="ci-unlock" class="btn btn-outline-secondary">Разблокировать</button>
      </div>
    </div>

    <div class="mt-3">
      <div class="progress" style="height:22px;">
        <div id="ci-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width:0%">0%</div>
      </div>
      <div class="mt-2 small" id="ci-stats">готово: 0 / 0 • ok: 0 • skip: 0 • err: 0</div>
      <pre id="ci-log" class="mt-2" style="max-height:260px;overflow:auto;background:#111;color:#9f9;padding:10px;"></pre>
    </div>
  </div>
</div>

<script>
(function(){
  const adminpath = '<?= ADMIN ?>';
  const el = (id)=>document.getElementById(id);

  const startBtn = el('ci-start');
  const stopBtn  = el('ci-stop');
  const unlockBtn= el('ci-unlock');
  const bar      = el('ci-bar');
  const statsEl  = el('ci-stats');
  const logEl    = el('ci-log');
  const limitEl  = el('ci-limit');
  const dryEl    = el('ci-dry');
  const sectionEl= el('ci-section');
  const targetEl = el('ci-target');

  let running=false, offset=0, total=0, token='';
  let ok=0, skip=0, err=0;

  function setProgress(){
    const done = Math.min(offset, total);
    const pct = total ? Math.floor(done*100/total) : 0;
    bar.style.width = pct+'%';
    bar.textContent = pct+'%';
    statsEl.textContent = `готово: ${done} / ${total} • ok: ${ok} • skip: ${skip} • err: ${err}`;
  }
  function log(lines){
    if (!lines) return;
    if (Array.isArray(lines)) lines = lines.join('\n');
    logEl.textContent += lines+'\n';
    logEl.scrollTop = logEl.scrollHeight;
  }

  function callBatch(){
    if (!running) return;
    const limit   = parseInt(limitEl.value||'200',10);
    const dry     = parseInt(dryEl.value||'1',10);
    const section = sectionEl.value || 'product';
    const target  = targetEl.value || 'webp';

    const url = `${adminpath}/plagins/convert-images-batch?section=${encodeURIComponent(section)}&limit=${limit}&offset=${offset}&dry=${dry}&target=${encodeURIComponent(target)}&token=${encodeURIComponent(token)}`;

    fetch(url, { credentials:'same-origin' })
      .then(r => r.text())
      .then(t => {
        let d;
        try {
          d = JSON.parse(t);
        } catch (e) {
          // сервер вернул не-JSON (чаще всего HTML с ошибкой PHP)
          log('RAW RESPONSE (первые 1000 символов):\n' + t.slice(0,1000));
          throw new SyntaxError('Bad JSON from server');
        }
        return d;
      })
      .then(d=>{
        if (d.error){
          running=false; startBtn.disabled=false; stopBtn.disabled=true;
          log(['ERROR:', d.error, d.message||''].join(' '));
          return;
        }
        if (!token) token = d.token || '';
        if (!total) total = d.total || 0;

        ok   += d.ok   || 0;
        skip += d.skip || 0;
        err  += d.err  || 0;

        offset = (d.next_offset==null) ? (offset + (d.processed||0)) : d.next_offset;

        if (d.log && d.log.length) log(d.log);
        setProgress();

        if (d.done){
          running=false; startBtn.disabled=false; stopBtn.disabled=true;
          log('=== ГОТОВО ===');
        } else {
          setTimeout(callBatch, 300);
        }
      })
      .catch(e=>{
        running=false; startBtn.disabled=false; stopBtn.disabled=true;
        log('FETCH ERROR: ' + e);
      });
  }

  startBtn.addEventListener('click', function(e){
    e.preventDefault();
    if (running) return;
    running=true;
    offset=0; total=0; ok=0; skip=0; err=0; token='';
    startBtn.disabled=true; stopBtn.disabled=false;
    logEl.textContent='';
    setProgress();
    callBatch();
  });

  stopBtn.addEventListener('click', function(e){
    e.preventDefault();
    if (!running) return;
    running=false; startBtn.disabled=false; stopBtn.disabled=true;
    log('=== ОСТАНОВЛЕНО ПОЛЬЗОВАТЕЛЕМ ===');
  });

  unlockBtn.addEventListener('click', function(e){
    e.preventDefault();
    const section = sectionEl.value || 'product';
    fetch(`${adminpath}/plagins/convert-images-unlock?section=${encodeURIComponent(section)}`, { credentials:'same-origin' })
      .then(r=>r.json())
      .then(d=>{ alert(d.message || 'OK'); })
      .catch(err=> alert('unlock error: '+err));
  });
})();
</script>
