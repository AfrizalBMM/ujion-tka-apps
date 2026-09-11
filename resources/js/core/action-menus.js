let actionMenusInitialized = false;

export function closeAllActionMenus() {
	document.querySelectorAll('[data-action-menu-toggle]').forEach((button) => {
		button.setAttribute('aria-expanded', 'false');
	});

	document.querySelectorAll('[data-action-menu-panel]').forEach((panel) => {
		panel.classList.add('invisible', 'translate-y-2', 'opacity-0');
	});
}

export function initActionMenus() {
	if (actionMenusInitialized) return;
	actionMenusInitialized = true;

	document.addEventListener('click', (event) => {
		const target = event.target;
		if (!(target instanceof Element)) return;

		const button = target.closest('[data-action-menu-toggle]');
		if (button) {
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
			return;
		}

		closeAllActionMenus();
	});

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape') {
			closeAllActionMenus();
		}
	});
}

if (typeof document !== 'undefined') {
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initActionMenus);
	} else {
		initActionMenus();
	}
}
