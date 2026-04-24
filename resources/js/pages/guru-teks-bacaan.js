function initGuruTeksBacaan() {
	const modal = document.getElementById('edit-modal');
	const form = document.getElementById('edit-form');
	const titleInput = document.getElementById('edit-judul');
	const contentInput = document.getElementById('edit-konten');

	if (!modal || !form || !titleInput || !contentInput) return;

	const closeModal = () => {
		modal.classList.add('hidden');
	};

	modal.querySelectorAll('[data-close-modal]').forEach((button) => {
		button.addEventListener('click', closeModal);
	});

	modal.addEventListener('click', (event) => {
		if (event.target === modal) {
			closeModal();
		}
	});

	document.querySelectorAll('[data-edit-bacaan]').forEach((button) => {
		button.addEventListener('click', () => {
			const rawData = button.getAttribute('data-edit-bacaan');
			if (!rawData) return;

			const data = JSON.parse(rawData);
			const actionTemplate = form.getAttribute('data-action-template') || '';

			form.action = actionTemplate.replace('__ID__', data.id);
			titleInput.value = data.judul ?? '';
			contentInput.value = data.konten ?? '';
			modal.classList.remove('hidden');
			titleInput.focus();
		});
	});
}

document.addEventListener('DOMContentLoaded', initGuruTeksBacaan);
