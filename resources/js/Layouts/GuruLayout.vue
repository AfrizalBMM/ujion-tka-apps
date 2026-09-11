<script setup>
import { computed, onBeforeUnmount, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { initDokuCheckout } from '@/core/doku-checkout';
import { initGuruSidebarToken } from '@/core/guru-sidebar-token';
import { initLayoutBehaviors } from '@/core/legacy-init';
import FlashAlerts from '@/Components/Ui/FlashAlerts.vue';
import ConfirmModal from '@/Components/Ui/ConfirmModal.vue';

const page = usePage();

const user = computed(() => page.props.auth?.user || null);
const guruLayout = computed(() => page.props.guruLayout || null);

const paymentLocked = computed(() => guruLayout.value?.paymentLocked || false);
const waGroupLink = computed(() => guruLayout.value?.waGroupLink || null);
const notifLogs = computed(() => guruLayout.value?.notifLogs || []);
const dokuConfig = computed(() => guruLayout.value?.dokuConfig || null);

const guruAvatarUrl = computed(
	() => user.value?.avatar_url || 'https://ui-avatars.com/api/?name=Guru&background=22C1C3&color=fff'
);

const showTokenCard = computed(
	() => user.value?.account_status === 'active' && !!(user.value?.access_token ?? '')
);

const lockedLinks = [
	{ route: 'guru.materials', icon: 'fa-book', label: 'Materi', active: 'guru.materials' },
	{ route: 'guru.soal-ujion.index', icon: 'fa-layer-group', label: 'Soal dari Ujion', active: 'guru.soal-ujion*' },
	{ route: 'guru.personal-questions', icon: 'fa-database', label: 'Soal Pribadi', active: 'guru.personal-questions*' },
	{ route: 'guru.paket-soal.index', icon: 'fa-database', label: 'Paket Soal TKA', active: 'guru.paket-soal.*' },
	{ route: 'guru.exams', icon: 'fa-file-lines', label: 'Simulasi Ujian', active: 'guru.exams*' },
	{ route: 'guru.results.index', icon: 'fa-chart-line', label: 'Hasil Siswa', active: 'guru.results.*' },
];

let previousBodyClass = null;

onMounted(() => {
	previousBodyClass = document.body.className;
	document.body.className = 'app-shell flex flex-col';
	document.body.removeAttribute('style');

	if (dokuConfig.value) {
		document.body.setAttribute('data-dashboard-shell', 'guru');
		document.body.dataset.dokuConfig = JSON.stringify(dokuConfig.value);
		initDokuCheckout();
	} else {
		document.body.setAttribute('data-dashboard-shell', 'guru');
	}

	initGuruSidebarToken();
	initLayoutBehaviors();
});

onBeforeUnmount(() => {
	document.body.className = previousBodyClass || '';
	delete document.body.dataset.dokuConfig;
	document.body.removeAttribute('data-dashboard-shell');
});
</script>

<template>
	<div>
		<header class="app-topbar">
			<div class="app-topbar-panel">
				<div class="app-brand">
					<div class="app-brand-mark">
						<i class="fa-solid fa-graduation-cap"></i>
					</div>
					<div class="app-brand-copy">
						<div class="app-brand-subtitle">Semangat!!!</div>
						<div class="app-brand-title">Guru / Operator</div>
					</div>
				</div>

				<div class="app-topbar-actions">
					<div class="app-topbar-meta">
						<span class="font-semibold uppercase tracking-[0.24em] text-[11px]">Jam</span>
						<span id="live-clock" class="app-clock"></span>
					</div>
					<button class="icon-button hidden md:inline-flex" title="Perbesar Font" data-font-size="increase">
						<i class="fa-solid fa-magnifying-glass-plus"></i>
					</button>
					<button class="icon-button hidden md:inline-flex" title="Perkecil Font" data-font-size="decrease">
						<i class="fa-solid fa-magnifying-glass-minus"></i>
					</button>
					<button class="icon-button hidden md:inline-flex" title="Ganti Tema" data-theme-toggle>
						<i class="fa-solid fa-moon"></i>
					</button>
					<div class="app-user-menu">
						<button class="icon-button relative" title="Notifikasi">
							<i class="fa-solid fa-bell"></i>
							<span
								v-if="notifLogs.length > 0"
								class="absolute -right-0.5 -top-0.5 flex h-2.5 w-2.5 items-center justify-center rounded-full bg-primary ring-2 ring-white dark:ring-slate-950"
							></span>
						</button>
						<div class="app-dropdown min-w-72 max-w-80">
							<div class="border-b border-slate-200/70 px-3 py-2 dark:border-slate-700/60">
								<span class="text-sm font-bold text-slate-900 dark:text-white">Notifikasi</span>
							</div>
							<div class="max-h-80 overflow-y-auto py-1">
								<div
									v-for="log in notifLogs"
									:key="log.label + log.created_at"
									class="flex flex-col gap-0.5 px-3 py-2 transition hover:bg-primary/8 dark:hover:bg-slate-900/70"
								>
									<div class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ log.label }}</div>
									<div class="text-xs text-textSecondary dark:text-slate-400">{{ log.created_at }}</div>
								</div>
								<div
									v-if="notifLogs.length === 0"
									class="px-3 py-6 text-center text-sm text-textSecondary dark:text-slate-400"
								>
									<i class="fa-solid fa-bell-slash mb-1 block text-lg text-muted"></i>
									Belum ada notifikasi
								</div>
							</div>
						</div>
					</div>
					<div class="app-user-menu">
						<button class="app-user-trigger">
							<img :src="guruAvatarUrl" alt="avatar" class="app-user-avatar" />
							<div class="app-user-copy">
								<div class="app-user-name">{{ user?.name ?? 'Guru' }}</div>
								<div class="app-user-role">Guru / Operator</div>
							</div>
							<i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
						</button>
						<div class="app-dropdown">
							<div class="md:hidden">
								<button type="button" class="app-dropdown-link w-full text-left" data-font-size="increase">
									<i class="fa-solid fa-magnifying-glass-plus fa-fw shrink-0"></i>
									Perbesar tampilan
								</button>
								<button type="button" class="app-dropdown-link w-full text-left" data-font-size="decrease">
									<i class="fa-solid fa-magnifying-glass-minus fa-fw shrink-0"></i>
									Perkecil tampilan
								</button>
								<button type="button" class="app-dropdown-link w-full text-left" data-theme-toggle>
									<i class="fa-solid fa-moon fa-fw shrink-0"></i>
									Dark mode
								</button>
								<div class="my-1 border-t border-slate-200/70 dark:border-slate-700/60"></div>
							</div>
							<a :href="route('guru.profile')" class="app-dropdown-link">
								<i class="fa-solid fa-user fa-fw shrink-0"></i>
								Profil
							</a>
							<a :href="route('guru.guide')" class="app-dropdown-link">
								<i class="fa-solid fa-circle-info fa-fw shrink-0"></i>
								Panduan
							</a>
							<form method="POST" :action="route('logout')">
								<input type="hidden" name="_token" :value="page.props.csrf_token" />
								<button type="submit" class="app-dropdown-link w-full text-left">
									<i class="fa-solid fa-right-from-bracket fa-fw shrink-0"></i>
									Keluar
								</button>
							</form>
						</div>
					</div>
				</div>
			</div>
		</header>

		<nav class="bottom-nav">
			<div class="bottom-nav-track">
				<a :href="route('guru.dashboard')" class="bottom-nav-item" :class="route().current('guru.dashboard') ? 'active' : ''">
					<i class="fa-solid fa-house"></i>
					Beranda
				</a>
				<template v-if="paymentLocked">
					<a href="#" class="bottom-nav-item opacity-60" data-payment-locked>
						<i class="fa-solid fa-book"></i>
						Materi
						<i class="fa-solid fa-lock absolute right-1 top-1 text-[9px] text-amber-500"></i>
					</a>
					<a href="#" class="bottom-nav-item opacity-60" data-payment-locked>
						<i class="fa-solid fa-layer-group"></i>
						Soal
						<i class="fa-solid fa-lock absolute right-1 top-1 text-[9px] text-amber-500"></i>
					</a>
					<a href="#" class="bottom-nav-item opacity-60" data-payment-locked>
						<i class="fa-solid fa-file-pen"></i>
						Ujian
						<i class="fa-solid fa-lock absolute right-1 top-1 text-[9px] text-amber-500"></i>
					</a>
				</template>
				<template v-else>
					<a :href="route('guru.materials')" class="bottom-nav-item" :class="route().current('guru.materials*') ? 'active' : ''">
						<i class="fa-solid fa-book"></i>
						Materi
					</a>
					<a
						:href="route('guru.paket-soal.index')"
						class="bottom-nav-item"
						:class="route().current('guru.paket-soal.*') || route().current('guru.soal.*') ? 'active' : ''"
					>
						<i class="fa-solid fa-layer-group"></i>
						Soal
					</a>
					<a :href="route('guru.exams')" class="bottom-nav-item" :class="route().current('guru.exams*') ? 'active' : ''">
						<i class="fa-solid fa-file-pen"></i>
						Ujian
					</a>
				</template>
				<a :href="route('guru.profile')" class="bottom-nav-item" :class="route().current('guru.profile*') ? 'active' : ''">
					<i class="fa-solid fa-user"></i>
					Akun
				</a>
			</div>
		</nav>

		<div class="app-body">
			<aside class="sidebar-container" data-app-sidebar>
				<nav class="sidebar-nav">
					<div class="sidebar-section-row">
						<div class="sidebar-section-title sidebar-section-title-static">Utama</div>
						<button
							type="button"
							class="sidebar-toggle"
							data-sidebar-toggle
							aria-label="Toggle sidebar"
							aria-expanded="true"
							title="Ciutkan sidebar"
						>
							<i class="fa-solid fa-angles-left" data-sidebar-toggle-icon></i>
						</button>
					</div>
					<a :href="route('guru.dashboard')" class="sidebar-link" :class="route().current('guru.dashboard') ? 'active' : ''">
						<i class="fa-solid fa-gauge-high w-5"></i>
						<span class="sidebar-link-label">Dashboard</span>
					</a>
					<a :href="route('guru.chat')" class="sidebar-link" :class="route().current('guru.chat') ? 'active' : ''">
						<i class="fa-solid fa-comments w-5"></i>
						<span class="sidebar-link-label">Live Chat</span>
					</a>

					<div class="sidebar-section-title">Konten</div>
					<template v-for="link in lockedLinks" :key="link.route">
						<a
							v-if="!paymentLocked"
							:href="route(link.route)"
							class="sidebar-link"
							:class="route().current(link.active) ? 'active' : ''"
						>
							<i class="fa-solid w-5" :class="link.icon"></i>
							<span class="sidebar-link-label">{{ link.label }}</span>
						</a>
						<a
							v-else
							href="#"
							class="sidebar-link opacity-70"
							data-payment-locked
							title="Selesaikan pembayaran untuk membuka"
						>
							<i class="fa-solid w-5" :class="link.icon"></i>
							<span class="sidebar-link-label">{{ link.label }}</span>
							<i class="fa-solid fa-lock ml-auto text-[10px] text-amber-500"></i>
						</a>
					</template>

					<div class="sidebar-section-title">Akun</div>
					<a :href="route('guru.guide')" class="sidebar-link" :class="route().current('guru.guide') ? 'active' : ''">
						<i class="fa-solid fa-circle-info w-5"></i>
						<span class="sidebar-link-label">Cara Menggunakan</span>
					</a>
					<a :href="route('guru.profile')" class="sidebar-link" :class="route().current('guru.profile*') ? 'active' : ''">
						<i class="fa-solid fa-user w-5"></i>
						<span class="sidebar-link-label">Profil</span>
					</a>
					<div v-if="showTokenCard" class="sidebar-token-card" title="Token akses untuk login guru">
						<div class="flex items-center justify-between gap-2">
							<span class="text-[10px] font-bold uppercase tracking-wide text-muted">Token Akses</span>
							<button
								type="button"
								class="icon-button h-6 w-6 text-xs"
								:data-guru-token-copy="user.access_token"
								title="Salin token"
							>
								<i class="fa-regular fa-copy"></i>
							</button>
						</div>
						<code class="block break-all font-mono text-xs font-bold text-slate-900 dark:text-slate-100">{{ user.access_token }}</code>
						<p class="mt-1 text-[10px] text-muted">Pakai nomor WA + token ini untuk login.</p>
					</div>
					<a v-if="waGroupLink" :href="waGroupLink" target="_blank" rel="noopener" class="sidebar-link">
						<i class="fa-brands fa-whatsapp w-5"></i>
						<span class="sidebar-link-label">Gabung Saluran WA</span>
					</a>
				</nav>
			</aside>

			<main class="page-shell">
				<div class="page-stack">
					<div class="page-content">
						<div class="page-content-inner">
							<FlashAlerts />
							<ConfirmModal />
							<slot />
						</div>
					</div>
					<footer class="page-footer">
						&copy; {{ new Date().getFullYear() }} Ujion. All rights reserved.
					</footer>
				</div>
			</main>
		</div>
	</div>
</template>
