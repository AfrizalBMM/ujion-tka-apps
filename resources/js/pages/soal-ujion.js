import { initLiveFilterForm } from '../utils/init-live-filter-form';

document.addEventListener('DOMContentLoaded', () => {
	initLiveFilterForm({
		formSelector: 'form[data-soal-ujion-filter-form]',
		gridSelector: '#questions-grid',
		searchSelector: '[data-live-search][name="search"]',
		watchSelects: true,
	});
});
