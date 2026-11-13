(() => {
  function buildRoute(base, selected, primary, fields = {}) {
    const url = new URL(base), counts = new Map();
    selected.forEach(item => counts.set(item.group, (counts.get(item.group) || 0) + 1));
    const candidates = selected.filter(item => item.alias && counts.get(item.group) === 1);
    const main = candidates.find(item => item.id === primary) || candidates[0];
    if (main) url.pathname += '/' + main.alias.split('/').map(encodeURIComponent).join('/');
    const extras = selected.filter(item => item !== main);
    if (extras.length) url.searchParams.set('filter', extras.map(item => item.id).join(','));
    for (const key of ['sort', 'status', 'in_stock', 'perpage']) {
      const value = fields[key];
      if (value === undefined || value === '' || (key === 'sort' && value === 'stock') || (key === 'perpage' && value === '24') || (key === 'in_stock' && value !== '1')) continue;
      url.searchParams.set(key, value);
    }
    return url.href;
  }
  if (typeof module !== 'undefined' && module.exports) module.exports = {buildRoute};
  if (typeof document === 'undefined') return;
  let current, timer, request, revision = 0;
  const desktop = matchMedia('(min-width:992px)');

  function initRelated() {
    if (typeof window.Swiper !== 'function') return;
    document.querySelectorAll('.catalog-related-slider').forEach(el => {
      if (el.swiper) return;
      const columns = Number.parseInt(getComputedStyle(el.closest('.catalog-facet-layout')).getPropertyValue('--catalog-columns'),10) === 3 ? 3 : 4;
      const wrapper = el.closest('.related_prod');
      new window.Swiper(el,{slidesPerView:1,slidesPerGroup:1,watchOverflow:true,spaceBetween:16,breakpoints:{576:{slidesPerView:2},992:{slidesPerView:3},1400:{slidesPerView:columns}},navigation:{nextEl:wrapper?.querySelector('.swiper-button-next-2'),prevEl:wrapper?.querySelector('.swiper-button-prev-2')}});
    });
  }
  function init(saved) {
    const root = document.querySelector('[data-facets]');
    if (!root) {current = null; return;}
    root.classList.add('is-enhanced');
    const form = root.querySelector('form'), opener = root.querySelector('[data-facets-open]');
    const backdrop = root.querySelector('.catalog-facets__backdrop');
    const state = {root, form, order:[...form.querySelectorAll('input[name="filter[]"]:checked')].map(el => el.value)};
    let oldOverflow = '';
    state.close = (focus = true) => {
      if (!root.classList.contains('is-open')) return;
      root.classList.remove('is-open'); backdrop.hidden = true;
      form.removeAttribute('role'); form.removeAttribute('aria-modal');
      document.body.style.overflow = oldOverflow; opener.setAttribute('aria-expanded', 'false');
      if (focus) opener.focus();
    };
    state.open = () => {
      if (root.classList.contains('is-open')) return;
      oldOverflow = document.body.style.overflow; document.body.style.overflow = 'hidden';
      root.classList.add('is-open'); backdrop.hidden = false;
      form.setAttribute('role', 'dialog'); form.setAttribute('aria-modal', 'true'); form.setAttribute('aria-label', 'Фильтры каталога');
      opener.setAttribute('aria-expanded', 'true'); form.querySelector('[data-facets-close]').focus();
    };
    opener.addEventListener('click', state.open);
    root.querySelectorAll('[data-facets-close]').forEach(el => el.addEventListener('click', () => state.close()));
    form.querySelectorAll('.catalog-facets__group').forEach(group => {
      const options = [...group.querySelectorAll('.catalog-facets__option')], more = group.querySelector('.catalog-facets__more');
      const restored = saved?.groups.find(item => item.id === group.querySelector('fieldset').id);
      let expanded = restored?.expanded || false;
      if (restored) group.open = restored.open;
      function refresh() {
        let count = 0;
        options.forEach((option, i) => {option.hidden = !expanded && i >= 6 && !option.querySelector('input').checked; if (option.hidden) count++;});
        more.hidden = options.length <= 6 || (!expanded && count === 0);
        more.textContent = expanded ? 'Свернуть' : `Показать ещё (${count})`; more.setAttribute('aria-expanded', String(expanded));
      }
      more.addEventListener('click', () => {expanded = !expanded; refresh();});
      group.addEventListener('change', refresh); refresh();
    });
    current = state; initRelated();
    if (saved?.open && !desktop.matches) {
      state.open();
      root.querySelector('.catalog-facets__body').scrollTop = saved.panelScroll || 0;
    }
  }

  function targetUrl() {
    const {form, order} = current;
    const selected = [...form.querySelectorAll('input[name="filter[]"]:checked')]
      .sort((a,b) => order.indexOf(a.value) - order.indexOf(b.value))
      .map(el => ({id:el.value, group:el.dataset.group, alias:el.dataset.alias}));
    const data = new FormData(form), fields = {};
    for (const key of ['sort','status','in_stock','perpage']) if (data.has(key)) fields[key] = data.get(key);
    try {localStorage.setItem('catalog-sort', fields.sort || 'stock');} catch {}
    return buildRoute(form.action, selected, form.dataset.primaryId, fields);
  }

  function busy(value) {
    const results = document.querySelector('.catalog-facet-results');
    if (results) {results.classList.toggle('is-loading', value); results.setAttribute('aria-busy', String(value));}
    const status = document.querySelector('[data-catalog-loading]'); if (status) status.hidden = !value;
  }

  async function navigate(url, version, push) {
    if (url === location.href && push) {busy(false); return;}
    request = new AbortController();
    try {
      const response = await fetch(url, {credentials:'same-origin', signal:request.signal});
      if (!response.ok) throw new Error('Catalog response');
      const html = await response.text();
      if (version !== revision) return;
      const page = new DOMParser().parseFromString(html, 'text/html');
      const replacement = page.querySelector('.content'), old = document.querySelector('.content');
      if (!replacement?.querySelector('[data-facets]') || !old) throw new Error('Catalog markup');
      const focused = document.activeElement, focusKey = focused?.name ? {name:focused.name,value:focused.value} : null;
      const scroll = window.scrollY;
      const saved = {open:current.root.classList.contains('is-open'), panelScroll:current.root.querySelector('.catalog-facets__body').scrollTop, groups:[...current.root.querySelectorAll('details')].map(group => ({id:group.querySelector('fieldset').id,open:group.open,expanded:group.querySelector('.catalog-facets__more').getAttribute('aria-expanded') === 'true'}))};
      current.close(false);
      old.querySelectorAll('.swiper-container').forEach(el => {if (el.swiper) el.swiper.destroy(true,true);});
      replacement.querySelectorAll('script:not([type="application/ld+json"])').forEach(el => el.remove());
      old.replaceWith(document.importNode(replacement,true));
      document.title = page.title;
      for (const selector of ['meta[name="description"]','meta[name="keywords"]','meta[name="robots"]','link[rel="canonical"]','meta[property="og:title"]','meta[property="og:description"]','meta[property="og:url"]','meta[property="og:image"]']) {
        const next = page.querySelector(selector), previous = document.head.querySelector(selector);
        if (next) {if (previous) previous.replaceWith(document.importNode(next,true)); else document.head.append(document.importNode(next,true));}
        else if (previous) previous.remove();
      }
      if (push) history.pushState({catalogFilters:true},'',response.url);
      init(saved); busy(false);
      if (focusKey) {
        const controls = [...document.querySelectorAll('input,select')];
        const next = controls.find(el => el.name === focusKey.name && (el.tagName === 'SELECT' || el.value === focusKey.value));
        if (next && next.getClientRects().length) next.focus({preventScroll:true});
      }
      if (!saved.open) window.scrollTo({top:scroll,behavior:'instant'});
      if (typeof window.initCategoryProductLazyLoading === 'function') window.initCategoryProductLazyLoading();
      document.dispatchEvent(new CustomEvent('catalog:updated'));
    } catch (error) {
      if (version !== revision || error.name === 'AbortError') return;
      // A normal server-rendered navigation remains the fallback for every URL.
      location.assign(url);
    }
  }

  function schedule(delay = 350, explicitUrl) {
    clearTimeout(timer); if (request) request.abort();
    const version = ++revision; busy(true);
    timer = setTimeout(() => navigate(explicitUrl || targetUrl(),version,true),delay);
  }

  document.addEventListener('change', event => {
    if (!current) return;
    const input = event.target;
    if (input.matches('[data-facets-form] input[type=checkbox]')) {
      if (input.name === 'filter[]') {current.order = current.order.filter(id => id !== input.value); if (input.checked) current.order.push(input.value);}
      current.root.querySelector('[data-facets-count]').textContent = current.form.querySelectorAll('input[type=checkbox]:checked').length;
      schedule();
    } else if (input.matches('select[form="catalog-facet-panel"]')) schedule(0);
  });
  document.addEventListener('submit', event => {if (event.target.matches('[data-facets-form]')) {event.preventDefault();schedule(0);}});
  document.addEventListener('click', event => {
    const reset = event.target.closest('[data-facets-reset]');
    if (reset && current && !event.ctrlKey && !event.metaKey) {event.preventDefault();schedule(0,reset.href);}
  });
  document.addEventListener('keydown', event => {
    if (!current?.root.classList.contains('is-open')) return;
    if (event.key === 'Escape') current.close();
    if (event.key === 'Tab') {
      const elements = [...current.form.querySelectorAll('button,a[href],input:not([type=hidden]):not([disabled]),summary')].filter(el => el.getClientRects().length);
      const first = elements[0], last = elements[elements.length-1];
      if (event.shiftKey && document.activeElement === first) {event.preventDefault();last.focus();}
      else if (!event.shiftKey && document.activeElement === last) {event.preventDefault();first.focus();}
    }
  });
  desktop.addEventListener('change', event => {if (event.matches && current) current.close();});
  window.addEventListener('popstate', () => {if (current) {clearTimeout(timer);request?.abort();busy(true);navigate(location.href,++revision,false);}});
  window.addEventListener('load', initRelated, {once:true});
  init();
})();
