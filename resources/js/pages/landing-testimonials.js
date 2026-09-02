document.addEventListener('DOMContentLoaded', () => {
	const slider = document.querySelector('[data-testimonial-slider]');

	if (!slider) return;

	const track = slider.querySelector('[data-testimonial-track]');
	const slides = Array.from(slider.querySelectorAll('[data-testimonial-slide]'));
	const dotsWrap = slider.querySelector('[data-testimonial-dots]');

	if (!track || slides.length === 0) return;

	let current = 0;
	let timer = null;

	const dots = slides.map((_, index) => {
		const button = document.createElement('button');
		button.type = 'button';
		button.className = 'h-2 rounded-full bg-slate-300 transition-all dark:bg-slate-600';
		button.setAttribute('aria-label', `Testimoni ${index + 1}`);
		button.addEventListener('click', () => {
			goTo(index);
			restart();
		});
		dotsWrap.appendChild(button);
		return button;
	});

	function render() {
		track.style.transform = `translateX(-${current * 100}%)`;
		dots.forEach((dot, index) => {
			const active = index === current;
			dot.className = active
				? 'h-2 w-6 rounded-full bg-primary transition-all'
				: 'h-2 w-2 rounded-full bg-slate-300 transition-all dark:bg-slate-600';
		});
	}

	function goTo(index) {
		current = (index + slides.length) % slides.length;
		render();
	}

	function next() {
		goTo(current + 1);
	}

	function restart() {
		if (timer) clearInterval(timer);
		timer = setInterval(next, 5000);
	}

	slider.addEventListener('mouseenter', () => {
		if (timer) clearInterval(timer);
	});
	slider.addEventListener('mouseleave', restart);

	if (slides.length > 1) {
		render();
		restart();
	} else {
		render();
	}
});
