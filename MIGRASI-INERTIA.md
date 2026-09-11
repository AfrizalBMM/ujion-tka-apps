# MIGRASI-INERTIA — Konvensi Wajib Konversi Blade → Vue (Inertia)

Dokumen ini adalah **panduan wajib** untuk semua konversi halaman Blade ke halaman Vue Inertia di project ini. Baca lengkap sebelum mulai.

## Arsitektur yang sudah berdiri

- Entry Inertia: `resources/js/app.js` (createInertiaApp, glob `./Pages/**/*.vue`).
- Root Blade: `resources/views/app.blade.php` — memuat CSS `resources/css/app.css` + JS `resources/js/app.js`, KaTeX CDN, `partials.ssd-style`.
- Layout Vue tersedia di `resources/js/Layouts/`:
  - `GuestLayout.vue` — props: `fullscreen`, `hideFooter`, `wide`, `hideShowcase` (boolean, default false).
  - `GuruLayout.vue`, `SuperadminLayout.vue`, `UjianLayout.vue` — tanpa props.
- Komponen UI di `resources/js/Components/Ui/`:
  - `FlashAlerts.vue` — notifikasi toast fixed di pojok kanan bawah (z-[70]): flash session + error validasi pertama. Countdown reaktif internal (pause saat hover, auto-close, tombol X, progress bar) — TIDAK bergantung pada JS legacy. Sudah di-include di semua layout (Guest, Guru, Superadmin) — JANGAN menambahkannya lagi per halaman.
  - `ConfirmModal.vue` — modal global di layout Guru/Superadmin. TIDAK perlu ditambahkan per halaman.
- Shared props dari `HandleInertiaRequests`: `auth.user`, `csrf_token`, `flash`, `status`, `routeName`, `guruLayout` (paymentLocked, waGroupLink, dokuConfig, notifLogs), `superadminLayout` (pendingPaymentCount).
- Ziggy terpasang: fungsi global `route(name, params)` dan `route().current('pattern.*')` tersedia di semua komponen Vue.

## Aturan konversi per halaman

1. **Lokasi file**: `resources/views/foo/bar.blade.php` → `resources/js/Pages/Foo/Bar.vue` (PascalCase setiap segmen). Contoh: `superadmin/paket-soal/index.blade.php` → `Pages/Superadmin/PaketSoal/Index.vue`; `guru/results/practice-show.blade.php` → `Pages/Guru/Results/PracticeShow.vue`.
2. **Controller**: ganti `return view('foo.bar', compact(...))` → `return Inertia::render('Foo/Bar', compact(...))`. Tambah `use Inertia\Inertia;` bila belum ada. **JANGAN ubah logic controller, validasi, redirect, atau policy** — hanya ganti cara render.
3. **Markup**: salin HTML + class Tailwind APA ADANYA dari Blade ke `<template>`. Jangan merapikan, mengganti class, atau mengubah struktur. Ini persyaratan PRD (layout tidak boleh bergeser).
4. **Layout**: gunakan layout sebagai wrapper component di template halaman:
   ```vue
   <GuestLayout fullscreen hide-footer hide-showcase>
     ...konten @section('content')...
   </GuestLayout>
   ```
   Flag layout dari `@php` di atas file Blade lama: `$fullscreenGuest` → `fullscreen`, `$hideFooterGuest` → `hide-footer`, `$hideShowcase` (di-set otomatis untuk path siswa*/materi*) → `hide-showcase`, `$guestWide` → `wide`.
   Halaman guru → `GuruLayout`, superadmin → `SuperadminLayout`, pengerjaan ujian → `UjianLayout`.
