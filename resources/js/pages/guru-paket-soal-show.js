import { copyTextToClipboard } from '../utils/copy-text';

function initGuruPaketSoalShow() {
	document.querySelectorAll('[data-copy-paket-token]').forEach((button) => {
		button.addEventListener('click', async () => {
			const token = button.getAttribute('data-copy-paket-token');
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
				console.error('Failed to copy paket token.', error);
			}
		});
	});
}

document.addEventListener('DOMContentLoaded', initGuruPaketSoalShow);
