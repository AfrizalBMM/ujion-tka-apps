import { initLiveFilterForm } from '../utils/init-live-filter-form';

document.addEventListener('DOMContentLoaded', () => {
	initLiveFilterForm({
		formSelector: 'form[data-materials-filter-form]',
		gridSelector: '#materials-grid',
		searchSelector: '[data-live-search][name="search"]',
	});
});
