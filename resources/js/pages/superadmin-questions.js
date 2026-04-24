function initSuperadminQuestions() {
	const configElement = document.getElementById('superadmin-questions-config');
	if (!configElement) return;

	let config = {};
	try {
		config = JSON.parse(configElement.textContent || '{}');
	} catch (error) {
		console.error('Failed to parse superadmin questions config.', error);
		return;
	}

	const materialData = Array.isArray(config.materialOptions) ? config.materialOptions : [];
	const materialFieldOrder = ['mapel', 'curriculum', 'subelement', 'unit', 'sub_unit'];
	const optionLabels = Array.isArray(config.optionLabels) ? config.optionLabels : [];
	const updateRouteTemplate = config.updateRouteTemplate || '';

	const editModal = document.getElementById('edit-question-modal');
	const form = document.getElementById('edit-question-form');
	const createOptionList = document.querySelector('[data-option-list="create"]');
	const editOptionList = document.querySelector('[data-option-list="edit"]');
	const modals = Array.from(document.querySelectorAll('[id$="-modal"]'));
	const materialPickers = new Map();

	const fields = {
		questionType: document.getElementById('edit-question-type'),
		questionText: document.getElementById('edit-question-text'),
		answerKey: document.getElementById('edit-answer-key'),
		isActive: document.getElementById('edit-is-active'),
		explanation: document.getElementById('edit-explanation'),
		readingPassage: document.getElementById('edit-reading-passage'),
	};

	const importDropdownButton = document.getElementById('import-dropdown-btn');
	const importDropdownMenu = document.getElementById('import-dropdown-menu');

	importDropdownButton?.addEventListener('click', (event) => {
		event.stopPropagation();
		importDropdownMenu?.classList.toggle('hidden');
	});

	document.addEventListener('click', (event) => {
		if (!(event.target instanceof Element) || event.target.closest('#import-dropdown-wrapper')) {
			return;
		}

		importDropdownMenu?.classList.add('hidden');
	});

	const openModal = (modal) => {
		if (!modal) return;
		modal.classList.remove('hidden');
		modal.classList.add('flex');
	};

	const closeModal = (modal) => {
		if (!modal) return;
		modal.classList.add('hidden');
		modal.classList.remove('flex');
	};

	const closeAllMaterialDropdowns = () => {
		materialPickers.forEach((picker) => {
			Object.values(picker.fields).forEach((field) => field.dropdown.classList.add('hidden'));
		});
	};

	const buildMaterialPicker = (container) => {
		const fieldMap = {};

		materialFieldOrder.forEach((name) => {
			const root = container.querySelector(`[data-material-field="${name}"]`);
			if (!root) return;

			fieldMap[name] = {
				root,
				valueInput: root.querySelector('[data-material-value]'),
				trigger: root.querySelector('[data-material-trigger]'),
				label: root.querySelector('[data-material-label]'),
				dropdown: root.querySelector('[data-material-dropdown]'),
				search: root.querySelector('[data-material-search]'),
				options: root.querySelector('[data-material-options]'),
			};
		});

		const picker = {
			container,
			fields: fieldMap,
			state: {
				mapel: '',
				curriculum: '',
				subelement: '',
				unit: '',
				sub_unit: '',
			},
		};

		const getFilteredDataset = (fieldName) => {
			const fieldIndex = materialFieldOrder.indexOf(fieldName);

			return materialData.filter((item) => {
				return materialFieldOrder.slice(0, fieldIndex).every((key) => !picker.state[key] || item[key] === picker.state[key]);
			});
		};

		const getOptions = (fieldName) => {
			const searchTerm = picker.fields[fieldName].search.value.trim().toLowerCase();
			const options = [...new Set(getFilteredDataset(fieldName).map((item) => item[fieldName]).filter(Boolean))];

			return options.filter((option) => option.toLowerCase().includes(searchTerm));
		};

		const updateFieldUI = (fieldName) => {
			const field = picker.fields[fieldName];
			const options = getOptions(fieldName);
			const selectedValue = picker.state[fieldName];
			const placeholderMap = {
				mapel: 'Pilih mapel',
				curriculum: 'Pilih kurikulum',
				subelement: 'Pilih subelement',
				unit: 'Pilih unit',
				sub_unit: 'Pilih sub unit',
			};

			field.valueInput.value = selectedValue || '';
			field.label.textContent = selectedValue || placeholderMap[fieldName];
			field.trigger.disabled = options.length === 0 && !selectedValue;
			field.trigger.classList.toggle('cursor-not-allowed', field.trigger.disabled);
			field.trigger.classList.toggle('opacity-60', field.trigger.disabled);
			field.options.innerHTML = '';

			if (options.length === 0) {
				const emptyState = document.createElement('div');
				emptyState.className = 'rounded-xl px-3 py-2 text-sm text-muted';
				emptyState.textContent = 'Tidak ada data yang cocok.';
				field.options.appendChild(emptyState);
				return;
			}

			options.forEach((option) => {
				const button = document.createElement('button');
				button.type = 'button';
				button.className = `flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm transition hover:bg-slate-100 dark:hover:bg-slate-800 ${option === selectedValue ? 'bg-blue-50 font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300' : 'text-slate-700 dark:text-slate-200'}`;
				button.innerHTML = `<span>${option}</span>${option === selectedValue ? '<i class="fa-solid fa-check text-xs"></i>' : ''}`;
				button.addEventListener('click', () => {
					picker.state[fieldName] = option;

					const currentIndex = materialFieldOrder.indexOf(fieldName);
					materialFieldOrder.slice(currentIndex + 1).forEach((key) => {
						picker.state[key] = '';
						picker.fields[key].search.value = '';
					});

					refreshPicker();

					const nextFieldName = materialFieldOrder[currentIndex + 1];
					if (nextFieldName) {
						openFieldDropdown(nextFieldName);
					} else {
						closeAllMaterialDropdowns();
					}
				});
				field.options.appendChild(button);
			});
		};

		const refreshPicker = () => {
			materialFieldOrder.forEach((name) => updateFieldUI(name));
		};

		const openFieldDropdown = (fieldName) => {
			closeAllMaterialDropdowns();
			const field = picker.fields[fieldName];
			if (!field || field.trigger.disabled) return;
			field.dropdown.classList.remove('hidden');
			field.search.focus();
			field.search.select();
		};

		materialFieldOrder.forEach((fieldName) => {
			const field = picker.fields[fieldName];
			field.trigger.addEventListener('click', () => {
				if (field.dropdown.classList.contains('hidden')) {
					openFieldDropdown(fieldName);
				} else {
					field.dropdown.classList.add('hidden');
				}
			});
			field.search.addEventListener('input', () => updateFieldUI(fieldName));
		});

		container.querySelector('[data-material-reset]')?.addEventListener('click', () => {
			materialFieldOrder.forEach((name) => {
				picker.state[name] = '';
				picker.fields[name].search.value = '';
			});
			refreshPicker();
			closeAllMaterialDropdowns();
		});

		refreshPicker();

		picker.setValues = (values = {}) => {
			materialFieldOrder.forEach((name) => {
				picker.state[name] = values[name] || '';
				picker.fields[name].search.value = '';
			});
			refreshPicker();
		};

		return picker;
	};

	const renderOptionFields = (container, values = []) => {
		const entries = values.length ? values : ['', '', '', ''];
		container.innerHTML = '';

		entries.forEach((value, index) => {
			const label = optionLabels[index] ?? `O${index + 1}`;
			const row = document.createElement('div');
			row.className = 'flex items-center gap-3 rounded-2xl border border-border bg-slate-50/80 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/80';
			row.innerHTML = `
				<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">${label}</span>
				<input class="input border-0 bg-transparent px-0" name="options[]" placeholder="Tulis jawaban ${label}" value="${String(value ?? '').replace(/"/g, '&quot;')}">
			`;
			container.appendChild(row);
		});
	};

	const appendOptionField = (container) => {
		const values = Array.from(container.querySelectorAll('input[name="options[]"]')).map((input) => input.value);
		values.push('');
		renderOptionFields(container, values);
	};

	if (createOptionList) {
		renderOptionFields(createOptionList);
	}

	document.querySelectorAll('[data-material-picker]').forEach((container) => {
		const picker = buildMaterialPicker(container);
		materialPickers.set(container.dataset.materialPicker, picker);
	});

	document.querySelectorAll('[data-open-modal]').forEach((button) => {
		button.addEventListener('click', () => {
			if (button.dataset.openModal === 'create-question-modal') {
				materialPickers.get('create')?.setValues();
			}
			openModal(document.getElementById(button.dataset.openModal));
		});
	});

	document.querySelectorAll('[data-close-modal]').forEach((button) => {
		button.addEventListener('click', () => {
			closeAllMaterialDropdowns();
			closeModal(document.getElementById(button.dataset.closeModal));
		});
	});

	modals.forEach((modal) => {
		modal.addEventListener('click', (event) => {
			if (event.target === modal) {
				closeAllMaterialDropdowns();
				closeModal(modal);
			}
		});
	});

	document.addEventListener('click', (event) => {
		if (!(event.target instanceof Element) || event.target.closest('[data-material-field]')) {
			return;
		}
		closeAllMaterialDropdowns();
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape') {
			closeAllMaterialDropdowns();
			modals.forEach((modal) => {
				if (!modal.classList.contains('hidden')) {
					closeModal(modal);
				}
			});
		}
	});

	document.querySelectorAll('[data-edit-question]').forEach((button) => {
		button.addEventListener('click', () => {
			const raw = button.getAttribute('data-edit-question');
			if (!raw || !form || !updateRouteTemplate) return;

			const data = JSON.parse(raw);
			form.action = updateRouteTemplate.replace('__ID__', data.id);
			const jenjangInput = document.getElementById('edit-jenjang-id');
			if (jenjangInput) jenjangInput.value = data.jenjang_id ?? '';
			if (fields.questionType) fields.questionType.value = data.question_type ?? 'multiple_choice';
			materialPickers.get('edit')?.setValues({
				mapel: data.material_mapel ?? '',
				curriculum: data.material_curriculum ?? '',
				subelement: data.material_subelement ?? '',
				unit: data.material_unit ?? '',
				sub_unit: data.material_sub_unit ?? '',
			});
			if (fields.questionText) fields.questionText.value = data.question_text ?? '';
			if (fields.readingPassage) fields.readingPassage.value = data.reading_passage ?? '';
			if (editOptionList) renderOptionFields(editOptionList, data.options ?? []);
			if (fields.answerKey) fields.answerKey.value = data.answer_key ?? '';
			if (fields.isActive) fields.isActive.value = data.is_active ?? '1';
			if (fields.explanation) fields.explanation.value = data.explanation ?? '';
			openModal(editModal);
			fields.questionText?.focus();
		});
	});

	document.querySelectorAll('.toggle-reading-passage').forEach((button) => {
		button.addEventListener('click', () => {
			const target = document.getElementById(button.dataset.target);
			const chevron = button.querySelector('[data-rp-chevron]');
			target?.classList.toggle('hidden');
			chevron?.classList.toggle('rotate-180');
		});
	});

	document.querySelectorAll('.toggle-explanation').forEach((button) => {
		button.addEventListener('click', () => {
			const target = document.getElementById(button.dataset.target);
			const chevron = button.querySelector('[data-ex-chevron]');
			target?.classList.toggle('hidden');
			chevron?.classList.toggle('rotate-180');
		});
	});

	document.querySelectorAll('[data-option-add]').forEach((button) => {
		button.addEventListener('click', () => {
			const target = button.dataset.optionAdd === 'edit' ? editOptionList : createOptionList;
			if (target) appendOptionField(target);
		});
	});
}

document.addEventListener('DOMContentLoaded', initSuperadminQuestions);
