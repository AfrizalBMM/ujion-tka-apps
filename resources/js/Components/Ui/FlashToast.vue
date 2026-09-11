<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
	type: {
		type: String,
		default: 'info',
	},
	title: {
		type: String,
		default: null,
	},
	message: {
		type: String,
		required: true,
	},
	description: {
		type: String,
		default: null,
	},
	token: {
		type: String,
		default: null,
	},
	tokenLabel: {
		type: String,
		default: 'Token akses baru',
	},
	copyBlock: {
		type: String,
		default: null,
	},
	copyBlockLabel: {
		type: String,
		default: 'Template pesan siap kirim',
	},
	duration: {
		type: Number,
		default: 5000,
	},
});

const emit = defineEmits(['close']);

const remaining = ref(props.duration);

let startTime = Date.now();
let pausedAt = null;
let pausedTotal = 0;
let rafId = null;
let closed = false;

const seconds = computed(() => Math.ceil(remaining.value / 1000));
const progress = computed(() => Math.max(0, Math.min(1, remaining.value / props.duration)));

const typeClass = computed(() => {
	switch (props.type) {
		case 'success':
			return 'alert-success';
		case 'warning':
			return 'alert-warning';
		case 'danger':
			return 'alert-danger';
		default:
			return 'alert-info';
	}
});

const icon = computed(() => {
	switch (props.type) {
		case 'success':
			return 'fa-solid fa-circle-check';
		case 'warning':
			return 'fa-solid fa-triangle-exclamation';
		case 'danger':
			return 'fa-solid fa-circle-xmark';
		default:
			return 'fa-solid fa-circle-info';
	}
});

const tick = () => {
	if (closed) return;

	if (pausedAt !== null) {
		rafId = requestAnimationFrame(tick);
		return;
	}

	const elapsed = Date.now() - startTime - pausedTotal;
	remaining.value = Math.max(0, props.duration - elapsed);

	if (remaining.value <= 0) {
		emit('close');
		return;
	}

	rafId = requestAnimationFrame(tick);
};

onMounted(() => {
	rafId = requestAnimationFrame(tick);
});

onBeforeUnmount(() => {
	closed = true;
	if (rafId) cancelAnimationFrame(rafId);
});

const pause = () => {
	pausedAt = Date.now();
};

const resume = () => {
	if (pausedAt !== null) {
		pausedTotal += Date.now() - pausedAt;
		pausedAt = null;
	}
};

const close = () => {
	closed = true;
	if (rafId) cancelAnimationFrame(rafId);
	emit('close');
};
</script>

<template>
	<div
		class="alert pointer-events-auto w-[calc(100vw-2rem)] max-w-sm shadow-2xl"
		:class="typeClass"
		role="status"
		@mouseenter="pause"
		@mouseleave="resume"
	>
		<div class="flex items-start justify-between gap-3">
			<div class="flex items-start gap-3">
				<i :class="icon" class="mt-0.5"></i>
				<div>
					<div v-if="title" class="font-bold">{{ title }}</div>
					<div class="text-sm font-medium" :class="title ? 'mt-1' : ''">{{ message }}</div>
					<div v-if="description" class="mt-1 text-sm opacity-90">{{ description }}</div>
					<template v-if="token">
						<div class="mt-3 text-xs font-semibold uppercase tracking-wide opacity-75">{{ tokenLabel }}</div>
						<div class="mt-2 rounded-xl border border-current/20 bg-white/60 px-3 py-2 font-mono text-sm tracking-widest text-slate-800">
							{{ token }}
						</div>
					</template>
					<template v-if="copyBlock">
						<div class="mt-4 text-xs font-semibold uppercase tracking-wide opacity-75">{{ copyBlockLabel }}</div>
						<pre class="mt-2 whitespace-pre-wrap rounded-xl border border-current/20 bg-white/60 px-3 py-3 text-sm text-slate-800">{{ copyBlock }}</pre>
					</template>
				</div>
			</div>
			<div class="flex shrink-0 items-center gap-2">
				<span class="flash-countdown">{{ seconds }}</span>
				<button type="button" class="btn-secondary px-3" aria-label="Tutup notifikasi" @click="close">
					<i class="fa-solid fa-xmark"></i>
				</button>
			</div>
		</div>
		<div class="flash-progress" :style="{ transform: `scaleX(${progress})` }"></div>
	</div>
</template>
