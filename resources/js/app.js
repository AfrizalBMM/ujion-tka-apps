import './bootstrap';

import { createApp, h, defineAsyncComponent } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { ZiggyVue, route as ziggyRoute } from 'ziggy-js';
import { Ziggy } from './ziggy';
import { initFlowbite } from 'flowbite';
import { initLayoutBehaviors } from './core/legacy-init';

window.Ziggy = Ziggy;
window.route = ziggyRoute;

Ziggy.url = window.location.origin;
Ziggy.port = null;

createInertiaApp({
	title: (title) => title || 'Ujion — Platform Ujian TKA',
	resolve: (name) => {
		const pages = import.meta.glob('./Pages/**/*.vue');
		return defineAsyncComponent(pages[`./Pages/${name}.vue`]);
	},
	setup({ el, App, props, plugin }) {
		const app = createApp({ render: () => h(App, props) });

		app.use(plugin);
		app.use(ZiggyVue, Ziggy);

		app.mount(el);

		initFlowbite();
		initLayoutBehaviors();

		router.on('navigate', () => {
			initFlowbite();
			initLayoutBehaviors();
		});
	},
	progress: {
		delay: 100,
		color: '#22C1C3',
	},
});
