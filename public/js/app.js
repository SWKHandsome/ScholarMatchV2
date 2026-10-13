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

function updateAcademicResult(academicRow, qualificationChanged = false) {
  const qualification = academicRow.querySelector('select[name$="[qualification]"]');
  const resultType = academicRow.querySelector('input[name$="[result_type]"]');
  const resultValue = academicRow.querySelector('[data-result-value]');
  if (!qualification || !resultType || !resultValue) return;
  if (qualificationChanged || !resultType.value) {
    resultType.value = qualification.selectedOptions[0]?.dataset.resultType || '';
    if (qualificationChanged) {
      resultValue.value = '';
      academicRow.querySelector('[data-saved-result-format]')?.setAttribute('hidden', '');
    }
  }
  const label = resultValue.closest('.form-field').querySelector('label');
  const isMinimum = academicRow.classList.contains('route-row');
  academicRow.querySelector('[data-result-input]').hidden = !['cgpa', 'as_count'].includes(resultType.value);
  academicRow.querySelector('[data-academic-help]').textContent = resultType.value === 'other'
    ? (isMinimum ? 'Add the result requirement to Special requirements below for manual verification.' : 'Enter the result details in the notes below. These results require manual verification.')
    : (resultType.value ? 'Result format is selected automatically from the qualification.' : `Choose a qualification to see the ${isMinimum ? 'minimum ' : ''}result input.`);
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

document.querySelectorAll('[data-academic-result]').forEach(academicRow => updateAcademicResult(academicRow));
document.addEventListener('change', event => {
  if (event.target.matches('select[name$="[qualification]"]')) {
    const academicRow = event.target.closest('[data-academic-result]');
    if (academicRow) updateAcademicResult(academicRow, true);
  }
});
document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => {
  if (!window.confirm(form.dataset.confirm)) event.preventDefault();
}));

document.querySelectorAll('[data-selection-dropdown]').forEach(selectionField => {
  const select = selectionField.querySelector('select');
  const choices = selectionField.querySelector('[data-selected-choices]');
  const status = selectionField.querySelector('[data-selection-status]');
  const options = [...select.options].filter(option => option.value !== '');
  const selectedValues = new Set(options.filter(option => option.selected).map(option => option.value));
  select.multiple = false;
  select.removeAttribute('size');
  select.removeAttribute('name');

  const renderChoices = () => {
    choices.replaceChildren();
    options.forEach(option => {
      option.disabled = selectedValues.has(option.value);
      if (!option.disabled) return;
      const choice = document.createElement('li');
      const label = document.createElement('span');
      label.textContent = option.textContent;
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = selectionField.dataset.selectionDropdown;
      input.value = option.value;
      const remove = document.createElement('button');
      remove.type = 'button';
      remove.textContent = '×';
      remove.setAttribute('aria-label', `Remove ${option.textContent}`);
      remove.addEventListener('click', () => {
        selectedValues.delete(option.value);
        renderChoices();
        select.focus();
        select.dispatchEvent(new Event('change', { bubbles: true }));
      });
      choice.append(label, input, remove);
      choices.append(choice);
    });
    select.value = '';
    select.options[0].textContent = options.length > 0 && selectedValues.size === options.length ? 'All available options selected' : 'Select an option to add';
    choices.hidden = selectedValues.size === 0;
    status.hidden = false;
    status.textContent = selectedValues.size === 0 ? 'No restriction' : `${selectedValues.size} ${selectedValues.size === 1 ? 'choice' : 'choices'} selected`;
  };
  select.addEventListener('change', () => {
    if (!select.value) return;
    selectedValues.add(select.value);
    renderChoices();
  });
  renderChoices();
});

document.querySelectorAll('[data-income-form]').forEach(incomeForm => {
  const categories = incomeForm.querySelector('[data-income-categories]');
  const preservedRule = incomeForm.querySelector('[data-preserve-income]');
  const updateIncomeRequirement = () => {
    if (preservedRule) preservedRule.disabled = [...categories.querySelectorAll('input[type=hidden], option:checked')].some(input => input.value !== '');
  };
  incomeForm.addEventListener('change', updateIncomeRequirement);
  updateIncomeRequirement();
});
