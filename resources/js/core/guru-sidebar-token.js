import { copyTextToClipboard } from '../utils/copy-text';

export function initGuruSidebarToken() {
	document.querySelectorAll('[data-guru-token-copy]').forEach((button) => {
		if (button.dataset.tokenCopyBound === '1') return;
		button.dataset.tokenCopyBound = '1';

		button.addEventListener('click', async () => {
			const token = button.getAttribute('data-guru-token-copy') || '';

			if (!token) return;

			try {
				await copyTextToClipboard(token);
				button.innerHTML = '<i class="fa-solid fa-check"></i>';
			} catch (e) {
				button.innerHTML = '<i class="fa-solid fa-xmark"></i>';
			}

			window.setTimeout(() => {
				button.innerHTML = '<i class="fa-regular fa-copy"></i>';
			}, 1500);
		});
	});
}

if (typeof document !== 'undefined') {
	document.addEventListener('DOMContentLoaded', initGuruSidebarToken);
}
