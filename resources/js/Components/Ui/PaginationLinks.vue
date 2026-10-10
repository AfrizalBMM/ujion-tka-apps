<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
	paginator: {
		type: Object,
		required: true,
	},
});

const elements = () => props.paginator.links.slice(1, -1);
</script>

<template>
	<nav role="navigation" aria-label="Pagination Navigation">
		<div class="flex gap-2 items-center justify-between sm:hidden">
			<span
				v-if="!paginator.prev_page_url"
				class="inline-flex items-center px-4 py-2 text-sm font-medium text-textSecondary bg-white border border-border cursor-not-allowed leading-5 rounded-md dark:text-slate-400 dark:bg-slate-800 dark:border-slate-700"
			>
				&laquo; Previous
			</span>
			<a
				v-else
				:href="paginator.prev_page_url"
				rel="prev"
				class="inline-flex items-center px-4 py-2 text-sm font-medium text-textPrimary bg-white border border-border leading-5 rounded-md hover:text-textPrimary hover:bg-slate-50 focus:outline-none focus:ring focus:ring-primary/20 focus:border-primary active:bg-slate-100 transition ease-in-out duration-150 dark:bg-slate-900/60 dark:border-slate-700 dark:text-slate-200 dark:focus:border-primary dark:active:bg-slate-800 dark:active:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-slate-200"
			>
				&laquo; Previous
			</a>

			<a
				v-if="paginator.next_page_url"
				:href="paginator.next_page_url"
				rel="next"
				class="inline-flex items-center px-4 py-2 text-sm font-medium text-textPrimary bg-white border border-border leading-5 rounded-md hover:text-textPrimary hover:bg-slate-50 focus:outline-none focus:ring focus:ring-primary/20 focus:border-primary active:bg-slate-100 transition ease-in-out duration-150 dark:bg-slate-900/60 dark:border-slate-700 dark:text-slate-200 dark:focus:border-primary dark:active:bg-slate-800 dark:active:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-slate-200"
			>
				Next &raquo;
			</a>
			<span
				v-else
				class="inline-flex items-center px-4 py-2 text-sm font-medium text-textSecondary bg-white border border-border cursor-not-allowed leading-5 rounded-md dark:text-slate-400 dark:bg-slate-800 dark:border-slate-700"
			>
				Next &raquo;
			</span>
		</div>

		<div class="hidden sm:flex-1 sm:flex sm:gap-2 sm:items-center sm:justify-between">
			<div>
				<p class="text-sm text-textPrimary leading-5 dark:text-slate-400">
					Showing
					<template v-if="paginator.from">
						<span class="font-medium">{{ paginator.from }}</span>
						to
						<span class="font-medium">{{ paginator.to }}</span>
					</template>
					<template v-else>{{ paginator.data.length }}</template>
					of
					<span class="font-medium">{{ paginator.total }}</span>
					results
				</p>
			</div>

			<div>
				<span class="inline-flex rtl:flex-row-reverse shadow-sm rounded-md">
					<span v-if="!paginator.prev_page_url" aria-disabled="true" aria-label="&laquo; Previous">
						<span
							class="inline-flex items-center px-2 py-2 text-sm font-medium text-muted bg-white border border-border cursor-not-allowed rounded-l-md leading-5 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-500"
							aria-hidden="true"
						>
							<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
								<path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
							</svg>
						</span>
					</span>
					<Link
						v-else
						:href="paginator.prev_page_url"
						rel="prev"
						class="inline-flex items-center px-2 py-2 text-sm font-medium text-muted bg-white border border-border rounded-l-md leading-5 hover:text-textSecondary focus:outline-none focus:ring focus:ring-primary/20 focus:border-primary active:bg-slate-100 transition ease-in-out duration-150 dark:bg-slate-900/60 dark:border-slate-700 dark:active:bg-slate-800 dark:focus:border-primary dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-slate-300"
						aria-label="&laquo; Previous"
					>
						<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
							<path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
						</svg>
					</Link>

					<template v-for="(element, index) in elements()" :key="`element-${index}`">
						<span v-if="element.label === '...'" aria-disabled="true">
							<span class="inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-textPrimary bg-white border border-border cursor-default leading-5 dark:bg-slate-900/60 dark:border-slate-700 dark:text-slate-300">...</span>
						</span>
						<span v-else-if="element.active" aria-current="page">
							<span class="inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-textPrimary bg-primary/10 border border-primary/20 cursor-default leading-5 dark:bg-primary/15 dark:border-primary/30 dark:text-slate-200">{{ element.label }}</span>
						</span>
						<Link
							v-else
							:href="element.url"
							class="inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-textPrimary bg-white border border-border leading-5 hover:text-textPrimary hover:bg-slate-50 focus:outline-none focus:ring focus:ring-primary/20 focus:border-primary active:bg-slate-100 transition ease-in-out duration-150 dark:bg-slate-900/60 dark:border-slate-700 dark:text-slate-300 dark:hover:text-slate-300 dark:active:bg-slate-800 dark:focus:border-primary dark:hover:bg-slate-900"
							:aria-label="`Go to page ${element.label}`"
						>
							{{ element.label }}
						</Link>
					</template>

					<Link
						v-if="paginator.next_page_url"
						:href="paginator.next_page_url"
						rel="next"
						class="inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-muted bg-white border border-border rounded-r-md leading-5 hover:text-textSecondary focus:outline-none focus:ring focus:ring-primary/20 focus:border-primary active:bg-slate-100 transition ease-in-out duration-150 dark:bg-slate-900/60 dark:border-slate-700 dark:active:bg-slate-800 dark:focus:border-primary dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-slate-300"
						aria-label="Next &raquo;"
					>
						<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
							<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
						</svg>
					</Link>
					<span v-else aria-disabled="true" aria-label="Next &raquo;">
						<span
							class="inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-muted bg-white border border-border cursor-not-allowed rounded-r-md leading-5 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-500"
							aria-hidden="true"
						>
							<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
								<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
							</svg>
						</span>
					</span>
				</span>
			</div>
		</div>
	</nav>
</template>
