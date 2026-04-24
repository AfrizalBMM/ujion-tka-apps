import { copyTextToClipboard } from '../utils/copy-text';

function initSuperadminTeachers() {
	const closeAllActionMenus = () => {
		document.querySelectorAll('[data-action-menu-toggle]').forEach((button) => {
			button.setAttribute('aria-expanded', 'false');
		});

		document.querySelectorAll('[data-action-menu-panel]').forEach((panel) => {
			panel.classList.add('invisible', 'translate-y-2', 'opacity-0');
		});
	};

	document.querySelectorAll('[data-action-menu-toggle]').forEach((button) => {
		button.addEventListener('click', (event) => {
			event.stopPropagation();

			const menu = button.closest('[data-action-menu]');
			const panel = menu?.querySelector('[data-action-menu-panel]');
			const isOpen = button.getAttribute('aria-expanded') === 'true';

			closeAllActionMenus();

			if (!panel || isOpen) {
				return;
			}

			button.setAttribute('aria-expanded', 'true');
			panel.classList.remove('invisible', 'translate-y-2', 'opacity-0');
		});
	});

	document.addEventListener('click', closeAllActionMenus);

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

	const proofModal = document.getElementById('payment-proof-modal');
	const proofImage = document.getElementById('payment-proof-image');
	const proofTitle = document.getElementById('payment-proof-title');
	const adminFlowModal = document.getElementById('admin-flow-modal');
	const rejectPaymentModal = document.getElementById('reject-payment-modal');
	const rejectPaymentForm = document.getElementById('reject-payment-form');
	const rejectPaymentTitle = document.getElementById('reject-payment-title');
	const rejectPaymentReason = document.getElementById('payment_rejection_reason');

	document.querySelectorAll('[data-payment-proof-open]').forEach((button) => {
		button.addEventListener('click', () => {
			if (proofImage) {
				proofImage.src = button.getAttribute('data-payment-proof-src') || '';
			}
			if (proofTitle) {
				proofTitle.textContent = button.getAttribute('data-payment-proof-name') || 'Guru';
			}
			proofModal?.classList.remove('hidden');
			proofModal?.classList.add('flex');
		});
	});

	document.querySelectorAll('[data-payment-proof-close]').forEach((button) => {
		button.addEventListener('click', () => {
			proofModal?.classList.add('hidden');
			proofModal?.classList.remove('flex');
			if (proofImage) {
				proofImage.src = '';
			}
		});
	});

	proofModal?.addEventListener('click', (event) => {
		if (event.target === proofModal) {
			proofModal.classList.add('hidden');
			proofModal.classList.remove('flex');
			if (proofImage) {
				proofImage.src = '';
			}
		}
	});

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

	document.querySelectorAll('[data-reject-payment-open]').forEach((button) => {
		button.addEventListener('click', () => {
			const action = button.getAttribute('data-reject-payment-action') || '';
			const name = button.getAttribute('data-reject-payment-name') || 'Guru';

			if (rejectPaymentForm) {
				rejectPaymentForm.setAttribute('action', action);
			}

			if (rejectPaymentTitle) {
				rejectPaymentTitle.textContent = name;
			}

			if (rejectPaymentReason) {
				rejectPaymentReason.value = '';
			}

			rejectPaymentModal?.classList.remove('hidden');
			rejectPaymentModal?.classList.add('flex');
			rejectPaymentReason?.focus();
		});
	});

	document.querySelectorAll('[data-reject-payment-close]').forEach((button) => {
		button.addEventListener('click', () => {
			rejectPaymentModal?.classList.add('hidden');
			rejectPaymentModal?.classList.remove('flex');
		});
	});

	rejectPaymentModal?.addEventListener('click', (event) => {
		if (event.target === rejectPaymentModal) {
			rejectPaymentModal.classList.add('hidden');
			rejectPaymentModal.classList.remove('flex');
		}
	});

	document.addEventListener('keydown', (event) => {
		if (event.key !== 'Escape') return;

		closeAllActionMenus();

		[proofModal, adminFlowModal, rejectPaymentModal].forEach((modal) => {
			if (modal && !modal.classList.contains('hidden')) {
				modal.classList.add('hidden');
				modal.classList.remove('flex');
			}
		});

		if (proofImage) {
			proofImage.src = '';
		}
	});
}

document.addEventListener('DOMContentLoaded', initSuperadminTeachers);
