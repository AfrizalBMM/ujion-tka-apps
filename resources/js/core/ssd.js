export function initSSD(root) {
	(root || document).querySelectorAll('.ssd-wrap:not([data-ssd-init])').forEach(function (wrap) {
		wrap.dataset.ssdInit = '1';

		var trigger = wrap.querySelector('.ssd-trigger');
		var panel = wrap.querySelector('.ssd-panel');
		var search = panel ? panel.querySelector('.ssd-search') : null;
		var list = panel ? panel.querySelector('.ssd-list') : null;
		var label = wrap.querySelector('.ssd-label');
		var hidden = wrap.querySelector('input[type="hidden"]');
		if (!trigger || !panel || !search || !list) return;

		wrap._ssdPanel = panel;
		var getOpts = function () { return panel.querySelectorAll('.ssd-option'); };

		var positionPanel = function () {
			var rect = trigger.getBoundingClientRect();
			var panelWidth = Math.max(rect.width, 180);
			var left = rect.left;
			if (left + panelWidth > window.innerWidth - 8) {
				left = rect.right - panelWidth;
			}
			panel.style.top = (rect.bottom + 4) + 'px';
			panel.style.left = left + 'px';
			panel.style.minWidth = rect.width + 'px';
		};

		var closeWrap = function (targetWrap) {
			targetWrap = targetWrap || wrap;
			if (!targetWrap.classList.contains('ssd-open')) return;
			targetWrap.classList.remove('ssd-open');
			var p = targetWrap._ssdPanel;
			if (p && p.parentNode === document.body) {
				p.style.cssText = '';
				targetWrap.appendChild(p);
			}
		};

		var openWrap = function () {
			document.querySelectorAll('.ssd-wrap.ssd-open').forEach(function (w) {
				if (w !== wrap) closeWrap(w);
			});

			panel.style.position = 'fixed';
			panel.style.maxWidth = '300px';
			panel.style.width = 'max-content';
			positionPanel();
			document.body.appendChild(panel);
			wrap.classList.add('ssd-open');

			search.value = '';
			filterOpts('');
			search.focus();
		};

		trigger.addEventListener('click', function (e) {
			e.stopPropagation();
			wrap.classList.contains('ssd-open') ? closeWrap() : openWrap();
		});

		window.addEventListener('scroll', function () {
			if (wrap.classList.contains('ssd-open')) positionPanel();
		}, true);

		var filterOpts = function (q) {
			var empty = list.querySelector('.ssd-empty');
			if (empty) empty.remove();
			var visible = 0;
			getOpts().forEach(function (o) {
				var match = o.textContent.toLowerCase().includes(q.toLowerCase());
				o.classList.toggle('ssd-hidden', !match);
				if (match) visible++;
			});
			if (visible === 0) {
				var el = document.createElement('div');
				el.className = 'ssd-empty';
				el.textContent = 'Tidak ditemukan';
				list.appendChild(el);
			}
		};

		search.addEventListener('input', function () { filterOpts(search.value.trim()); });
		search.addEventListener('click', function (e) { e.stopPropagation(); });

		var autoSubmitForm = wrap.closest('[data-ssd-autosubmit]');
		var syncUI = function () {
			var val = hidden.value;
			var opts = getOpts();
			opts.forEach(function (o) {
				var isMatch = String(o.dataset.value || '') === String(val);
				o.classList.toggle('ssd-selected', isMatch);
				if (isMatch) {
					if (label) label.textContent = o.textContent.trim();
				}
			});
		};

		hidden.addEventListener('change', syncUI);

		getOpts().forEach(function (opt) {
			opt.addEventListener('click', function () {
				var val = opt.dataset.value !== undefined ? opt.dataset.value : '';
				if (hidden) {
					hidden.value = val;
					hidden.dispatchEvent(new Event('change'));
				}
				closeWrap();
				if (autoSubmitForm) autoSubmitForm.submit();
			});
		});
	});
}

export function syncSSD(hiddenInput) {
	if (!hiddenInput) return;
	var wrap = hiddenInput.closest('.ssd-wrap');
	if (!wrap) return;

	var val = hiddenInput.value;
	var opts = wrap.querySelectorAll('.ssd-option');
	var label = wrap.querySelector('.ssd-label');

	opts.forEach(function (o) {
		var isMatch = String(o.dataset.value || '') === String(val);
		o.classList.toggle('ssd-selected', isMatch);
		if (isMatch && label) {
			label.textContent = o.textContent.trim();
		}
	});
}

export function initSsdGlobalClose() {
	document.addEventListener('click', function (e) {
		document.querySelectorAll('.ssd-wrap.ssd-open').forEach(function (wrap) {
			var panel = wrap._ssdPanel;
			if (panel && !panel.contains(e.target) && !wrap.contains(e.target)) {
				var p = panel;
				wrap.classList.remove('ssd-open');
				if (p.parentNode === document.body) {
					p.style.cssText = '';
					wrap.appendChild(p);
				}
			}
		});
	});
}
