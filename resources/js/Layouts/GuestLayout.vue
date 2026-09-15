<script setup>
import { computed, onBeforeUnmount, onMounted } from 'vue';
import FlashAlerts from '@/Components/Ui/FlashAlerts.vue';
import { initLayoutBehaviors } from '@/core/legacy-init';

const props = defineProps({
	fullscreen: {
		type: Boolean,
		default: false,
	},
	hideFooter: {
		type: Boolean,
		default: false,
	},
	wide: {
		type: Boolean,
		default: false,
	},
	hideShowcase: {
		type: Boolean,
		default: false,
	},
});

const bodyClass = computed(() =>
	props.fullscreen ? 'min-h-screen' : 'min-h-screen flex flex-col items-center justify-center p-4'
);

const shellClass = computed(() => [
	'guest-shell',
	props.fullscreen ? '!max-w-none !w-full !px-0 !py-0 !min-h-screen flex flex-col' : 'mx-auto',
	props.hideShowcase ? (props.wide ? 'w-full max-w-6xl' : 'max-w-lg') : '',
]);

const panelClass = computed(() => [
	'guest-panel',
	props.fullscreen ? '!rounded-none !border-0 !bg-transparent !shadow-none flex-1 min-h-0 md:!grid-cols-[40%_60%]' : '',
	props.hideShowcase ? '!grid-cols-1' : '',
]);

const contentClass = computed(() => [
	'guest-content',
	props.fullscreen
		? '!px-5 !py-8 sm:!px-8 sm:!py-10 md:!px-12 md:!py-12 bg-white/85 backdrop-blur-xl dark:bg-slate-950/80'
		: '',
]);

const showcaseClass = computed(() => [
	'guest-showcase',
	props.fullscreen ? 'md:border-r md:border-white/10' : '',
]);

const footerClass = computed(() => (props.fullscreen ? 'px-5 py-6 sm:px-8 md:px-12' : 'pt-5'));

let previousBodyClass = null;
let previousBodyStyle = null;

onMounted(() => {
	previousBodyClass = document.body.className;
	previousBodyStyle = document.body.getAttribute('style');

	document.body.className = bodyClass.value;
	document.body.style.fontFamily = "'Inter', sans-serif";
	document.body.style.background = props.fullscreen
		? ''
		: 'linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%)';

	initLayoutBehaviors();
});

onBeforeUnmount(() => {
	document.body.className = previousBodyClass || '';
	if (previousBodyStyle) {
		document.body.setAttribute('style', previousBodyStyle);
	} else {
		document.body.removeAttribute('style');
	}
});
</script>

<template>
	<div>
		<button
			type="button"
			data-theme-toggle
			aria-label="Ganti tema"
			title="Ganti tema"
			class="fixed right-4 top-4 z-50 inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200/80 bg-white/80 text-slate-600 shadow-sm backdrop-blur transition hover:text-primary dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-300 dark:hover:text-white"
		>
			<i class="fa-solid fa-moon"></i>
		</button>

		<FlashAlerts />

		<div :class="shellClass">
			<div :class="panelClass">
				<section v-if="!hideShowcase" :class="showcaseClass">
					<span class="page-kicker">Platform Ujian TKA</span>
					<h1 class="mt-5 text-center text-3xl md:text-4xl">
						Ruang belajar dan operasional yang terasa lebih modern, ringan, dan jelas.
					</h1>
					<p class="mx-auto mt-4 max-w-md text-center text-sm leading-6 text-slate-200">
						Ujion membantu sekolah dan guru mengelola ujian, materi, dan aktivasi akun dalam satu alur yang lebih tertata.
					</p>
					<div class="mt-8 grid grid-cols-1 gap-3 text-center sm:grid-cols-2">
						<div class="hero-chip rounded-2xl border border-white/12 bg-white/8 p-4 backdrop-blur-sm">
							<i class="fa-solid fa-shield-halved text-white/90"></i>
							<span class="font-semibold">Aktivasi akun lebih terkontrol</span>
						</div>
						<div class="hero-chip rounded-2xl border border-white/12 bg-white/8 p-4 backdrop-blur-sm">
							<i class="fa-solid fa-layer-group text-white/90"></i>
							<span class="font-semibold">Pengelolaan materi dan soal lebih rapi</span>
						</div>
						<div class="hero-chip rounded-2xl border border-white/12 bg-white/8 p-4 backdrop-blur-sm">
							<i class="fa-solid fa-chart-line text-white/90"></i>
							<span class="font-semibold">Dashboard siap untuk monitoring harian</span>
						</div>
						<div class="hero-chip rounded-2xl border border-white/12 bg-white/8 p-4 backdrop-blur-sm">
							<i class="fa-solid fa-clipboard-check text-white/90"></i>
							<span class="font-semibold">Rekap & hasil ujian lebih cepat</span>
						</div>
					</div>
				</section>

				<section :class="contentClass">
					<slot />
				</section>
			</div>

			<footer v-if="!hideFooter" :class="footerClass" class="text-center text-xs text-textSecondary dark:text-slate-500">
				&copy; {{ new Date().getFullYear() }} Ujion. All rights reserved.
			</footer>
		</div>
	</div>
</template>

<style>
.dark body {
	background: linear-gradient(135deg, #020617 0%, #1e1b4b 100%);
}
</style>
