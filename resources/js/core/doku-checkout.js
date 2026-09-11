export function initDokuCheckout() {
	const rawConfig = document.body.dataset.dokuConfig;

	if (!rawConfig) return;

	let config;
	try {
		config = JSON.parse(rawConfig);
	} catch (e) {
		return;
	}

	if (!config.startUrl || !config.statusUrl || !config.finishUrl) return;

	const csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
	let pollTimer = null;
	let pollAttempts = 0;
	let currentOrderId = config.pollOrderId || '';

	const showPanel = (name) => {
		document.querySelectorAll('[data-doku-panel]').forEach((panel) => {
			panel.classList.toggle('hidden', panel.dataset.dokuPanel !== name);
		});
	};

	const pollStatus = async () => {
		if (!currentOrderId) return;

		pollAttempts += 1;

		try {
			const res = await fetch(config.statusUrl + '?order_id=' + encodeURIComponent(currentOrderId), {
				headers: { Accept: 'application/json' },
			});

			if (!res.ok) throw new Error('HTTP ' + res.status);

			const data = await res.json();

			if (data.ok && data.status === 'success') {
				window.clearInterval(pollTimer);
				window.location.href = config.finishUrl + '?order_id=' + encodeURIComponent(currentOrderId);

				return;
			}

			if (data.ok && data.status === 'failed') {
				window.clearInterval(pollTimer);
				showPanel('failed');

				return;
			}
		} catch (e) {
			// keep polling — retry on next tick
		}

		if (pollAttempts >= 60) {
			window.clearInterval(pollTimer);
		}
	};

	const startPolling = (orderId) => {
		if (!orderId) return;

		currentOrderId = orderId;
		pollAttempts = 0;
		window.clearInterval(pollTimer);
		showPanel('polling');
		pollTimer = window.setInterval(pollStatus, 3000);
		pollStatus();
	};

	const openCheckout = async () => {
		const popup = window.open('', '_blank');
		showPanel('loading');

		try {
			const res = await fetch(config.startUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN': csrfToken,
					Accept: 'application/json',
				},
				body: JSON.stringify({}),
			});

			const data = await res.json();

			if (!res.ok || !data.ok) {
				throw new Error(data.message || 'Gagal memulai pembayaran.');
			}

			if (popup) {
				popup.location.href = data.payment_url;
			} else {
				window.location.href = data.payment_url;

				return;
			}

			startPolling(data.order_id);
		} catch (e) {
			if (popup) popup.close();
			window.alert(e.message || 'Gagal memulai pembayaran. Silakan coba lagi.');
			showPanel('idle');
		}
	};

	document.querySelectorAll('[data-payment-locked], [data-doku-start]').forEach((element) => {
		element.addEventListener('click', (event) => {
			event.preventDefault();
			window.clearInterval(pollTimer);
			openCheckout();
		});
	});

	window.addEventListener('message', (event) => {
		if (event.origin !== window.location.origin) return;
		if (!event.data || typeof event.data.type !== 'string') return;

		if (event.data.type === 'doku-payment-finished' && currentOrderId) {
			pollAttempts = 0;
			pollStatus();
		}

		if (event.data.type === 'doku-payment-cancelled') {
			showPanel('idle');
		}
	});

	if (currentOrderId) {
		startPolling(currentOrderId);
	} else {
		showPanel('idle');
	}
}

if (typeof document !== 'undefined') {
	document.addEventListener('DOMContentLoaded', initDokuCheckout);
}
