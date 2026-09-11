import { copyTextToClipboard } from '../utils/copy-text';

function initGuruSidebarToken() {
	document.querySelectorAll('[data-guru-token-copy]').forEach((button) => {
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

document.addEventListener('DOMContentLoaded', initGuruSidebarToken);
