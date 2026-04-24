const katexDelimiters = [
	{ left: '$$', right: '$$', display: true },
	{ left: '$', right: '$', display: false },
	{ left: '\\(', right: '\\)', display: false },
	{ left: '\\[', right: '\\]', display: true },
];

function renderKaTeX(root = document.body, { force = false } = {}) {
	if (!root || typeof window.renderMathInElement !== 'function') {
		return false;
	}

	if (!force && root instanceof HTMLElement && root.dataset.katexRendered === 'true') {
		return true;
	}

	window.renderMathInElement(root, {
		delimiters: katexDelimiters,
		throwOnError: false,
	});

	if (root instanceof HTMLElement) {
		root.dataset.katexRendered = 'true';
	}

	return true;
}

function attemptRender(retries = 20) {
	if (renderKaTeX()) {
		return;
	}

	if (retries <= 0) {
		return;
	}

	window.setTimeout(() => {
		attemptRender(retries - 1);
	}, 150);
}

window.UjionKaTeX = {
	render: renderKaTeX,
};

document.addEventListener('DOMContentLoaded', () => {
	attemptRender();
});

window.addEventListener('load', () => {
	attemptRender(5);
});
