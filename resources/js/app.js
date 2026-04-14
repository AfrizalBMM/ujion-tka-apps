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

	return window.matchMedia?.('(prefers-color-scheme: dark)')?.matches ? 'dark' : 'light';
}

applyTheme(getInitialTheme());

document.addEventListener('DOMContentLoaded', () => {
	applyTheme(getInitialTheme());

	const toggle = document.querySelector('[data-theme-toggle]');
	if (!toggle) return;

	toggle.addEventListener('click', () => {
		const isDark = document.documentElement.classList.contains('dark');
		const next = isDark ? 'light' : 'dark';
		localStorage.setItem('theme', next);
		applyTheme(next);
	});
});
