import './bootstrap';

import 'flowbite';
import './superadmin';
import './ui';

function applyTheme(theme) {
	const root = document.documentElement;

	if (theme === 'dark') {
		root.classList.add('dark');
	} else {
		root.classList.remove('dark');
	}

	const icon = document.querySelector('[data-theme-toggle] i');
	if (icon) {
		icon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
	}
}

function getInitialTheme() {
	const stored = localStorage.getItem('theme');
	if (stored === 'dark' || stored === 'light') {
		return stored;
	}

	return 'light';
}

function initDesktopSidebarCollapse() {
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

applyTheme(getInitialTheme());

document.addEventListener('DOMContentLoaded', () => {
	applyTheme(getInitialTheme());
	initDesktopSidebarCollapse();

	const toggle = document.querySelector('[data-theme-toggle]');
	if (!toggle) return;

	toggle.addEventListener('click', () => {
		const isDark = document.documentElement.classList.contains('dark');
		const next = isDark ? 'light' : 'dark';
		localStorage.setItem('theme', next);
		applyTheme(next);
	});
});
