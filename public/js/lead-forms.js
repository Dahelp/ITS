(function () {
  'use strict';
  if (window.itsLeadFormsReady) return;
  window.itsLeadFormsReady = true;
  var sequence = 0, pending = {}, queue = Promise.resolve();
  function status(form, message) {
    var node = form.querySelector('[data-lead-status]');
    if (!node) {
      node = document.createElement('p');
      node.dataset.leadStatus = '1';
      node.className = 'alert alert-danger';
      node.setAttribute('role', 'alert');
      form.appendChild(node);
    }
    node.hidden = !message;
    node.textContent = message;
  }
  function validate(form) {
    form.noValidate = true;
    phoneFields(form).forEach(validatePhone);
    var first = null;
    Array.from(form.elements).forEach(function (field) {
      if (!field.willValidate) return;
      var invalid = !field.checkValidity();
      field.setAttribute('aria-invalid', String(invalid));
      var node = field._leadError;
      if (invalid && !node) {
        node = document.createElement('span');
        node.id = 'lead-field-error-' + (++sequence);
        node.className = 'invalid-feedback';
        field.insertAdjacentElement('afterend', node);
        field._leadError = node;
        field.setAttribute('aria-describedby', ((field.getAttribute('aria-describedby') || '') + ' ' + node.id).trim());
      }
      if (node) { node.textContent = invalid ? field.validationMessage : ''; node.hidden = !invalid; node.style.display = invalid ? 'block' : 'none'; }
      if (invalid && !first) first = field;
    });
    if (first) { form.classList.add('was-validated'); first.focus(); return false; }
    return true;
  }
  function busy(form, enabled) {
    form.setAttribute('aria-busy', String(enabled));
    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (button) {
      if (enabled && !button.disabled) { button.dataset.leadDisabled = '1'; button.disabled = true; }
      if (!enabled && button.dataset.leadDisabled) { button.disabled = false; delete button.dataset.leadDisabled; }
    });
  }
  function tokenField(form) { return form.querySelector('input[name="lead_token"]'); }
  function prepare(form) {
    var field = tokenField(form);
    if (!field) return Promise.reject(new Error('Missing token field'));
    if (/^[a-f0-9]{64}$/.test(field.value)) return Promise.resolve(field.value);
    var scope = field.dataset.leadScope || (form.querySelector('input[name="oneclick"]') ? 'oneclick' : 'callback');
    if (!pending[scope]) {
      // Different scopes also wait for the first session cookie, avoiding a race
      // that would otherwise create two independent sessions on a first visit.
      var request = queue.catch(function () {}).then(function () {
        var controller = new AbortController();
        var timer = setTimeout(function () { controller.abort(); }, 12000);
        return fetch((window.APP_PATH || '') + '/session/lead-token', {
          method: 'POST', credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
          headers: {'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded'},
          body: 'scope=' + encodeURIComponent(scope)
        }).then(function (response) {
          if (!response.ok) throw new Error('Token request failed');
          return response.json();
        }).then(function (data) {
          if (!data.ok || !/^[a-f0-9]{64}$/.test(data.token)) throw new Error('Invalid token');
          return data.token;
        }).finally(function () { clearTimeout(timer); });
      });
      pending[scope] = request;
      queue = request;
      request.then(function () { delete pending[scope]; }, function () { delete pending[scope]; });
    }
    return pending[scope].then(function (token) { field.value = token; return token; });
  }
  function warm(form) {
    if (form && form.matches('[data-lead-form]')) prepare(form).catch(function () {});
  }
  document.addEventListener('focusin', function (event) { warm(event.target.closest('[data-lead-form]')); });
  document.addEventListener('show.bs.modal', function (event) {
    if (event.target.matches('[data-lead-form]')) warm(event.target);
    event.target.querySelectorAll('[data-lead-form]').forEach(warm);
  });
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
    if (!field.closest('[data-lead-form]')) return;
    if (field.matches('input[name="phone"], input[name="tell_modal"]')) validatePhone(field);
    if (field._leadError && field.checkValidity()) {
      field._leadError.hidden = true;
      field._leadError.textContent = '';
      field._leadError.style.display = 'none';
      field.setAttribute('aria-invalid', 'false');
    }
  }, true);
  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form.matches('[data-lead-form]')) return;
    if (form.dataset.sending === '1' || form.dataset.tokenLoading === '1') { event.preventDefault(); return; }
    if (!validate(form)) { event.preventDefault(); return; }
    var field = tokenField(form);
    if (!field || !/^[a-f0-9]{64}$/.test(field.value)) {
      event.preventDefault();
      form.dataset.tokenLoading = '1';
      status(form, '');
      busy(form, true);
      var submitter = event.submitter;
      prepare(form).then(function () {
        delete form.dataset.tokenLoading;
        busy(form, false);
        if (submitter && submitter.form === form) form.requestSubmit(submitter);
        else form.requestSubmit();
      }).catch(function () {
        delete form.dataset.tokenLoading;
        busy(form, false);
        status(form, 'Не удалось подготовить отправку. Проверьте подключение и повторите попытку. Введённые данные сохранены в форме.');
      });
      return;
    }
    status(form, '');
    form.dataset.sending = '1';
    setTimeout(function () {
      if (event.defaultPrevented) { delete form.dataset.sending; return; }
      busy(form, true);
    }, 0);
  }, true);
  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-bs-target="#Modalclick"], [data-target="#Modalclick"]');
    if (!trigger) return;
    var scope = trigger.closest('form') || trigger.parentElement;
    var quantity = scope && scope.querySelector('input.detail-quantity');
    var target = document.querySelector('#Modalclick input[name="quantity"]');
    if (target && quantity) target.value = quantity.value;
  });
  window.addEventListener('pageshow', function (event) {
    document.querySelectorAll('[data-lead-form]').forEach(function (form) {
      delete form.dataset.sending;
      delete form.dataset.tokenLoading;
      form.noValidate = true;
      busy(form, false);
      if (event.persisted && tokenField(form)) tokenField(form).value = '';
    });
  });
}());
