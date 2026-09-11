let liveClockStarted = false;
let fontSizeInitialized = false;

function updateLiveClocks() {
	document.querySelectorAll('#live-clock').forEach((clock) => {
		clock.textContent = new Date().toLocaleTimeString('id-ID');
	});
}

export function initLiveClock() {
	if (liveClockStarted) return;
	if (!document.getElementById('live-clock')) return;

	liveClockStarted = true;

	updateLiveClocks();
	window.setInterval(updateLiveClocks, 1000);
}

export function initFontSizeControls() {
	if (fontSizeInitialized) return;
	fontSizeInitialized = true;

	const actions = {
		increase: '1.05em',
		decrease: '0.97em',
		reset: '',
	};

	document.addEventListener('click', (event) => {
		const target = event.target;
		if (!(target instanceof Element)) return;

		const button = target.closest('[data-font-size]');
		if (!button) return;

		const action = button.getAttribute('data-font-size');
		if (!action || !(action in actions)) return;

		document.body.style.fontSize = actions[action];
	});
}

if (typeof document !== 'undefined') {
	document.addEventListener('DOMContentLoaded', () => {
		initLiveClock();
		initFontSizeControls();
	});
}
