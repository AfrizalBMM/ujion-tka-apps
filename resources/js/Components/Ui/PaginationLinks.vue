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
				class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 cursor-not-allowed leading-5 rounded-md dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600"
			>
				&laquo; Previous
			</span>
			<a
				v-else
				:href="paginator.prev_page_url"
				rel="prev"
				class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-700 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-800 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:focus:border-blue-700 dark:active:bg-gray-700 dark:active:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 dark:hover:text-gray-200"
			>
				&laquo; Previous
			</a>

			<a
				v-if="paginator.next_page_url"
				:href="paginator.next_page_url"
				rel="next"
				class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-800 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-700 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-800 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200 dark:focus:border-blue-700 dark:active:bg-gray-700 dark:active:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-900 dark:hover:text-gray-200"
			>
				Next &raquo;
			</a>
			<span
				v-else
				class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 cursor-not-allowed leading-5 rounded-md dark:text-gray-300 dark:bg-gray-700 dark:border-gray-600"
			>
				Next &raquo;
			</span>
		</div>

		<div class="hidden sm:flex-1 sm:flex sm:gap-2 sm:items-center sm:justify-between">
			<div>
				<p class="text-sm text-gray-700 leading-5 dark:text-gray-600">
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
							class="inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-not-allowed rounded-l-md leading-5 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400"
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
						class="inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md leading-5 hover:text-gray-400 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:active:bg-gray-700 dark:focus:border-blue-800 dark:text-gray-300 dark:hover:bg-gray-900 dark:hover:text-gray-300"
						aria-label="&laquo; Previous"
					>
						<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
							<path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
						</svg>
					</Link>

					<template v-for="(element, index) in elements()" :key="`element-${index}`">
						<span v-if="element.label === '...'" aria-disabled="true">
							<span class="inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 cursor-default leading-5 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300">...</span>
						</span>
						<span v-else-if="element.active" aria-current="page">
							<span class="inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 cursor-default leading-5 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300">{{ element.label }}</span>
						</span>
						<Link
							v-else
							:href="element.url"
							class="inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:text-gray-700 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:text-gray-300 dark:active:bg-gray-700 dark:focus:border-blue-800 hover:bg-gray-100 dark:hover:bg-gray-900"
							:aria-label="`Go to page ${element.label}`"
						>
							{{ element.label }}
						</Link>
					</template>

					<Link
						v-if="paginator.next_page_url"
						:href="paginator.next_page_url"
						rel="next"
						class="inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md leading-5 hover:text-gray-400 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-500 transition ease-in-out duration-150 dark:bg-gray-800 dark:border-gray-600 dark:active:bg-gray-700 dark:focus:border-blue-800 dark:text-gray-300 dark:hover:bg-gray-900 dark:hover:text-gray-300"
						aria-label="Next &raquo;"
					>
						<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
							<path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
						</svg>
					</Link>
					<span v-else aria-disabled="true" aria-label="Next &raquo;">
						<span
							class="inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-not-allowed rounded-r-md leading-5 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400"
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