5. **Judul**: `@section('title', 'X')` → `<Head title="X" />` (import `Head` dari `@inertiajs/vue3`).
6. **Template directive**:
   - `{{ $var }}` → `{{ var }}` (var jadi props).
   - `{!! $html !!}` → `<div v-html="html"></div>` (perlu wrapper element).
   - `@if/@foreach/@forelse` → `v-if` / `v-for` / `v-if + v-for + v-else`.
   - `@php` block komputasi → `computed` atau `const` di `<script setup>`.
   - `route('x')` → `route('x')` (Ziggy, tetap sama).
   - `asset('x')` → `/x` atau `route('storage.local', {path})` bila storage.
   - `csrf_token()` di atribut → prop `csrfToken` dari `usePage().props.csrf_token` bila perlu.
   - `date('Y')` → `new Date().getFullYear()`.
   - `number_format($x, 1)` → `Number(x).toFixed(1)`; `number_format($x)` → `Number(x).toLocaleString('id-ID')`.
   - `Str::limit`, `->diffForHumans()`, dsb: hitung di controller sebagai prop tambahan, JANGAN replikasi kompleks di JS.
7. **Form**:
   - Form biasa (tanpa confirm-modal): pakai `useForm` dari `@inertiajs/vue3`, `@submit.prevent="submit"`, tombol `:disabled="form.processing"`. Nilai `old()` tidak perlu — useForm mempertahankan nilai.
   - Form yang tombol submitnya punya atribut `data-confirm` (confirm-modal native submit): **WAJIB tetap native form** — `<form method="POST" :action="route(...)">` + `<input type="hidden" name="_token" :value="page.props.csrf_token">`. ConfirmModal memanggil `form.submit()` native.
   - Error validasi: `form.errors.xxx` untuk halaman form; `usePage().props.errors` untuk halaman non-form yang menerima redirect withErrors.
8. **Navigasi**: link internal antar halaman app pakai `<Link :href="route('x')" class="...">` (import `Link` dari `@inertiajs/vue3`) AGAR tidak full reload. Link eksternal/file download/export PDF/CSV tetap `<a :href>`.
9. **Script halaman lama** (`resources/js/pages/*.js`):
   - Preferensi: port logikanya ke reactive Vue (`ref`, `computed`, `onMounted`) bila sederhana.
   - Bila script kompleks/DOM-heavy (drag-drop builder, chart, dsb): pertahankan markup identik, lalu jalankan logika vanilla di `onMounted` dengan `const rootEl = ref(null)` + `rootEl.value.querySelectorAll(...)`. Pastikan cleanup listener/interval di `onBeforeUnmount`.
   - Chart.js: dynamic `import('chart.js/auto')` di onMounted, simpan instance, `destroy()` di unmount.
   - KaTeX: konten statis dirender otomatis oleh init global. Untuk konten yang dirender ulang secara dinamis, panggil `window.UjionKaTeX?.render(el)` setelah update (di `nextTick`).
   - SSD (`.ssd-wrap` markup searchable select) diinisialisasi otomatis per navigasi — cukup salin markup, hidden input pakai `:value` + `@change` (bukan v-model).
10. **Props**: `defineProps` untuk semua variabel yang dikirim controller via compact/array. Model Eloquent yang di-pass akan ter-serialisasi otomatis (atribut publik). Method model (mis. `isSurvey()`, accessor `nama_label`) HARUS dihitung di controller jadi prop array eksplisit — tambahkan prop baru di controller bila Blade memanggil method.
11. **JANGAN**:
    - Menghapus/memodifikasi file Blade lama (cleanup dilakukan terpusat di akhir).
    - Mengubah `routes/*.php` kecuali mengganti closure `view()` menjadi `Inertia::render()` untuk halaman yang bersangkutan.
    - Menyentuh halaman publik SEO (landing, ujian-online, kisi-kisi, artikel, register-guru, payments, errors, PDF/print/export views di `superadmin/exports`).
    - Menambah komentar kode.
12. **Verifikasi**: setelah selesai batch, jalankan `php artisan route:list` (pastikan tidak ada error) dan `npm run build` (boleh gagal sementara karena modul lain setengah jalan — pastikan error BUKAN dari file yang Anda buat).

## Contoh pola lengkap

Lihat file acuan yang sudah jadi:
- `resources/js/Pages/Auth/Login.vue` — useForm + GuestLayout fullscreen.
- `resources/js/Pages/Auth/ForgotToken.vue` — SSD + useForm.
- `resources/js/Pages/Ujian/Pengerjaan.vue` — halaman reactive kompleks (timer, auto-save, grid navigasi).
- `app/Http/controllers/Siswa/ExamController.php` — pola Inertia::render + prop eksplisit.
