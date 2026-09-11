<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';
import PaginationLinks from '@/Components/Ui/PaginationLinks.vue';

const props = defineProps({
	posts: {
		type: Object,
		required: true,
	},
});

const postItems = computed(() => props.posts.data || []);

const isBlank = (value) => value === null || value === undefined || String(value).trim() === '';
</script>

<template>
	<Head title="Blog / Artikel" />

	<SuperadminLayout>
		<div class="space-y-6">
			<div class="flex flex-wrap items-start justify-between gap-4">
				<div>
					<h1 class="text-2xl font-bold">Blog / Artikel</h1>
					<p class="mt-2 text-sm text-textSecondary dark:text-slate-300">
						Kelola artikel edukasi yang tampil di halaman publik <span class="font-mono">/artikel</span>. Artikel terbit akan masuk sitemap dan bisa muncul di hasil pencarian Google.
					</p>
				</div>
				<div class="flex gap-2">
					<Link :href="route('superadmin.blog.create')" class="btn-primary">
						<i class="fa-solid fa-plus mr-2"></i>
						Tambah Artikel
					</Link>
				</div>
			</div>

			<div class="card">
				<div class="text-xs font-semibold uppercase tracking-wide text-muted">Daftar</div>
				<div class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">Artikel</div>

				<div class="mt-4 overflow-x-auto">
					<table class="min-w-full text-sm">
						<thead>
							<tr class="text-left text-muted">
								<th class="py-2 pr-4">Judul</th>
								<th class="py-2 pr-4">Slug</th>
								<th class="py-2 pr-4">Status</th>
								<th class="py-2 pr-4">Terbit</th>
								<th class="py-2 pr-4">Aksi</th>
							</tr>
						</thead>
						<tbody>
							<template v-if="postItems.length > 0">
								<tr v-for="post in postItems" :key="post.id" class="border-t border-slate-200/60 dark:border-slate-700/60">
									<td class="py-3 pr-4">
										<div class="font-semibold text-slate-900 dark:text-slate-100">{{ post.title }}</div>
										<div v-if="!isBlank(post.excerpt)" class="mt-1 text-xs text-textSecondary dark:text-slate-300">{{ post.excerpt_limited }}</div>
									</td>
									<td class="py-3 pr-4 font-mono text-xs">{{ post.slug }}</td>
									<td class="py-3 pr-4">
										<span v-if="post.is_published" class="inline-flex items-center rounded-full bg-emerald-500/10 px-2 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-200">Terbit</span>
										<span v-else class="inline-flex items-center rounded-full bg-slate-500/10 px-2 py-1 text-xs font-semibold text-slate-700 dark:text-slate-200">Draf</span>
									</td>
									<td class="py-3 pr-4 text-xs text-textSecondary dark:text-slate-300">
										{{ post.published_at_formatted ?? '-' }}
									</td>
									<td class="py-3 pr-4">
										<div class="flex flex-wrap gap-2">
											<Link :href="route('superadmin.blog.edit', post.id)" class="btn-secondary">
												<i class="fa-solid fa-pen mr-2"></i>
												Edit
											</Link>

											<a v-if="post.is_published" :href="route('artikel.show', post.slug)" target="_blank" rel="noopener" class="btn-secondary">
												<i class="fa-solid fa-arrow-up-right-from-square mr-2"></i>
												Lihat
											</a>

											<form method="POST" :action="route('superadmin.blog.toggle', post.id)">
												<input type="hidden" name="_token" :value="$page.props.csrf_token">
												<button type="submit" class="btn-secondary">
													<i class="mr-2" :class="post.is_published ? 'fa-solid fa-toggle-on' : 'fa-solid fa-toggle-off'"></i>
													{{ post.is_published ? 'Jadikan Draf' : 'Terbitkan' }}
												</button>
											</form>

											<form method="POST" :action="route('superadmin.blog.destroy', post.id)" onsubmit="return confirm('Hapus artikel ini?')">
												<input type="hidden" name="_token" :value="$page.props.csrf_token">
												<button type="submit" class="btn-danger">
													<i class="fa-solid fa-trash mr-2"></i>
													Hapus
												</button>
											</form>
										</div>
									</td>
								</tr>
							</template>
							<tr v-else class="border-t border-slate-200/60 dark:border-slate-700/60">
								<td colspan="5" class="py-6 text-center text-textSecondary dark:text-slate-300">
									Belum ada artikel. Klik "Tambah Artikel" untuk mulai menulis.
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<div v-if="posts.last_page > 1" class="mt-4">
					<PaginationLinks :paginator="posts" />
				</div>
			</div>
		</div>
	</SuperadminLayout>
</template>
