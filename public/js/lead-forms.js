(function () {
  'use strict';
  if (window.itsLeadFormsReady) return;
  window.itsLeadFormsReady = true;
  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-bs-target="#Modalclick"]');
    if (!trigger) return;
    var scope = trigger.closest('form') || trigger.parentElement;
    var quantity = scope && scope.querySelector('input.detail-quantity');
    var target = document.querySelector('#Modalclick input[name="quantity"]');
    if (target && quantity) target.value = quantity.value;
  });
  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form.matches('[data-lead-form]') || event.defaultPrevented) return;
    if (!form.checkValidity()) { event.preventDefault(); form.reportValidity(); return; }
    if (form.dataset.sending === '1') { event.preventDefault(); return; }
    form.dataset.sending = '1';
    setTimeout(function () {
      form.querySelectorAll('button[type="submit"]').forEach(function (button) { button.disabled = true; });
    }, 0);
  });
}());
