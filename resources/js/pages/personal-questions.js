function initPersonalQuestionsPage() {
	const configEl = document.getElementById('personal-questions-config');
	if (!configEl) return;

	let config = { optionLabels: [] };
	try {
		config = JSON.parse(configEl.textContent || '{}');
	} catch {
		config = { optionLabels: [] };
	}

	const optionLabels = Array.isArray(config.optionLabels) ? config.optionLabels : [];

	const buildOptionRow = (label, value = '') => {
		const row = document.createElement('div');
		row.className = 'flex items-center gap-3 rounded-2xl border border-border bg-slate-50/80 px-3 py-2 dark:border-slate-700 dark:bg-slate-900/80';
		row.innerHTML = `
			<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">${label}</span>
			<input name="options[]" class="input border-0 bg-transparent px-0" placeholder="Tulis jawaban ${label}" value="${String(value ?? '').replace(/"/g, '&quot;')}">
		`;
		return row;
	};

	const initQuestionForm = ({
		optionList,
		addButton,
		questionType,
		objectiveOptions,
		answerSelect,
		answerText,
		answerHelp,
	}) => {
		if (!optionList || !addButton || !questionType || !objectiveOptions || !answerSelect || !answerText || !answerHelp) {
			return;
		}

		const getValues = () => Array.from(optionList.querySelectorAll('input[name="options[]"]')).map((input) => input.value);

		const syncAnswerOptions = () => {
			const values = getValues();
			const previous = answerSelect.getAttribute('data-current-answer') || answerSelect.value;
			answerSelect.innerHTML = '<option value="">Pilih jawaban benar</option>';

			Array.from({ length: Math.min(values.length, 5) }, (_, index) => optionLabels[index]).forEach((label, index) => {
				const option = document.createElement('option');
				option.value = label;
				option.textContent = values[index] ? `${label} - ${values[index]}` : label;
				answerSelect.appendChild(option);
			});

			if (previous && Array.from(answerSelect.options).some((option) => option.value === previous)) {
				answerSelect.value = previous;
			}
		};

		const syncTypeState = () => {
			const isObjective = ['PG', 'Checklist'].includes(questionType.value);
			objectiveOptions.classList.toggle('hidden', !isObjective);
			addButton.disabled = !isObjective;
			answerSelect.classList.toggle('hidden', !isObjective);
			answerText.classList.toggle('hidden', isObjective);
			answerSelect.disabled = !isObjective;
			answerText.disabled = isObjective;

			optionList.querySelectorAll('input[name="options[]"]').forEach((input) => {
				input.disabled = !isObjective;
			});

			answerHelp.textContent = isObjective
				? 'Pilih huruf jawaban yang benar sesuai opsi aktif.'
				: 'Isi jawaban teks singkat yang dianggap benar.';
		};

		addButton.addEventListener('click', () => {
			const values = getValues();
			if (values.length >= 5) return;
			optionList.appendChild(buildOptionRow(optionLabels[values.length] ?? `O${values.length + 1}`));
			syncAnswerOptions();
		});

		optionList.addEventListener('input', syncAnswerOptions);
		questionType.addEventListener('change', syncTypeState);
		syncAnswerOptions();
		syncTypeState();
	};

	const initImagePreview = (input) => {
		const key = input.getAttribute('data-image-input');
		if (!key) return;

		const wrap = document.querySelector(`[data-image-preview-wrap="${key}"]`);
		const image = document.querySelector(`[data-image-preview="${key}"]`);
		if (!wrap || !image) return;

		input.addEventListener('change', () => {
			const file = input.files && input.files[0] ? input.files[0] : null;
			if (!file) return;
			if (file.size > 2 * 1024 * 1024) {
				alert('Ukuran gambar maksimal 2 MB.');
				input.value = '';
				return;
			}
			image.src = URL.createObjectURL(file);
			wrap.classList.remove('hidden');
		});
	};

	const initLiveFilter = () => {
		const filterForm = document.querySelector('[data-personal-questions-filter-form]');
		const filterInput = filterForm?.querySelector('[data-live-search][name="q"]');
		const tableWrap = document.getElementById('personal-questions-table-wrap');
		if (!filterForm || !filterInput || !tableWrap) return;

		let timer = null;
		let abortController = null;
		let isComposing = false;

		const buildUrl = () => {
			const action = filterForm.getAttribute('action') || window.location.pathname;
			const url = new URL(action, window.location.origin);
			url.search = new URLSearchParams(new FormData(filterForm)).toString();
			return url;
		};

		const fetchAndReplace = async (url = buildUrl()) => {
			if (abortController) abortController.abort();
			abortController = new AbortController();
			tableWrap.classList.add('opacity-60', 'pointer-events-none');

			try {
				const res = await fetch(url.toString(), {
					method: 'GET',
					headers: { 'X-Requested-With': 'XMLHttpRequest' },
					signal: abortController.signal,
				});
				if (!res.ok) throw new Error('Request failed');
				const html = await res.text();
				const doc = new DOMParser().parseFromString(html, 'text/html');
				const nextWrap = doc.getElementById('personal-questions-table-wrap');
				if (nextWrap) tableWrap.innerHTML = nextWrap.innerHTML;
				history.replaceState({}, '', url.pathname + (url.search ? ('?' + url.searchParams.toString()) : ''));
			} catch {
				// ignore abort / request error
			} finally {
				tableWrap.classList.remove('opacity-60', 'pointer-events-none');
			}
		};

		const scheduleFetch = (delay = 350) => {
			if (isComposing) return;
			window.clearTimeout(timer);
			timer = window.setTimeout(fetchAndReplace, delay);
		};

		filterInput.addEventListener('compositionstart', () => {
			isComposing = true;
		});
		filterInput.addEventListener('compositionend', () => {
			isComposing = false;
			scheduleFetch(150);
		});
		filterInput.addEventListener('input', () => scheduleFetch(350));
		filterForm.querySelectorAll('select').forEach((select) => {
			select.addEventListener('change', () => scheduleFetch(0));
		});
		filterForm.addEventListener('submit', (event) => {
			event.preventDefault();
			scheduleFetch(0);
		});
		tableWrap.addEventListener('click', (event) => {
			const link = event.target.closest('a[href]');
			if (!link || !link.href.includes('page=')) return;
			event.preventDefault();
			fetchAndReplace(new URL(link.href, window.location.origin));
		});
	};

	const initModalTriggers = () => {
		document.querySelectorAll('[data-modal-open]').forEach((button) => {
			button.addEventListener('click', () => {
				const targetId = button.getAttribute('data-modal-open');
				const modal = targetId ? document.getElementById(targetId) : null;
				if (!modal) return;
				modal.classList.remove('hidden');
			});
		});

		document.querySelectorAll('[data-modal-close]').forEach((button) => {
			button.addEventListener('click', () => {
				const targetId = button.getAttribute('data-modal-close');
				const modal = targetId ? document.getElementById(targetId) : null;
				if (!modal) return;
				modal.classList.add('hidden');
			});
		});

		document.querySelectorAll('[id^="modal-"]').forEach((modal) => {
			modal.addEventListener('click', (event) => {
				if (event.target === modal) {
					modal.classList.add('hidden');
				}
			});
		});
	};

	initQuestionForm({
		optionList: document.querySelector('[data-option-list="guru-personal"]'),
		addButton: document.querySelector('[data-option-add="guru-personal"]'),
		questionType: document.getElementById('guru-personal-question-type'),
		objectiveOptions: document.querySelector('[data-objective-options]'),
		answerSelect: document.getElementById('guru-personal-answer-key-select'),
		answerText: document.getElementById('guru-personal-answer-key-text'),
		answerHelp: document.getElementById('guru-personal-answer-key-help'),
	});

	document.querySelectorAll('[data-edit-personal-form]').forEach((form) => {
		initQuestionForm({
			optionList: form.querySelector('[data-edit-option-list]'),
			addButton: form.querySelector('[data-edit-option-add]'),
			questionType: form.querySelector('[data-edit-question-type]'),
			objectiveOptions: form.querySelector('[data-edit-objective-options]'),
			answerSelect: form.querySelector('[data-edit-answer-select]'),
			answerText: form.querySelector('[data-edit-answer-text]'),
			answerHelp: form.querySelector('[data-edit-answer-help]'),
		});
	});

	document.querySelectorAll('[data-image-input]').forEach(initImagePreview);
	initLiveFilter();
	initModalTriggers();
}

document.addEventListener('DOMContentLoaded', initPersonalQuestionsPage);
