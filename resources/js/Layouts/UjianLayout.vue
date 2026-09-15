<script setup>
import { onBeforeUnmount, onMounted } from 'vue';
import { initLayoutBehaviors } from '@/core/legacy-init';

let previousBodyClass = null;

onMounted(() => {
	previousBodyClass = document.body.className;
	document.body.className = 'h-full overflow-hidden bg-slate-100 font-[Inter] dark:bg-slate-950';
	document.body.style.fontFamily = "'Inter', sans-serif";

	initLayoutBehaviors();
});

onBeforeUnmount(() => {
	document.body.className = previousBodyClass || '';
	document.body.removeAttribute('style');
});
</script>

<template>
	<div class="h-full">
		<slot />
	</div>
</template>
