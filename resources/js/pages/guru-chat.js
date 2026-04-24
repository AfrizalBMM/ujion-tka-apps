function initGuruChat() {
	const chatBox = document.getElementById('chat-box');
	if (chatBox) {
		window.setTimeout(() => {
			chatBox.scrollTop = chatBox.scrollHeight;
		}, 100);
	}

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

	const openButtons = document.querySelectorAll('[data-open-chat-info]');
	const closeButtons = modal.querySelectorAll('[data-close-chat-info]');

	const openModal = () => modal.classList.remove('hidden');
	const closeModal = () => modal.classList.add('hidden');

	openButtons.forEach((button) => {
		button.addEventListener('click', openModal);
	});

	closeButtons.forEach((button) => {
		button.addEventListener('click', closeModal);
	});

	modal.addEventListener('click', (event) => {
		if (event.target === modal) {
			closeModal();
		}
	});
}

document.addEventListener('DOMContentLoaded', initGuruChat);
