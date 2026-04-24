export function initLiveFilterForm({
	formSelector,
	gridSelector,
	searchSelector,
	watchSelects = false,
	searchDelay = 450,
	onBeforeReplace = null,
	onAfterReplace = null,
}) {
	const form = document.querySelector(formSelector);
	const input = document.querySelector(searchSelector);
	const grid = document.querySelector(gridSelector);

	if (!form || !input || !grid) return;

	let timer = null;
	let abortController = null;
	let isComposing = false;
	let lastValue = (input.value || '').trim();

	function buildUrl() {
		const action = form.getAttribute('action') || window.location.pathname;
		const url = new URL(action, window.location.origin);
		const params = new URLSearchParams(new FormData(form));
		url.search = params.toString();
		return url;
	}

	function setLoading(isLoading) {
		grid.classList.toggle('opacity-60', !!isLoading);
		grid.classList.toggle('pointer-events-none', !!isLoading);
	}

	async function fetchAndReplace(force = false) {
		const nextValue = (input.value || '').trim();
		if (!force && !watchSelects && nextValue === lastValue) return;
		lastValue = nextValue;

		const url = buildUrl();

		if (abortController) {
			abortController.abort();
		}

		abortController = new AbortController();
		setLoading(true);

		try {
			const response = await fetch(url.toString(), {
				method: 'GET',
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
				},
				signal: abortController.signal,
			});

			if (!response.ok) {
				throw new Error('Request failed');
			}

			const html = await response.text();
			const doc = new DOMParser().parseFromString(html, 'text/html');
			const nextGrid = doc.querySelector(gridSelector);

			if (typeof onBeforeReplace === 'function') {
				onBeforeReplace({ form, grid, nextGrid, doc, url });
			}

			if (nextGrid) {
				grid.innerHTML = nextGrid.innerHTML;
			}

			window.history.replaceState({}, '', url.pathname + (url.search ? `?${url.searchParams.toString()}` : ''));

			if (typeof onAfterReplace === 'function') {
				onAfterReplace({ form, grid, nextGrid, doc, url });
			}
		} catch (error) {
			// Ignore fetch aborts or transient request errors; the full form submit still works.
		} finally {
			setLoading(false);
		}
	}

	function scheduleFetch(delay = searchDelay, force = false) {
		if (isComposing) return;

		window.clearTimeout(timer);
		timer = window.setTimeout(() => {
			fetchAndReplace(force);
		}, delay);
	}

	form.addEventListener('submit', (event) => {
		if (document.activeElement === input) {
			event.preventDefault();
			fetchAndReplace(true);
		}
	});

	input.addEventListener('compositionstart', () => {
		isComposing = true;
	});

	input.addEventListener('compositionend', () => {
		isComposing = false;
		scheduleFetch(250);
	});

	input.addEventListener('input', () => {
		scheduleFetch(searchDelay, !watchSelects);
	});

	if (watchSelects) {
		form.querySelectorAll('select').forEach((select) => {
			select.addEventListener('change', () => {
				scheduleFetch(0, true);
			});
		});
	}
}
