import { copyTextToClipboard } from '../utils/copy-text';

function initSuperadminExamDetail() {
	const button = document.querySelector('[data-copy-exam-token-single]');
	const tokenElement = document.getElementById('token-text');
	const success = document.getElementById('copy-success');

	if (!button || !tokenElement || !success) return;

	button.addEventListener('click', async () => {
		try {
			await copyTextToClipboard(tokenElement.innerText);
			success.classList.remove('hidden');

			window.setTimeout(() => {
				success.classList.add('hidden');
			}, 1500);
		} catch (error) {
			console.error('Failed to copy exam token.', error);
		}
	});
}

document.addEventListener('DOMContentLoaded', initSuperadminExamDetail);
