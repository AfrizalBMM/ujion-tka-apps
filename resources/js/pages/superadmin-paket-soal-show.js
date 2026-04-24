import { copyTextToClipboard } from '../utils/copy-text';

function initSuperadminPaketSoalShow() {
	document.querySelectorAll('[data-copy-paket-detail-token]').forEach((button) => {
		button.addEventListener('click', async () => {
			const token = button.getAttribute('data-copy-paket-detail-token');
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
				console.error('Failed to copy paket detail token.', error);
			}
		});
	});

	document.querySelectorAll('[id^="delete-trigger-wrap-"]').forEach((wrap) => {
		const mapelId = wrap.id.replace('delete-trigger-wrap-', '');
		const deleteForm = document.getElementById(`delete-form-${mapelId}`);
		if (!deleteForm || wrap.childElementCount > 0) return;

		const button = document.createElement('button');
		button.type = 'button';
		button.className = 'btn-danger px-4 py-2 text-xs flex items-center gap-1.5';
		button.innerHTML = '<i class="fa-solid fa-trash-can"></i> Hapus Semua Soal';
		button.setAttribute('data-confirm', deleteForm.dataset.confirm || 'Yakin hapus semua soal?');
		button.setAttribute('data-confirm-title', deleteForm.dataset.confirmTitle || 'Hapus Semua Soal');

		button.addEventListener('click', () => {
			const modal = document.querySelector('[data-confirm-modal]');
			const titleElement = modal?.querySelector('[data-confirm-modal-title]');
			const messageElement = modal?.querySelector('[data-confirm-modal-message]');
			const confirmButton = modal?.querySelector('[data-confirm-modal-confirm]');

			if (!modal || !confirmButton) {
				deleteForm.submit();
				return;
			}

			if (titleElement) {
				titleElement.textContent = deleteForm.dataset.confirmTitle || 'Hapus Semua Soal';
			}

			if (messageElement) {
				messageElement.textContent = deleteForm.dataset.confirm || 'Yakin hapus semua soal?';
			}

			const confirmHandler = () => {
				deleteForm.submit();
				confirmButton.removeEventListener('click', confirmHandler);
			};

			confirmButton.addEventListener('click', confirmHandler);
			modal.classList.remove('hidden');
			modal.setAttribute('aria-hidden', 'false');
			confirmButton.focus();
		});

		wrap.appendChild(button);
	});
}

document.addEventListener('DOMContentLoaded', initSuperadminPaketSoalShow);
