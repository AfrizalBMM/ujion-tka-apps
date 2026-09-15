<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue';

defineProps({
	templates: {
		type: Array,
		default: () => [],
	},
});

const isBlank = (value) => value === null || value === undefined || String(value).trim() === '';
</script>

<template>
	<Head title="Pesan Otomatis" />

	<SuperadminLayout>
		<div class="space-y-6">
			<div class="flex flex-wrap items-start justify-between gap-4">
				<div>
					<h1 class="text-2xl font-bold">Pesan Otomatis</h1>
					<p class="mt-2 text-sm text-textSecondary dark:text-slate-300">
						Kelola copywriting untuk balasan bot, notifikasi pembayaran, dan pesan event. Placeholder yang didukung: <span class="font-mono">{name}</span>, <span class="font-mono">{token}</span>, <span class="font-mono">{reason}</span>, <span class="font-mono">{exam_title}</span>, <span class="font-mono">{exam_date}</span>, <span class="font-mono">{login_url}</span>.
					</p>
				</div>
				<div class="flex gap-2">
					<Link :href="route('superadmin.wa-templates.create')" class="btn-primary">
						<i class="fa-solid fa-plus mr-2"></i>
						Tambah Template
					</Link>
				</div>
			</div>

			<div class="card">
				<div class="text-xs font-semibold uppercase tracking-wide text-muted">Daftar</div>
				<div class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">Template pesan</div>

				<div class="mt-4 overflow-x-auto">
					<table class="min-w-full text-sm">
						<thead>
							<tr class="text-left text-muted">
								<th class="py-2 pr-4">Key</th>
								<th class="py-2 pr-4">Judul</th>
								<th class="py-2 pr-4">Status</th>
								<th class="py-2 pr-4">Aksi</th>
							</tr>
						</thead>
						<tbody>
							<template v-if="templates.length > 0">
								<tr v-for="template in templates" :key="template.id" class="border-t border-slate-200/60 dark:border-slate-700/60">
									<td class="py-3 pr-4 font-mono text-xs">{{ template.key }}</td>
									<td class="py-3 pr-4">
										<div class="font-semibold text-slate-900 dark:text-slate-100">{{ template.title }}</div>
										<div v-if="!isBlank(template.description)" class="mt-1 text-xs text-textSecondary dark:text-slate-300">{{ template.description }}</div>
									</td>
									<td class="py-3 pr-4">
										<span v-if="template.is_active" class="inline-flex items-center rounded-full bg-emerald-500/10 px-2 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-200">Aktif</span>
										<span v-else class="inline-flex items-center rounded-full bg-slate-500/10 px-2 py-1 text-xs font-semibold text-slate-700 dark:text-slate-200">Nonaktif</span>
									</td>
									<td class="py-3 pr-4">
										<div class="flex flex-wrap gap-2">
											<Link :href="route('superadmin.wa-templates.edit', template.id)" class="btn-secondary">
												<i class="fa-solid fa-pen mr-2"></i>
												Edit
											</Link>

											<form method="POST" :action="route('superadmin.wa-templates.toggle', template.id)">
												<input type="hidden" name="_token" :value="$page.props.csrf_token">
												<button type="submit" class="btn-secondary">
													<i class="mr-2" :class="template.is_active ? 'fa-solid fa-toggle-on' : 'fa-solid fa-toggle-off'"></i>
													{{ template.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
												</button>
											</form>

											<form method="POST" :action="route('superadmin.wa-templates.destroy', template.id)" onsubmit="return confirm('Hapus template ini?')">
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
								<td colspan="4" class="py-6 text-center text-textSecondary dark:text-slate-300">
									Belum ada template. Klik "Tambah Template" untuk mulai.
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</SuperadminLayout>
</template>
