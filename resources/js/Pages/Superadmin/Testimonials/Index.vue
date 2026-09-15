<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';

defineProps({
	testimonials: {
		type: Array,
		default: () => [],
	},
});

const isBlank = (value) => value === null || value === undefined || String(value).trim() === '';
</script>

<template>
	<Head title="Testimoni" />

	<SuperadminLayout>
		<div class="space-y-6">
			<div class="flex flex-wrap items-start justify-between gap-4">
				<div>
					<h1 class="text-2xl font-bold">Testimoni</h1>
					<p class="mt-2 text-sm text-textSecondary dark:text-slate-300">
						Kelola feedback dari pengguna yang tampil di landing page pada section "Mereka sudah mencoba". Testimoni aktif akan tampil sebagai slider otomatis.
					</p>
				</div>
				<div class="flex gap-2">
					<Link :href="route('superadmin.testimonials.create')" class="btn-primary">
						<i class="fa-solid fa-plus mr-2"></i>
						Tambah Testimoni
					</Link>
				</div>
			</div>

			<div class="card">
				<div class="text-xs font-semibold uppercase tracking-wide text-muted">Daftar</div>
				<div class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">Testimoni</div>

				<div class="mt-4 overflow-x-auto">
					<table class="min-w-full text-sm">
						<thead>
							<tr class="text-left text-muted">
								<th class="py-2 pr-4">Pengguna</th>
								<th class="py-2 pr-4">Rating</th>
								<th class="py-2 pr-4">Urutan</th>
								<th class="py-2 pr-4">Status</th>
								<th class="py-2 pr-4">Aksi</th>
							</tr>
						</thead>
						<tbody>
							<template v-if="testimonials.length > 0">
								<tr v-for="testimonial in testimonials" :key="testimonial.id" class="border-t border-slate-200/60 dark:border-slate-700/60">
									<td class="py-3 pr-4">
										<div class="flex items-center gap-3">
											<img
												v-if="testimonial.photo_path"
												:src="testimonial.photo_url"
												:alt="testimonial.name"
												class="h-10 w-10 rounded-full object-cover"
											>
											<div v-else class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-primary text-white">
												<i class="fa-solid fa-user text-sm"></i>
											</div>
											<div>
												<div class="font-semibold text-slate-900 dark:text-slate-100">{{ testimonial.name }}</div>
												<div v-if="!isBlank(testimonial.role)" class="text-xs text-textSecondary dark:text-slate-300">{{ testimonial.role }}</div>
											</div>
										</div>
									</td>
									<td class="py-3 pr-4">
										<div class="flex gap-0.5 text-amber-400">
											<i v-for="i in 5" :key="i" class="fa-solid fa-star" :class="i <= testimonial.rating ? '' : 'opacity-25'"></i>
										</div>
									</td>
									<td class="py-3 pr-4 text-textSecondary dark:text-slate-300">{{ testimonial.sort_order }}</td>
									<td class="py-3 pr-4">
										<span v-if="testimonial.is_active" class="inline-flex items-center rounded-full bg-emerald-500/10 px-2 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-200">Aktif</span>
										<span v-else class="inline-flex items-center rounded-full bg-slate-500/10 px-2 py-1 text-xs font-semibold text-slate-700 dark:text-slate-200">Nonaktif</span>
									</td>
									<td class="py-3 pr-4">
										<div class="flex flex-wrap gap-2">
											<Link :href="route('superadmin.testimonials.edit', testimonial.id)" class="btn-secondary">
												<i class="fa-solid fa-pen mr-2"></i>
												Edit
											</Link>

											<form method="POST" :action="route('superadmin.testimonials.toggle', testimonial.id)">
												<input type="hidden" name="_token" :value="$page.props.csrf_token">
												<button type="submit" class="btn-secondary">
													<i class="mr-2" :class="testimonial.is_active ? 'fa-solid fa-toggle-on' : 'fa-solid fa-toggle-off'"></i>
													{{ testimonial.is_active ? 'Sembunyikan' : 'Tampilkan' }}
												</button>
											</form>

											<form method="POST" :action="route('superadmin.testimonials.destroy', testimonial.id)" onsubmit="return confirm('Hapus testimoni ini?')">
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
									Belum ada testimoni. Klik "Tambah Testimoni" untuk mulai.
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</SuperadminLayout>
</template>
