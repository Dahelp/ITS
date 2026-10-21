(function(){
  'use strict';
  function init(){
    var root = document.querySelector('.hp-catalog');
    if (!root) return;
    function fitPhoneHits(){
      var element = document.querySelector('.hits-swiper');
      var slider = element && element.swiper;
      if (!slider || window.innerWidth > 767) return;
      [slider.params, slider.originalParams].forEach(function(params){
        params.slidesOffsetBefore = 0; params.slidesOffsetAfter = 0;
        Object.keys(params.breakpoints || {}).forEach(function(key){
          if (Number(key) <= 767) {
            params.breakpoints[key].slidesOffsetBefore = 0;
            params.breakpoints[key].slidesOffsetAfter = 0;
          }
        });
      });
      slider.update(); slider.slideTo(slider.activeIndex, 0, false);
    }
    if (document.readyState === 'complete') fitPhoneHits();
    else window.addEventListener('load', fitPhoneHits, {once:true});
    window.addEventListener('resize', fitPhoneHits);
    var menu = document.querySelector('.right-menu');
    if (menu) {
      var actions = document.createElement('div');
      actions.id = 'home-help-actions';
      actions.className = 'home-help-actions';
      while (menu.firstChild) actions.appendChild(menu.firstChild);
      var help = document.createElement('button');
      help.type = 'button'; help.className = 'home-help-toggle';
      help.textContent = 'Помощь';
      help.setAttribute('aria-expanded', 'false');
      help.setAttribute('aria-controls', actions.id);
      menu.appendChild(actions); menu.appendChild(help);
      menu.classList.add('home-help-menu');
      function closeHelp(){ menu.classList.remove('home-help-open'); help.setAttribute('aria-expanded', 'false'); }
      help.addEventListener('click', function(){
        var open = menu.classList.toggle('home-help-open'); help.setAttribute('aria-expanded', String(open));
      });
      actions.addEventListener('click', function(event){ if(event.target.closest('a')) closeHelp(); });
      document.addEventListener('click', function(event){ if(!menu.contains(event.target)) closeHelp(); });
      menu.addEventListener('keydown', function(event){ if(event.key === 'Escape'){closeHelp(); help.focus();} });
    }
    var tabs = Array.from(root.querySelectorAll('[data-hp-tab]'));
    var sectionLinks = root.querySelectorAll('[data-hp-section-link]');
    function activate(tab, focus){
      var active = tab.getAttribute('data-hp-tab');
      tabs.forEach(function(item){
        var selected = item === tab;
        item.setAttribute('aria-selected', String(selected));
        item.tabIndex = selected ? 0 : -1;
      });
      root.querySelectorAll('[data-hp-panel]').forEach(function(panel){
        panel.hidden = panel.getAttribute('data-hp-panel') !== active;
      });
      sectionLinks.forEach(function(sectionLink){
      sectionLink.href = active === 'atv' ? '/category/atv' : '/category/industrialnye-shiny';
      sectionLink.replaceChildren(document.createTextNode(active === 'atv' ? 'Все шины для квадроциклов ' : 'Все индустриальные шины '));
      var arrow = document.createElement('span'); arrow.setAttribute('aria-hidden', 'true'); arrow.textContent = '→'; sectionLink.appendChild(arrow);
      });
      if (focus) tab.focus();
    }
    tabs.forEach(function(tab, index){
      tab.addEventListener('click', function(){ activate(tab, false); });
      tab.addEventListener('keydown', function(event){
        var next;
        if (event.key === 'ArrowRight') next = tabs[(index + 1) % tabs.length];
        if (event.key === 'ArrowLeft') next = tabs[(index + tabs.length - 1) % tabs.length];
        if (event.key === 'Home') next = tabs[0];
        if (event.key === 'End') next = tabs[tabs.length - 1];
        if (next) { event.preventDefault(); activate(next, true); }
      });
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
