<script setup>
import { computed, onBeforeUnmount, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { initLayoutBehaviors } from '@/core/legacy-init';
import FlashAlerts from '@/Components/Ui/FlashAlerts.vue';
import ConfirmModal from '@/Components/Ui/ConfirmModal.vue';

const page = usePage();

const user = computed(() => page.props.auth?.user || null);
const superadminLayout = computed(() => page.props.superadminLayout || null);

const pendingPaymentCount = computed(() => superadminLayout.value?.pendingPaymentCount || 0);

const materialFilter = computed(() => {
	if (typeof window === 'undefined') return null;
	return new URLSearchParams(window.location.search).get('jenjang');
});

const globalQuestionFilter = computed(() => {
	if (typeof window === 'undefined') return null;
	return new URLSearchParams(window.location.search).get('jenjang_id');
});

const avatarUrl = computed(
	() => user.value?.avatar_url || 'https://ui-avatars.com/api/?name=Superadmin&background=4F6EF7&color=fff'
);

let previousBodyClass = null;

onMounted(() => {
	previousBodyClass = document.body.className;
	document.body.className = 'app-shell flex flex-col';
	document.body.removeAttribute('style');
	document.body.setAttribute('data-dashboard-shell', 'superadmin');

	initLayoutBehaviors();
});

onBeforeUnmount(() => {
	document.body.className = previousBodyClass || '';
	document.body.removeAttribute('data-dashboard-shell');
});
</script>

<template>
	<div>
		<header class="app-topbar">
			<div class="app-topbar-panel">
				<div class="app-brand">
					<div class="app-brand-mark">
						<i class="fa-solid fa-shield-halved"></i>
					</div>
					<div class="app-brand-copy">
						<div class="app-brand-subtitle">Pusat Kontrol</div>
						<div class="app-brand-title">Ujion Superadmin</div>
					</div>
				</div>

				<div class="app-topbar-actions">
					<div class="app-topbar-meta">
						<span class="font-semibold uppercase tracking-[0.24em] text-[11px]">Realtime</span>
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
						<button class="app-user-trigger">
							<img :src="avatarUrl" :alt="`Avatar ${user?.name ?? 'Superadmin'}`" class="app-user-avatar" />
							<div class="app-user-copy">
								<div class="app-user-name">{{ user?.name ?? 'Superadmin' }}</div>
								<div class="app-user-role">Administrator</div>
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
							<a :href="route('superadmin.profile')" class="app-dropdown-link">
								<i class="fa-solid fa-user fa-fw shrink-0"></i>
								Profil
							</a>
							<a :href="route('superadmin.guide')" class="app-dropdown-link">
								<i class="fa-solid fa-circle-info fa-fw shrink-0"></i>
								Panduan
							</a>
							<form method="POST" :action="route('logout')">
								<input type="hidden" name="_token" :value="page.props.csrf_token" />
								<button
									type="submit"
									class="app-dropdown-link w-full text-left"
									data-confirm-title="Konfirmasi Logout"
									data-confirm="Apakah Anda yakin ingin logout?"
								>
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
				<a :href="route('superadmin.dashboard')" class="bottom-nav-item" :class="route().current('superadmin.dashboard') ? 'active' : ''">
					<i class="fa-solid fa-house"></i>
					Beranda
				</a>
				<a :href="route('superadmin.teachers.index')" class="bottom-nav-item" :class="route().current('superadmin.teachers.index') ? 'active' : ''">
					<i class="fa-solid fa-chalkboard-user"></i>
					Guru
				</a>
				<a :href="route('superadmin.global-questions.index')" class="bottom-nav-item" :class="route().current('superadmin.global-questions.*') ? 'active' : ''">
					<i class="fa-solid fa-database"></i>
					Soal
				</a>
				<a
					:href="route('superadmin.exams.index')"
					class="bottom-nav-item"
					:class="route().current('superadmin.exams.index') || route().current('superadmin.landing-exams.*') ? 'active' : ''"
				>
					<i class="fa-solid fa-file-pen"></i>
					Ujian
				</a>
				<a
					:href="route('superadmin.finance.index')"
					class="bottom-nav-item relative"
					:class="
						route().current('superadmin.finance.*') ||
						route().current('superadmin.payment-confirmations.*') ||
						route().current('superadmin.wa-*') ||
						route().current('superadmin.audit-logs.*') ||
						route().current('superadmin.landing-settings.*') ||
						route().current('superadmin.guide')
							? 'active'
							: ''
					"
				>
					<i class="fa-solid fa-gear"></i>
					Sistem
					<span v-if="pendingPaymentCount > 0" class="bottom-nav-badge">{{ pendingPaymentCount }}</span>
				</a>
			</div>
		</nav>

		<div class="app-body">
			<aside class="sidebar-container" data-app-sidebar>
				<nav class="sidebar-nav">
					<div class="sidebar-section-row">
						<div class="sidebar-section-title sidebar-section-title-static">Utama</div>
						<button type="button" class="sidebar-toggle" data-sidebar-toggle aria-label="Toggle sidebar" aria-expanded="true" title="Ciutkan sidebar">
							<i class="fa-solid fa-angles-left" data-sidebar-toggle-icon></i>
						</button>
					</div>
					<a :href="route('superadmin.dashboard')" class="sidebar-link" :class="route().current('superadmin.dashboard') ? 'active' : ''">
						<i class="fa-solid fa-gauge-high w-5"></i>
						<span class="sidebar-link-label">Dashboard</span>
					</a>
					<a :href="route('superadmin.landing-settings.index')" class="sidebar-link" :class="route().current('superadmin.landing-settings.*') ? 'active' : ''">
						<i class="fa-solid fa-globe w-5"></i>
						<span class="sidebar-link-label">Pengaturan Landing</span>
					</a>
					<a :href="route('superadmin.blog.index')" class="sidebar-link" :class="route().current('superadmin.blog.*') ? 'active' : ''">
						<i class="fa-solid fa-newspaper w-5"></i>
						<span class="sidebar-link-label">Blog / Artikel</span>
					</a>
					<a :href="route('superadmin.testimonials.index')" class="sidebar-link" :class="route().current('superadmin.testimonials.*') ? 'active' : ''">
						<i class="fa-solid fa-quote-right w-5"></i>
						<span class="sidebar-link-label">Testimoni</span>
					</a>
					<a :href="route('superadmin.finance.index')" class="sidebar-link" :class="route().current('superadmin.finance.index') ? 'active' : ''">
						<i class="fa-solid fa-credit-card w-5"></i>
						<span class="sidebar-link-label">Keuangan</span>
					</a>
					<a :href="route('superadmin.payment-confirmations.index')" class="sidebar-link" :class="route().current('superadmin.payment-confirmations.*') ? 'active' : ''">
						<i class="fa-solid fa-money-check-dollar w-5"></i>
						<span class="sidebar-link-label flex-1">Riwayat Transaksi</span>
						<span
							v-if="pendingPaymentCount > 0"
							class="inline-flex items-center justify-center rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white shrink-0"
						>
							{{ pendingPaymentCount }}
						</span>
					</a>
					<a :href="route('superadmin.chat.index')" class="sidebar-link" :class="route().current('superadmin.chat.index') ? 'active' : ''">
						<i class="fa-solid fa-comments w-5"></i>
						<span class="sidebar-link-label">Live Chat</span>
					</a>

					<div class="sidebar-section-title">Akademik</div>
					<a :href="route('superadmin.teachers.index')" class="sidebar-link" :class="route().current('superadmin.teachers.index') ? 'active' : ''">
						<i class="fa-solid fa-chalkboard-user w-5"></i>
						<span class="sidebar-link-label">Daftar Guru</span>
					</a>
					<details class="sidebar-submenu" data-sidebar-submenu>
						<summary class="sidebar-link sidebar-submenu-trigger" :class="route().current('superadmin.materials.index') ? 'active' : ''" data-sidebar-submenu-trigger>
							<span class="flex items-center gap-3.5">
								<i class="fa-solid fa-book w-5"></i>
								<span class="sidebar-link-label">Master Materi</span>
							</span>
							<i class="fa-solid fa-chevron-down sidebar-submenu-caret"></i>
						</summary>
						<div class="sidebar-submenu-links">
							<a :href="route('superadmin.materials.index')" class="sidebar-sublink" :class="!materialFilter ? 'active' : ''">
								<span class="sidebar-sublink-badge">ALL</span>
								<span class="sidebar-sublink-label">Semua jenjang</span>
							</a>
							<a :href="route('superadmin.materials.index', { jenjang: 'SD' })" class="sidebar-sublink" :class="materialFilter === 'SD' ? 'active' : ''">
								<span class="sidebar-sublink-badge">SD</span>
								<span class="sidebar-sublink-label">Materi SD</span>
							</a>
							<a :href="route('superadmin.materials.index', { jenjang: 'SMP' })" class="sidebar-sublink" :class="materialFilter === 'SMP' ? 'active' : ''">
								<span class="sidebar-sublink-badge">SMP</span>
								<span class="sidebar-sublink-label">Materi SMP</span>
							</a>
							<a :href="route('superadmin.materials.index', { jenjang: 'SMA' })" class="sidebar-sublink" :class="materialFilter === 'SMA' ? 'active' : ''">
								<span class="sidebar-sublink-badge">SMA</span>
								<span class="sidebar-sublink-label">Materi SMA</span>
							</a>
						</div>
					</details>
					<details class="sidebar-submenu" data-sidebar-submenu>
						<summary class="sidebar-link sidebar-submenu-trigger" :class="route().current('superadmin.global-questions.index') ? 'active' : ''" data-sidebar-submenu-trigger>
							<span class="flex items-center gap-3.5">
								<i class="fa-solid fa-database w-5"></i>
								<span class="sidebar-link-label">Bank Soal Global</span>
							</span>
							<i class="fa-solid fa-chevron-down sidebar-submenu-caret"></i>
						</summary>
						<div class="sidebar-submenu-links">
							<a :href="route('superadmin.global-questions.index')" class="sidebar-sublink" :class="!globalQuestionFilter ? 'active' : ''">
								<span class="sidebar-sublink-badge">ALL</span>
								<span class="sidebar-sublink-label">Semua jenjang</span>
							</a>
							<a :href="route('superadmin.global-questions.index', { jenjang_id: 1 })" class="sidebar-sublink" :class="globalQuestionFilter == '1' ? 'active' : ''">
								<span class="sidebar-sublink-badge">SD</span>
								<span class="sidebar-sublink-label">Bank Soal SD</span>
							</a>
							<a :href="route('superadmin.global-questions.index', { jenjang_id: 2 })" class="sidebar-sublink" :class="globalQuestionFilter == '2' ? 'active' : ''">
								<span class="sidebar-sublink-badge">SMP</span>
								<span class="sidebar-sublink-label">Bank Soal SMP</span>
							</a>
							<a :href="route('superadmin.global-questions.index', { jenjang_id: 3 })" class="sidebar-sublink" :class="globalQuestionFilter == '3' ? 'active' : ''">
								<span class="sidebar-sublink-badge">SMA</span>
								<span class="sidebar-sublink-label">Bank Soal SMA</span>
							</a>
						</div>
					</details>
					<a :href="route('superadmin.paket-soal.index')" class="sidebar-link" :class="route().current('superadmin.paket-soal.*') || route().current('superadmin.soal.*') ? 'active' : ''">
						<i class="fa-solid fa-database w-5"></i>
						<span class="sidebar-link-label">Paket Soal TKA</span>
					</a>
					<a :href="route('superadmin.exams.index')" class="sidebar-link" :class="route().current('superadmin.exams.index') ? 'active' : ''">
						<i class="fa-solid fa-file-lines w-5"></i>
						<span class="sidebar-link-label">Manajemen Ujian</span>
					</a>
					<a :href="route('superadmin.landing-exams.index')" class="sidebar-link" :class="route().current('superadmin.landing-exams.*') ? 'active' : ''">
						<i class="fa-solid fa-store w-5"></i>
						<span class="sidebar-link-label">Ujian Publik Langsung</span>
					</a>

					<div class="sidebar-section-title">Sistem</div>
					<a :href="route('superadmin.audit-logs.index')" class="sidebar-link" :class="route().current('superadmin.audit-logs.index') ? 'active' : ''">
						<i class="fa-solid fa-shield-halved w-5"></i>
						<span class="sidebar-link-label">Log Aktivitas</span>
					</a>
					<a :href="route('superadmin.wa-koneksi')" class="sidebar-link" :class="route().current('superadmin.wa-koneksi') ? 'active' : ''">
						<i class="fa-solid fa-qrcode w-5"></i>
						<span class="sidebar-link-label">Koneksi WhatsApp</span>
					</a>
					<a :href="route('superadmin.wa-templates.index')" class="sidebar-link" :class="route().current('superadmin.wa-templates.*') ? 'active' : ''">
						<i class="fa-solid fa-message w-5"></i>
						<span class="sidebar-link-label">Pesan Otomatis</span>
					</a>
					<a :href="route('superadmin.wa-blast')" class="sidebar-link" :class="route().current('superadmin.wa-blast') || route().current('superadmin.wa-blast.*') ? 'active' : ''">
						<i class="fa-solid fa-bullhorn w-5"></i>
						<span class="sidebar-link-label">Blast Pengumuman</span>
					</a>
					<a :href="route('superadmin.guide')" class="sidebar-link" :class="route().current('superadmin.guide') ? 'active' : ''">
						<i class="fa-solid fa-circle-info w-5"></i>
						<span class="sidebar-link-label">Panduan</span>
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
					<footer class="page-footer">2026 Ujion TKA by Reditech</footer>
				</div>
			</main>
		</div>
	</div>
</template>
