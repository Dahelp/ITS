const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const events = {};
const windowEvents = {};
const form = {
  dataset: {}, classList: { add() {} },
  matches: () => true,
  querySelectorAll: () => [phone],
  checkValidity: () => phone.message === '',
  reportValidity() { this.reported = true; }
};
const phone = {
  value: '', message: '',
  matches: () => true, closest: () => form,
  setCustomValidity(message) { this.message = message; }
};
const quantity = { value: '4' };
const targetQuantity = { value: '1' };
const button = { disabled: false, dataset: {}, removeAttribute() {} };
const document = {
  addEventListener(name, fn, capture) { (events[name] ||= []).push({ fn, capture }); },
  querySelector: () => targetQuantity,
  querySelectorAll: () => [form]
};
const timers = [];
vm.runInNewContext(fs.readFileSync('public/js/lead-forms.js', 'utf8'), {
  document, window: { addEventListener(name, fn) { windowEvents[name] = fn; } },
  setTimeout(fn) { timers.push(fn); }
});
function submit() {
  const event = { target: form, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } };
  events.submit.forEach(({ fn }) => fn(event));
  return event;
}
for (const value of ['+7 (9', '+7 (999) 999-99-99', '+79123456789abc', '+79123456789\n', '']) {
  phone.value = value;
  assert.equal(submit().defaultPrevented, true, value);
  assert.equal(form.dataset.sending, undefined);
}
for (const value of ['+7 (912) 345-67-89', '8 (912) 345-67-89']) {
  phone.value = value;
  events.input[0].fn({ target: phone });
  assert.equal(phone.message, '');
}
events.click[0].fn({ target: { closest: () => ({ closest: () => null, parentElement: { querySelector: () => quantity } }) } });
assert.equal(targetQuantity.value, '4');
form.querySelectorAll = selector => selector.includes('button') ? [button] : [phone];
assert.equal(submit().defaultPrevented, false);
assert.equal(submit().defaultPrevented, true);
timers.forEach(fn => fn());
assert.equal(button.disabled, true);
form.querySelectorAll = () => [button];
windowEvents.pageshow();
assert.equal(form.dataset.sending, undefined);
assert.equal(button.disabled, false);
console.log('Lead forms client: strict phone, quantity, duplicate submit and back navigation OK');
