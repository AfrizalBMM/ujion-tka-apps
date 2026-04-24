function initSuperadminBankBuilder() {
	const configElement = document.getElementById('superadmin-bank-builder-config');
	if (!configElement) return;

	let config = {};
	try {
		config = JSON.parse(configElement.textContent || '{}');
	} catch (error) {
		console.error('Failed to parse superadmin bank builder config.', error);
		return;
	}

	const slotSisa = Number.parseInt(config.slotSisa ?? '0', 10) || 0;
	const emptySelectionMessage = config.emptySelectionMessage || 'Pilih minimal satu soal terlebih dahulu.';
	const processingImportHtml = config.processingImportHtml || '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Memproses...';

	const form = document.getElementById('import-bank-form');
	const selectAll = document.getElementById('select-all-checkbox');
	const footer = document.getElementById('import-footer');
	const selectedCountEl = document.getElementById('selected-count');
	const footerCountEl = document.getElementById('footer-count');
	const footerSlotEl = document.getElementById('footer-slot');
	const deselectBtn = document.getElementById('deselect-all-btn');
	const previewBtn = document.getElementById('preview-btn');
	const modal = document.getElementById('preview-modal');
	const previewList = document.getElementById('preview-list');
	const previewCount = document.getElementById('preview-count');
	const quotaWarning = document.getElementById('quota-warning');
	const closePreviewBtn = document.getElementById('close-preview-btn');
	const cancelPreviewBtn = document.getElementById('cancel-preview-btn');
	const confirmImportBtn = document.getElementById('confirm-import-btn');
	const checkboxes = Array.from(document.querySelectorAll('.soal-checkbox'));

	if (
		!form ||
		!selectAll ||
		!footer ||
		!selectedCountEl ||
		!footerCountEl ||
		!footerSlotEl ||
		!deselectBtn ||
		!previewBtn ||
		!modal ||
		!previewList ||
		!previewCount ||
		!quotaWarning ||
		!closePreviewBtn ||
		!cancelPreviewBtn ||
		!confirmImportBtn ||
		checkboxes.length === 0
	) {
		return;
	}

	const getChecked = () => checkboxes.filter((checkbox) => checkbox.checked);

	const openModal = () => {
		modal.classList.remove('hidden');
		modal.classList.add('flex');
	};

	const closeModal = () => {
		modal.classList.add('hidden');
		modal.classList.remove('flex');
	};

	const updateUI = () => {
		const checkedCount = getChecked().length;
		const full = checkedCount >= slotSisa;

		selectedCountEl.textContent = String(checkedCount);
		footerCountEl.textContent = String(checkedCount);
		footerSlotEl.textContent = String(Math.max(0, slotSisa - checkedCount));
		footer.classList.toggle('hidden', checkedCount === 0);

		checkboxes.forEach((checkbox) => {
			const card = checkbox.closest('.soal-card');
			if (card) {
				card.classList.toggle('ring-2', checkbox.checked);
				card.classList.toggle('ring-blue-500', checkbox.checked);
				card.classList.toggle('border-blue-400', checkbox.checked);
			}

			if (!checkbox.checked) {
				checkbox.disabled = full;
				if (card) {
					card.classList.toggle('opacity-40', full);
					card.style.cursor = full ? 'not-allowed' : '';
				}
				return;
			}

			checkbox.disabled = false;
			if (card) {
				card.classList.remove('opacity-40');
				card.style.cursor = '';
			}
		});

		const enabledUnchecked = checkboxes.filter((checkbox) => !checkbox.disabled && !checkbox.checked).length;
		selectAll.indeterminate = checkedCount > 0 && enabledUnchecked > 0;
		selectAll.checked = enabledUnchecked === 0 && checkedCount > 0;
	};

	const buildPreviewList = (selectedCheckboxes) => {
		previewList.innerHTML = '';

		selectedCheckboxes.forEach((checkbox, index) => {
			const card = checkbox.closest('.soal-card');
			const badge = card?.querySelector('[class*="badge-"]');
			const question = card?.querySelector('p.font-medium');
			const badgeText = badge?.textContent?.trim() || '';
			const badgeClass = badge?.className.match(/badge-\w+/)?.[0] || 'badge-info';
			const questionText = question?.textContent?.trim() || `Soal #${checkbox.value}`;
			const previewItem = document.createElement('div');

			previewItem.className = 'flex items-start gap-3 rounded-2xl border border-border bg-slate-50/70 p-3 dark:bg-slate-800/60';
			previewItem.innerHTML = `
				<span class="mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white">${index + 1}</span>
				<div class="min-w-0 flex-1">
					<span class="${badgeClass} mb-1 inline-block text-[10px] font-bold uppercase tracking-wider">${badgeText}</span>
					<p class="text-sm leading-snug text-slate-700 dark:text-slate-200">${questionText.length > 150 ? `${questionText.substring(0, 150)}…` : questionText}</p>
				</div>
			`;

			previewList.appendChild(previewItem);
		});
	};

	checkboxes.forEach((checkbox) => {
		checkbox.addEventListener('change', updateUI);
	});

	selectAll.addEventListener('change', () => {
		let remaining = slotSisa;

		checkboxes.forEach((checkbox) => {
			if (selectAll.checked && remaining > 0) {
				checkbox.checked = true;
				remaining -= 1;
				return;
			}

			checkbox.checked = false;
		});

		updateUI();
	});

	deselectBtn.addEventListener('click', () => {
		checkboxes.forEach((checkbox) => {
			checkbox.checked = false;
		});
		selectAll.checked = false;
		updateUI();
	});

	previewBtn.addEventListener('click', () => {
		const selectedCheckboxes = getChecked();
		if (selectedCheckboxes.length === 0) {
			window.alert(emptySelectionMessage);
			return;
		}

		previewCount.textContent = String(selectedCheckboxes.length);
		quotaWarning.classList.toggle('hidden', selectedCheckboxes.length <= slotSisa);
		buildPreviewList(selectedCheckboxes);
		openModal();
	});

	closePreviewBtn.addEventListener('click', closeModal);
	cancelPreviewBtn.addEventListener('click', closeModal);

	modal.addEventListener('click', (event) => {
		if (event.target === modal) {
			closeModal();
		}
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && modal.classList.contains('flex')) {
			closeModal();
		}
	});

	confirmImportBtn.addEventListener('click', () => {
		confirmImportBtn.disabled = true;
		confirmImportBtn.innerHTML = processingImportHtml;
		form.submit();
	});

	document.querySelectorAll('.toggle-bacaan').forEach((button) => {
		button.addEventListener('click', () => {
			const target = document.getElementById(button.dataset.target || '');
			const chevron = button.querySelector('[data-chevron]');
			target?.classList.toggle('hidden');
			chevron?.classList.toggle('rotate-180');
		});
	});

	updateUI();
}

document.addEventListener('DOMContentLoaded', initSuperadminBankBuilder);
