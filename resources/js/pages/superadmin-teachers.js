import { copyTextToClipboard } from '../utils/copy-text';

function initSuperadminTeachers() {
	document.querySelectorAll('[data-copy-template]').forEach((button) => {
		button.addEventListener('click', async () => {
			const text = button.getAttribute('data-copy-text') || '';
			const original = button.textContent;

			try {
				await copyTextToClipboard(text);
				button.textContent = 'Tersalin';
			} catch (error) {
				button.textContent = 'Gagal';
			}

			window.setTimeout(() => {
				button.textContent = original;
			}, 1200);
		});
	});

	document.querySelectorAll('[data-copy-token]').forEach((button) => {
		button.addEventListener('click', async () => {
			const text = button.getAttribute('data-copy-text') || '';
			const icon = button.querySelector('i');
			const label = button.querySelector('span');
			const originalLabel = label?.textContent || '';

			try {
				await copyTextToClipboard(text);
				if (icon) {
					icon.className = 'fa-solid fa-check text-xs';
				}
				if (label) {
					label.textContent = 'Token tersalin';
				}
			} catch (error) {
				if (icon) {
					icon.className = 'fa-solid fa-xmark text-xs';
				}
				if (label) {
					label.textContent = 'Gagal menyalin';
				}
			}

			window.setTimeout(() => {
				if (icon) {
					icon.className = 'fa-regular fa-copy text-xs';
				}
				if (label) {
					label.textContent = originalLabel;
				}
			}, 1200);
		});
	});

	const adminFlowModal = document.getElementById('admin-flow-modal');

	document.querySelectorAll('[data-admin-flow-open]').forEach((button) => {
		button.addEventListener('click', () => {
			adminFlowModal?.classList.remove('hidden');
			adminFlowModal?.classList.add('flex');
		});
	});

	document.querySelectorAll('[data-admin-flow-close]').forEach((button) => {
		button.addEventListener('click', () => {
			adminFlowModal?.classList.add('hidden');
			adminFlowModal?.classList.remove('flex');
		});
	});

	adminFlowModal?.addEventListener('click', (event) => {
		if (event.target === adminFlowModal) {
			adminFlowModal.classList.add('hidden');
			adminFlowModal.classList.remove('flex');
		}
	});

	document.addEventListener('keydown', (event) => {
		if (event.key !== 'Escape') return;

		closeAllActionMenus();

		if (adminFlowModal && !adminFlowModal.classList.contains('hidden')) {
			adminFlowModal.classList.add('hidden');
			adminFlowModal.classList.remove('flex');
		}
	});
}

document.addEventListener('DOMContentLoaded', initSuperadminTeachers);
