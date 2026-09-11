import { closeAllActionMenus } from '../core/action-menus';

function initSuperadminFinance() {

	const modal = document.getElementById('tarif-modal');
	const form = document.getElementById('tarif-form');
	const formTitle = document.getElementById('tarif-form-title');
	const inputName = document.getElementById('tarif-name');
	const inputJenjang = document.getElementById('tarif-jenjang');
	const inputPrice = document.getElementById('tarif-price');
	const inputSubtitle = document.getElementById('tarif-subtitle');
	const inputDescription = document.getElementById('tarif-description');
	const inputImage = document.getElementById('tarif-image');
	const imagePreviewWrap = document.getElementById('tarif-image-preview-wrap');
	const imagePreview = document.getElementById('tarif-image-preview');
	const submitButton = document.getElementById('tarif-submit');

	if (
		!modal ||
		!form ||
		!formTitle ||
		!inputName ||
		!inputPrice ||
		!inputSubtitle ||
		!inputDescription ||
		!submitButton
	) {
		return;
	}

	const defaultAction = form.getAttribute('action') || '';
	const defaultTitle = 'Tambah Tarif';
	const defaultSubmitHtml = '<i class="fa-solid fa-floppy-disk mr-2"></i> Simpan';
	const editSubmitHtml = '<i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Perubahan';
	let objectUrl = null;

	const clearObjectUrl = () => {
		if (!objectUrl) return;
		URL.revokeObjectURL(objectUrl);
		objectUrl = null;
	};

	const setPreviewImage = (src = '') => {
		if (!imagePreviewWrap || !imagePreview) return;

		if (src) {
			imagePreview.src = src;
			imagePreviewWrap.classList.remove('hidden');
			return;
		}

		imagePreview.src = '';
		imagePreviewWrap.classList.add('hidden');
	};

	const openModal = () => {
		modal.classList.remove('hidden');
		modal.classList.add('flex');
	};

	const closeModal = () => {
		modal.classList.add('hidden');
		modal.classList.remove('flex');
	};

	const resetForm = () => {
		form.action = defaultAction;
		formTitle.textContent = defaultTitle;
		submitButton.innerHTML = defaultSubmitHtml;

		inputName.value = '';
		if (inputJenjang && !inputJenjang.disabled) {
			inputJenjang.value = '';
			inputJenjang.dispatchEvent(new Event('change'));
		}
		inputPrice.value = '';
		inputSubtitle.value = '';
		inputDescription.value = '';

		if (inputImage && !inputImage.disabled) {
			inputImage.value = '';
		}

		clearObjectUrl();
		setPreviewImage('');
	};

	document.querySelectorAll('[data-tarif-form-open]').forEach((button) => {
		button.addEventListener('click', () => {
			closeAllActionMenus();
			resetForm();
			openModal();
			inputName.focus();
		});
	});

	document.querySelectorAll('[data-tarif-form-close]').forEach((button) => {
		button.addEventListener('click', closeModal);
	});

	document.querySelectorAll('[data-tarif-form-reset]').forEach((button) => {
		button.addEventListener('click', resetForm);
	});

	document.querySelectorAll('[data-tarif-edit]').forEach((button) => {
		button.addEventListener('click', () => {
			closeAllActionMenus();
			resetForm();
			openModal();

			const updateAction = button.getAttribute('data-tarif-update-action') || '';
			if (updateAction) {
				form.action = updateAction;
			}

			formTitle.textContent = 'Edit Tarif';
			submitButton.innerHTML = editSubmitHtml;

			inputName.value = button.getAttribute('data-tarif-name') || '';
			if (inputJenjang && !inputJenjang.disabled) {
				inputJenjang.value = button.getAttribute('data-tarif-jenjang') || '';
				inputJenjang.dispatchEvent(new Event('change'));
			}
			inputPrice.value = button.getAttribute('data-tarif-price') || '';
			inputSubtitle.value = button.getAttribute('data-tarif-subtitle') || '';
			inputDescription.value = button.getAttribute('data-tarif-description') || '';

			const imageUrl = button.getAttribute('data-tarif-image-url') || '';
			clearObjectUrl();
			setPreviewImage(imageUrl);
		});
	});

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

	inputImage?.addEventListener('change', () => {
		const file = inputImage.files?.[0] || null;
		if (!file) return;

		clearObjectUrl();
		objectUrl = URL.createObjectURL(file);
		setPreviewImage(objectUrl);
	});
}

document.addEventListener('DOMContentLoaded', initSuperadminFinance);
