function getColorFromClass(className) {
	const el = document.createElement('span');
	el.className = className;
	el.style.position = 'absolute';
	el.style.left = '-9999px';
	el.style.top = '-9999px';
	document.body.appendChild(el);
	const color = getComputedStyle(el).color;
	el.remove();
	return color;
}

function initSuperadminSidebar() {
	const sidebar = document.querySelector('[data-superadmin-sidebar]');
	const overlay = document.querySelector('[data-superadmin-sidebar-overlay]');
	const openBtn = document.querySelector('[data-superadmin-sidebar-open]');
	const closeBtn = document.querySelector('[data-superadmin-sidebar-close]');

	if (!sidebar || !overlay || !openBtn) return;

	const isMobile = () => window.matchMedia?.('(max-width: 767px)')?.matches;

	const open = () => {
		sidebar.classList.remove('-translate-x-full');
		sidebar.classList.add('translate-x-0');
		overlay.classList.remove('hidden');
		overlay.setAttribute('aria-hidden', 'false');
		openBtn.setAttribute('aria-expanded', 'true');
	};

	const close = () => {
		sidebar.classList.add('-translate-x-full');
		sidebar.classList.remove('translate-x-0');
		overlay.classList.add('hidden');
		overlay.setAttribute('aria-hidden', 'true');
		openBtn.setAttribute('aria-expanded', 'false');
	};

	openBtn.addEventListener('click', () => {
		if (!isMobile()) return;
		open();
	});

	closeBtn?.addEventListener('click', () => {
		close();
	});

	overlay.addEventListener('click', () => {
		close();
	});

	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape') {
			close();
		}
	});

	// Ensure closed when switching to desktop.
	window.addEventListener('resize', () => {
		if (!isMobile()) {
			overlay.classList.add('hidden');
			overlay.setAttribute('aria-hidden', 'true');
			openBtn.setAttribute('aria-expanded', 'false');
			sidebar.classList.remove('translate-x-0');
			sidebar.classList.remove('-translate-x-full');
		}
	});
}

async function initActivityChart() {
	const canvas = document.getElementById('superadmin-activity-chart');
	if (!canvas) return;

	let labels = [];
	let values = [];
	try {
		labels = JSON.parse(canvas.dataset.labels || '[]');
		values = JSON.parse(canvas.dataset.values || '[]');
	} catch {
		labels = [];
		values = [];
	}

	const [{ default: Chart }] = await Promise.all([import('chart.js/auto')]);

	const primary = getColorFromClass('text-primary');
	const muted = getColorFromClass('text-muted');

	new Chart(canvas, {
		type: 'line',
		data: {
			labels,
			datasets: [
				{
					label: 'Aktivitas',
					data: values,
					borderColor: primary,
					backgroundColor: primary,
					tension: 0.35,
					pointRadius: 2,
				},
			],
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: { display: false },
			},
			scales: {
				x: {
					ticks: { color: muted },
					grid: { display: false },
				},
				y: {
					ticks: { color: muted, precision: 0 },
					grid: { display: false },
				},
			},
		},
	});
}

document.addEventListener('DOMContentLoaded', () => {
	initSuperadminSidebar();
	initActivityChart();
});
