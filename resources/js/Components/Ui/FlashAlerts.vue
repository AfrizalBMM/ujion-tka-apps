<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import FlashToast from './FlashToast.vue';

const page = usePage();

const flash = computed(() => {
	const raw = page.props.flash;
	return raw && typeof raw === 'object' ? raw : null;
});

const status = computed(() => {
	if (flash.value?.message) return null;
	return page.props.status || null;
});

const firstError = computed(() => {
	const values = Object.values(page.props.errors || {});
	return values.length > 0 ? values[0] : null;
});

const resolved = computed(() => {
	const raw = flash.value;

	if (!raw?.message && status.value) {
		return {
			type: raw?.type || 'success',
			title: raw?.title || null,
			message: status.value,
			description: raw?.description || null,
			token: raw?.token || null,
			tokenLabel: raw?.token_label || 'Token akses baru',
			copyBlock: raw?.copy_block || null,
			copyBlockLabel: raw?.copy_block_label || 'Template pesan siap kirim',
		};
	}

	if (!raw?.message) return null;

	return {
		type: ['success', 'warning', 'danger', 'info'].includes(raw.type) ? raw.type : 'info',
		title: raw.title || null,
		message: raw.message,
		description: raw.description || null,
		token: raw.token || null,
		tokenLabel: raw.token_label || 'Token akses baru',
		copyBlock: raw.copy_block || null,
		copyBlockLabel: raw.copy_block_label || 'Template pesan siap kirim',
	};
});

const flashDuration = computed(() => {
	if (resolved.value?.token || resolved.value?.copyBlock) return 15000;
	switch (resolved.value?.type) {
		case 'danger':
			return 8000;
		case 'warning':
			return 7000;
		default:
			return 5000;
	}
});

const flashVisible = ref(true);
const errorVisible = ref(true);

watch(
	() => resolved.value?.message,
	() => {
		flashVisible.value = true;
	}
);

watch(firstError, () => {
	errorVisible.value = true;
});
</script>

<template>
	<div class="pointer-events-none fixed bottom-4 right-4 z-[70] flex flex-col items-end gap-3">
		<TransitionGroup name="toast">
			<FlashToast
				v-if="firstError && errorVisible"
				:key="`error-${firstError}`"
				type="danger"
				title="Terjadi kesalahan"
				:message="firstError"
				:duration="8000"
				@close="errorVisible = false"
			/>
			<FlashToast
				v-if="resolved && flashVisible"
				:key="`flash-${resolved.message}`"
				:type="resolved.type"
				:title="resolved.title"
				:message="resolved.message"
				:description="resolved.description"
				:token="resolved.token"
				:token-label="resolved.tokenLabel"
				:copy-block="resolved.copyBlock"
				:copy-block-label="resolved.copyBlockLabel"
				:duration="flashDuration"
				@close="flashVisible = false"
			/>
		</TransitionGroup>
	</div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
	transition: opacity 0.3s ease, transform 0.3s ease;
}

.toast-enter-from,
.toast-leave-to {
	opacity: 0;
	transform: translateY(12px);
}

.toast-leave-active {
	position: absolute;
	right: 0;
}
</style>
