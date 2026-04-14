function closestFlash(el) {
	return el?.closest?.('[role="alert"]') || el?.closest?.('.alert');
}

function initFlashDismiss() {
	document.addEventListener('click', (e) => {
		const target = e.target;
		if (!(target instanceof Element)) return;

		const btn = target.closest('[data-flash-close]');
		if (!btn) return;

		const alert = closestFlash(btn);
		alert?.classList?.add('hidden');
	});
}

function initConfirmModal() {
	const modal = document.querySelector('[data-confirm-modal]');
	if (!modal) return;

	const overlay = modal.querySelector('[data-confirm-modal-overlay]');
	const titleEl = modal.querySelector('[data-confirm-modal-title]');
	const messageEl = modal.querySelector('[data-confirm-modal-message]');
	const confirmBtn = modal.querySelector('[data-confirm-modal-confirm]');
	const cancelBtns = modal.querySelectorAll('[data-confirm-modal-cancel]');

	let pendingForm = null;

	const open = ({ title, message, form }) => {
		pendingForm = form;
		if (titleEl) titleEl.textContent = title || 'Konfirmasi';
		if (messageEl) messageEl.textContent = message || 'Yakin?';
		modal.classList.remove('hidden');
		modal.setAttribute('aria-hidden', 'false');
		confirmBtn?.focus?.();
	};

	const close = () => {
		pendingForm = null;
		modal.classList.add('hidden');
		modal.setAttribute('aria-hidden', 'true');
	};

	document.addEventListener('click', (e) => {
		const target = e.target;
		if (!(target instanceof Element)) return;

		const trigger = target.closest('[data-confirm]');
		if (!trigger) return;

		const form = trigger.closest('form');
		if (!form) return;

		e.preventDefault();

		open({
			title: trigger.getAttribute('data-confirm-title') || 'Konfirmasi',
			message: trigger.getAttribute('data-confirm') || 'Yakin?',
			form,
		});
	});

	overlay?.addEventListener('click', () => close());
	cancelBtns.forEach((btn) => btn.addEventListener('click', () => close()));

	confirmBtn?.addEventListener('click', () => {
		if (!pendingForm) {
			close();
			return;
		}

		pendingForm.submit();	
	});

	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape') close();
	});
}

document.addEventListener('DOMContentLoaded', () => {
	initFlashDismiss();
	initConfirmModal();
});
