function initSuperadminChat() {
	const input = document.getElementById('chat-image-input');
	const preview = document.getElementById('chat-image-preview');
	const image = document.getElementById('chat-image-preview-img');
	const meta = document.getElementById('chat-image-preview-meta');
	const clearButton = document.getElementById('chat-image-preview-clear');

	if (input && preview && image && meta && clearButton) {
		let objectUrl = null;

		const clearPreview = () => {
			if (objectUrl) {
				URL.revokeObjectURL(objectUrl);
				objectUrl = null;
			}

			image.removeAttribute('src');
			meta.textContent = '';
			preview.classList.add('hidden');
			input.value = '';
		};

		input.addEventListener('change', () => {
			const file = input.files?.[0] || null;
			if (!file) {
				clearPreview();
				return;
			}

			if (objectUrl) {
				URL.revokeObjectURL(objectUrl);
			}

			objectUrl = URL.createObjectURL(file);
			image.src = objectUrl;

			const sizeKb = Math.max(1, Math.round(file.size / 1024));
			meta.textContent = `${file.name} (${sizeKb} KB)`;
			preview.classList.remove('hidden');
		});

		clearButton.addEventListener('click', clearPreview);
	}

	const modal = document.getElementById('modal-detail-akun');
	if (!modal) return;

	document.querySelectorAll('[data-open-account-detail]').forEach((button) => {
		button.addEventListener('click', () => {
			modal.classList.remove('hidden');
		});
	});

	modal.querySelectorAll('[data-close-account-detail]').forEach((button) => {
		button.addEventListener('click', () => {
			modal.classList.add('hidden');
		});
	});

	modal.addEventListener('click', (event) => {
		if (event.target === modal) {
			modal.classList.add('hidden');
		}
	});
}

document.addEventListener('DOMContentLoaded', initSuperadminChat);
