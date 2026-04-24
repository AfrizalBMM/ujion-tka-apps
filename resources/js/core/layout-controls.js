function updateLiveClocks() {
	document.querySelectorAll('#live-clock').forEach((clock) => {
		clock.textContent = new Date().toLocaleTimeString('id-ID');
	});
}

function initLiveClock() {
	if (!document.getElementById('live-clock')) return;

	updateLiveClocks();
	window.setInterval(updateLiveClocks, 1000);
}

function initFontSizeControls() {
	const actions = {
		increase: '1.05em',
		decrease: '0.97em',
		reset: '',
	};

	document.querySelectorAll('[data-font-size]').forEach((button) => {
		button.addEventListener('click', () => {
			const action = button.getAttribute('data-font-size');
			if (!action || !(action in actions)) return;

			document.body.style.fontSize = actions[action];
		});
	});
}

document.addEventListener('DOMContentLoaded', () => {
	initLiveClock();
	initFontSizeControls();
});

