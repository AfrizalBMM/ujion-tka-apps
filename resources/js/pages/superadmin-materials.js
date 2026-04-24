function initSuperadminMaterials() {
	const modals = {
		import: document.getElementById('material-import-modal'),
		create: document.getElementById('material-create-modal'),
	};

	const closeModal = (modal) => {
		if (!modal) return;
		modal.classList.add('hidden');
	};

	const openModal = (modal) => {
		if (!modal) return;
		modal.classList.remove('hidden');
	};

	document.querySelectorAll('[data-open-material-modal]').forEach((button) => {
		button.addEventListener('click', () => {
			const target = button.getAttribute('data-open-material-modal');
			if (!target || !(target in modals)) return;
			openModal(modals[target]);
		});
	});

	document.querySelectorAll('[data-close-material-modal]').forEach((button) => {
		button.addEventListener('click', () => {
			const modal = button.closest('#material-import-modal, #material-create-modal');
			closeModal(modal);
		});
	});

	Object.values(modals).forEach((modal) => {
		if (!modal) return;

		modal.addEventListener('click', (event) => {
			if (event.target === modal) {
				closeModal(modal);
			}
		});
	});

	document.addEventListener('keydown', (event) => {
		if (event.key !== 'Escape') return;

		Object.values(modals).forEach((modal) => {
			if (modal && !modal.classList.contains('hidden')) {
				closeModal(modal);
			}
		});
	});
}

document.addEventListener('DOMContentLoaded', initSuperadminMaterials);
