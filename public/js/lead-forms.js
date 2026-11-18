(function () {
  'use strict';
  if (window.itsLeadFormsReady) return;
  window.itsLeadFormsReady = true;
  function validatePhone(field) {
    var raw = field.value;
    var digits = raw.replace(/\D/g, '').replace(/^8/, '7');
    var valid = !/[\r\n]/.test(raw) && /^\+?[0-9 ()-]+$/.test(raw) && /^79\d{9}$/.test(digits) && !/^(\d)\1{8}$/.test(digits.slice(2));
    field.setCustomValidity(valid ? '' : 'Укажите полный номер мобильного телефона РФ: +7 9ХХ ХХХ-ХХ-ХХ.');
  }
  function phoneFields(form) {
    return form.querySelectorAll('input[name="phone"], input[name="tell_modal"]');
  }
  document.addEventListener('input', function (event) {
    var field = event.target;
    if (field.matches('input[name="phone"], input[name="tell_modal"]') && field.closest('[data-lead-form]')) validatePhone(field);
  }, true);
  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form.matches('[data-lead-form]')) return;
    phoneFields(form).forEach(validatePhone);
    if (!form.checkValidity()) {
      event.preventDefault();
      form.classList.add('was-validated');
      form.reportValidity();
    }
  }, true);
  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-bs-target="#Modalclick"], [data-target="#Modalclick"]');
    if (!trigger) return;
    var scope = trigger.closest('form') || trigger.parentElement;
    var quantity = scope && scope.querySelector('input.detail-quantity');
    var target = document.querySelector('#Modalclick input[name="quantity"]');
    if (target && quantity) target.value = quantity.value;
  });
  window.addEventListener('pageshow', function () {
    document.querySelectorAll('[data-lead-form]').forEach(function (form) {
      delete form.dataset.sending;
      form.querySelectorAll('[data-lead-disabled]').forEach(function (button) {
        button.disabled = false;
        button.removeAttribute('data-lead-disabled');
      });
    });
  });
  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form.matches('[data-lead-form]') || event.defaultPrevented) return;
    if (!form.checkValidity()) { event.preventDefault(); form.reportValidity(); return; }
    if (form.dataset.sending === '1') { event.preventDefault(); return; }
    form.dataset.sending = '1';
    setTimeout(function () {
      form.querySelectorAll('button[type="submit"]').forEach(function (button) {
        if (!button.disabled) { button.dataset.leadDisabled = '1'; button.disabled = true; }
      });
    }, 0);
  });
}());
