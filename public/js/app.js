document.querySelector('.menu-toggle')?.addEventListener('click', event => {
  const open = document.body.classList.toggle('menu-open');
  event.currentTarget.setAttribute('aria-expanded', String(open));
});
document.addEventListener('click', event => {
  if (event.target.closest('.remove-row')) event.target.closest('.income-row, .academic-row, .route-row')?.remove();
  const add = event.target.closest('[data-add-row]');
  if (!add) return;
  const kind = add.dataset.addRow;
  const container = document.getElementById(`${kind}-rows`);
  const template = document.getElementById(`${kind}-template`);
  if (template) {
    const index = Date.now();
    container.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', index));
    updateAcademicResult(container.lastElementChild);
  } else if (kind === 'income') {
    const index = Date.now();
    container.insertAdjacentHTML('beforeend', `<div class="row income-row border-bottom py-2"><div class="col-md-4 form-field"><label for="income-name-${index}">Category name</label><input class="form-control" id="income-name-${index}" name="categories[${index}][name]" required></div><div class="col-md-3 form-field"><label for="income-min-${index}">Minimum (RM)</label><input class="form-control" id="income-min-${index}" name="categories[${index}][minimum]" type="number" step="0.01" min="0" required></div><div class="col-md-3 form-field"><label for="income-max-${index}">Maximum (RM)</label><input class="form-control" id="income-max-${index}" name="categories[${index}][maximum]" type="number" step="0.01" min="0"></div><div class="col-md-2 d-flex align-items-center"><button type="button" class="btn btn-outline remove-row">Remove</button></div></div>`);
  }
});

function updateAcademicResult(academicRow) {
  const resultType = academicRow.querySelector('select[name$="[result_type]"]');
  const resultValue = academicRow.querySelector('[data-result-value]');
  if (!resultType || !resultValue) return;
  const label = resultValue.closest('.form-field').querySelector('label');
  const isMinimum = academicRow.classList.contains('route-row');
  if (resultType.value === 'cgpa') {
    label.textContent = isMinimum ? 'Minimum CGPA (out of 4.00)' : 'CGPA (out of 4.00)';
    resultValue.max = '4';
    resultValue.step = '0.0001';
  } else if (resultType.value === 'as_count') {
    label.textContent = isMinimum ? 'Minimum number of As' : 'Number of As';
    resultValue.max = '30';
    resultValue.step = '1';
  } else {
    label.textContent = isMinimum ? 'Minimum result' : 'Result value (leave empty if unknown)';
    resultValue.removeAttribute('max');
    resultValue.step = '0.0001';
  }
}

document.querySelectorAll('[data-academic-result]').forEach(updateAcademicResult);
document.addEventListener('change', event => {
  if (event.target.matches('select[name$="[result_type]"]')) {
    const academicRow = event.target.closest('[data-academic-result]');
    if (academicRow) updateAcademicResult(academicRow);
  }
});
document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
  if (!window.confirm(form.dataset.confirm)) event.preventDefault();
}));

document.querySelectorAll('[data-income-form]').forEach(incomeForm => {
  const categories = incomeForm.querySelector('[data-income-categories]');
  const preservedRule = incomeForm.querySelector('[data-preserve-income]');
  const updateIncomeRequirement = () => {
    if (preservedRule) preservedRule.disabled = !!categories.querySelector('input:checked');
  };
  incomeForm.addEventListener('change', updateIncomeRequirement);
  updateIncomeRequirement();
});
