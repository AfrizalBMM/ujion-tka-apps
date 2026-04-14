import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;

if (pusherKey) {
	const [{ default: Echo }, { default: Pusher }] = await Promise.all([
		import('laravel-echo'),
		import('pusher-js'),
	]);

	window.Pusher = Pusher;

	const scheme = import.meta.env.VITE_PUSHER_SCHEME ?? 'https';
	const host = import.meta.env.VITE_PUSHER_HOST ?? window.location.hostname;
	const port = Number(import.meta.env.VITE_PUSHER_PORT ?? (scheme === 'https' ? 443 : 80));

	window.Echo = new Echo({
		broadcaster: 'pusher',
		key: pusherKey,
		cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
		wsHost: host,
		wsPort: port,
		wssPort: port,
		forceTLS: scheme === 'https',
		enabledTransports: ['ws', 'wss'],
	});
}
