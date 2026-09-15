import { initActionMenus } from './action-menus';
import { attemptRender } from './katex-render';
import { initLiveClock, initFontSizeControls } from './layout-controls';
import { initSSD, initSsdGlobalClose } from './ssd';
import { initFlashDismiss, initConfirmModal } from '../ui';

let globalOnceInitialized = false;

export function applyTheme(theme) {
	const root = document.documentElement;

	if (theme === 'dark') {
		root.classList.add('dark');
	} else {
		root.classList.remove('dark');
	}

	document.querySelectorAll('[data-theme-toggle] i').forEach((icon) => {
		icon.classList.remove('fa-sun', 'fa-moon');
		icon.classList.add('fa-solid', theme === 'dark' ? 'fa-sun' : 'fa-moon');
	});
}

export function getInitialTheme() {
	const stored = localStorage.getItem('theme');
	if (stored === 'dark' || stored === 'light') {
		return stored;
	}

	return 'light';
}

export function initTheme() {
	applyTheme(getInitialTheme());
}

function initGlobalOnce() {
	if (globalOnceInitialized) return;
	globalOnceInitialized = true;

	initActionMenus();
	initFontSizeControls();
	initSsdGlobalClose();

	document.addEventListener('click', (event) => {
		const target = event.target;
		if (!(target instanceof Element)) return;

		const toggle = target.closest('[data-theme-toggle]');
		if (!toggle) return;

		const isDark = document.documentElement.classList.contains('dark');
		const next = isDark ? 'light' : 'dark';
		localStorage.setItem('theme', next);
		applyTheme(next);
	});
}

export function initSidebarCollapse() {
	const shell = document.querySelector('[data-dashboard-shell]');
	const sidebar = document.querySelector('[data-app-sidebar]');
	const toggle = document.querySelector('[data-sidebar-toggle]');
	const toggleIcon = document.querySelector('[data-sidebar-toggle-icon]');
	const submenus = Array.from(sidebar?.querySelectorAll('[data-sidebar-submenu]') || []);

	if (!shell || !sidebar || !toggle) return;

	const storageKey = `sidebar:${shell.getAttribute('data-dashboard-shell')}:collapsed`;
	const isDesktop = () => window.matchMedia?.('(min-width: 768px)')?.matches;
	const links = Array.from(sidebar.querySelectorAll('.sidebar-link'));
	const submenuLinks = Array.from(sidebar.querySelectorAll('.sidebar-sublink'));

	links.forEach((link) => {
		const label = link.querySelector('.sidebar-link-label')?.textContent?.trim();
		if (!label) return;
		link.setAttribute('data-tooltip', label);
	});

	submenuLinks.forEach((link) => {
		const label = link.querySelector('.sidebar-sublink-label')?.textContent?.trim();
		if (!label) return;
		link.setAttribute('data-tooltip', label);
	});

	const applyState = (collapsed) => {
		shell.classList.toggle('sidebar-collapsed', collapsed && isDesktop());
		toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
		toggle.setAttribute('title', collapsed ? 'Buka sidebar' : 'Ciutkan sidebar');

		if (collapsed && isDesktop()) {
			submenus.forEach((submenu) => {
				submenu.removeAttribute('open');
			});
		}

		if (toggleIcon) {
			toggleIcon.className = collapsed ? 'fa-solid fa-angles-right' : 'fa-solid fa-angles-left';
		}

		links.forEach((link) => {
			if (collapsed && isDesktop()) {
				link.setAttribute('aria-label', link.getAttribute('data-tooltip') || '');
			} else {
				link.removeAttribute('aria-label');
			}
		});

		submenuLinks.forEach((link) => {
			if (collapsed && isDesktop()) {
				link.setAttribute('aria-label', link.getAttribute('data-tooltip') || '');
			} else {
				link.removeAttribute('aria-label');
			}
		});
	};

	const stored = localStorage.getItem(storageKey) === 'true';
	applyState(stored);

	toggle.addEventListener('click', () => {
		if (!isDesktop()) return;

		const next = !shell.classList.contains('sidebar-collapsed');
		localStorage.setItem(storageKey, String(next));
		applyState(next);
	});

	window.addEventListener('resize', () => {
		const collapsed = localStorage.getItem(storageKey) === 'true';
		applyState(collapsed);
	});
}

export function initLayoutBehaviors() {
	initGlobalOnce();
	initTheme();
	initSidebarCollapse();
	initLiveClock();
	initConfirmModal();
	initFlashDismiss();
	initSSD();
	attemptRender();
}

export function initLegacyBehaviors() {
	initFlashDismiss();
	initSSD();
	attemptRender();
}
