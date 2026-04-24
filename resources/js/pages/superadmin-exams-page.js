import { copyTextToClipboard } from '../utils/copy-text';

function initSuperadminExamsPage() {
	document.querySelectorAll('[data-copy-exam-mapel-token]').forEach((button) => {
		button.addEventListener('click', async () => {
			const token = button.getAttribute('data-copy-exam-mapel-token');
			if (!token) return;

			const originalHtml = button.innerHTML;

			try {
				await copyTextToClipboard(token);
				button.innerHTML = '<i class="fa-solid fa-check"></i>';
				button.classList.add('text-emerald-600');

				window.setTimeout(() => {
					button.innerHTML = originalHtml;
					button.classList.remove('text-emerald-600');
				}, 2000);
			} catch (error) {
				console.error('Failed to copy exam mapel token.', error);
			}
		});
	});

	const importModal = document.getElementById('modal-import');
	if (!importModal) return;

	document.querySelectorAll('[data-open-import-modal]').forEach((button) => {
		button.addEventListener('click', () => {
			importModal.classList.remove('hidden');
		});
	});

	document.querySelectorAll('[data-close-import-modal]').forEach((button) => {
		button.addEventListener('click', () => {
			importModal.classList.add('hidden');
		});
	});

	importModal.addEventListener('click', (event) => {
		if (event.target === importModal) {
			importModal.classList.add('hidden');
		}
	});
}

document.addEventListener('DOMContentLoaded', initSuperadminExamsPage);
