(function(){
  'use strict';
  var modal = document.getElementById('ModalAvailability');
  if (!modal) return;
  modal.addEventListener('show.bs.modal', function(event){
    var button = event.relatedTarget;
    if (!button || !button.hasAttribute('data-product-id')) return;
    var form = modal.querySelector('form');
    form.reset();
    form.classList.remove('was-validated');
    form.querySelectorAll('.is-valid,.is-invalid').forEach(function(input){input.classList.remove('is-valid','is-invalid');});
    form.action = button.getAttribute('data-product-url');
    form.elements.product_id.value = button.getAttribute('data-product-id');
    form.elements.name_tovar.value = button.getAttribute('data-product-name');
    modal.querySelector('.availability-product__name').textContent = button.getAttribute('data-product-name');
    form.querySelector('button[type="submit"]').disabled = true;
  });
})();
