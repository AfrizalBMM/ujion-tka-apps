function initSuperadminFinance() {
	const modal = document.getElementById('qris-modal');
	const form = document.getElementById('qris-form');
	const formTitle = document.getElementById('qris-form-title');
	const inputName = document.getElementById('qris-name');
	const inputJenjang = document.getElementById('qris-jenjang');
	const inputPrice = document.getElementById('qris-price');
	const inputSubtitle = document.getElementById('qris-subtitle');
	const inputDescription = document.getElementById('qris-description');
	const inputImage = document.getElementById('qris-image');
	const imagePreviewWrap = document.getElementById('qris-image-preview-wrap');
	const imagePreview = document.getElementById('qris-image-preview');
	const submitButton = document.getElementById('qris-submit');

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
	const defaultTitle = 'Tambah QRIS';
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

	document.querySelectorAll('[data-qris-form-open]').forEach((button) => {
		button.addEventListener('click', () => {
			resetForm();
			openModal();
			inputName.focus();
		});
	});

	document.querySelectorAll('[data-qris-form-close]').forEach((button) => {
		button.addEventListener('click', closeModal);
	});

	document.querySelectorAll('[data-qris-form-reset]').forEach((button) => {
		button.addEventListener('click', resetForm);
	});

	document.querySelectorAll('[data-qris-edit]').forEach((button) => {
		button.addEventListener('click', () => {
			resetForm();
			openModal();

			const updateAction = button.getAttribute('data-qris-update-action') || '';
			if (updateAction) {
				form.action = updateAction;
			}

			formTitle.textContent = 'Edit QRIS';
			submitButton.innerHTML = editSubmitHtml;

			inputName.value = button.getAttribute('data-qris-name') || '';
			if (inputJenjang && !inputJenjang.disabled) {
				inputJenjang.value = button.getAttribute('data-qris-jenjang') || '';
			}
			inputPrice.value = button.getAttribute('data-qris-price') || '';
			inputSubtitle.value = button.getAttribute('data-qris-subtitle') || '';
			inputDescription.value = button.getAttribute('data-qris-description') || '';

			const imageUrl = button.getAttribute('data-qris-image-url') || '';
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
