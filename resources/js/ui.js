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
	const promptWrap = modal.querySelector('[data-confirm-modal-prompt-wrap]');
	const promptLabel = modal.querySelector('[data-confirm-modal-prompt-label]');
	const promptInput = modal.querySelector('[data-confirm-modal-prompt-input]');
	const promptHelp = modal.querySelector('[data-confirm-modal-prompt-help]');
	const confirmBtn = modal.querySelector('[data-confirm-modal-confirm]');
	const cancelBtns = modal.querySelectorAll('[data-confirm-modal-cancel]');

	let pendingForm = null;
	let pendingConfirmText = null;
	let pendingConfirmField = null;

	const normalize = (value) => (value || '').toString().trim().toUpperCase();

	const updateConfirmButtonState = () => {
		if (!confirmBtn) return;
		if (!pendingConfirmText) {
			confirmBtn.disabled = false;
			return;
		}
		const ok = normalize(promptInput?.value) === normalize(pendingConfirmText);
		confirmBtn.disabled = !ok;
	};

	const open = ({ title, message, form, confirmText, confirmField, promptLabelText, promptPlaceholder }) => {
		pendingForm = form;
		pendingConfirmText = confirmText || null;
		pendingConfirmField = confirmField || 'confirm_text';
		if (titleEl) titleEl.textContent = title || 'Konfirmasi';
		if (messageEl) messageEl.textContent = message || 'Yakin?';

		if (promptWrap && promptInput) {
			if (pendingConfirmText) {
				promptWrap.classList.remove('hidden');
				if (promptLabel) promptLabel.textContent = promptLabelText || 'Ketik konfirmasi';
				promptInput.value = '';
				promptInput.placeholder = promptPlaceholder || '';
				promptHelp && (promptHelp.textContent = `Ketik: ${pendingConfirmText}`);
				setTimeout(() => promptInput.focus(), 0);
			} else {
				promptWrap.classList.add('hidden');
				promptInput.value = '';
			}
		}

		updateConfirmButtonState();
		modal.classList.remove('hidden');
		modal.setAttribute('aria-hidden', 'false');
		if (!pendingConfirmText) confirmBtn?.focus?.();
	};

	const close = () => {
		pendingForm = null;
		pendingConfirmText = null;
		pendingConfirmField = null;
		if (promptWrap && promptInput) {
			promptWrap.classList.add('hidden');
			promptInput.value = '';
		}
		if (confirmBtn) confirmBtn.disabled = false;
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

		const confirmText = trigger.getAttribute('data-confirm-require-text');
		const confirmField = trigger.getAttribute('data-confirm-require-field') || 'confirm_text';
		const promptLabelText = trigger.getAttribute('data-confirm-prompt-label') || 'Ketik konfirmasi';
		const promptPlaceholder = trigger.getAttribute('data-confirm-prompt-placeholder') || '';

		open({
			title: trigger.getAttribute('data-confirm-title') || 'Konfirmasi',
			message: trigger.getAttribute('data-confirm') || 'Yakin?',
			form,
			confirmText,
			confirmField,
			promptLabelText,
			promptPlaceholder,
		});
	});

	overlay?.addEventListener('click', () => close());
	cancelBtns.forEach((btn) => btn.addEventListener('click', () => close()));

	promptInput?.addEventListener('input', () => updateConfirmButtonState());

	confirmBtn?.addEventListener('click', () => {
		if (!pendingForm) {
			close();
			return;
		}

		if (pendingConfirmText) {
			const ok = normalize(promptInput?.value) === normalize(pendingConfirmText);
			if (!ok) {
				updateConfirmButtonState();
				return;
			}

			if (pendingConfirmField) {
				const existing = pendingForm.querySelector(`input[name="${pendingConfirmField}"]`);
				if (existing) existing.remove();

				const input = document.createElement('input');
				input.type = 'hidden';
				input.name = pendingConfirmField;
				input.value = promptInput?.value || '';
				pendingForm.appendChild(input);
			}
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
