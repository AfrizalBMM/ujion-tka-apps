Product Requirement Document (PRD): Migrasi Ujion Tka Apps ke Laravel + Vue.js (Inertia.js)
1. Project Overview
Project Name: Ujion TKA Apps (ujion-tka-apps)

Objective: Melakukan migrasi total antarmuka (frontend) aplikasi dari Laravel Blade PHP murni ke Vue.js 3 dengan mempertahankan backend Laravel yang sudah ada.

Architecture Choice: Laravel + Inertia.js + Vue.js 3 + Tailwind CSS.

Reasoning: Inertia.js dipilih agar routing, middleware autentikasi (sesi siswa/guru), dan controller Laravel tetap digunakan tanpa perlu membangun REST API terpisah dari nol, sehingga proses migrasi aman untuk fitur sensitif seperti ujian online (timer, state jawaban, lembar jawaban).

2. Tech Stack Target
Backend: Laravel (Existing) + Inertia.js Laravel Adapter

Frontend: Vue.js 3 (Composition API / <script setup>), Inertia.js Vue 3 Adapter

Styling: Tailwind CSS (mempertahankan desain sistem yang sudah ada)

Build Tool: Vite

3. Scope of Work (Ruang Lingkup Migrasi)
A. Konfigurasi & Setup Awal
Install package Inertia.js di backend Laravel (inertiajs/inertia-laravel).

Install dependensi frontend (@inertiajs/vue3, vue, @vitejs/plugin-vue).

Konfigurasi file root layout Vue (app.blade.php) dan file entry point JavaScript (app.js).

Pastikan setup Vite (vite.config.js) mendukung plugin Vue dan Inertia.

B. Migrasi Halaman & Komponen Utama
AI Agent diminta untuk mengubah file .blade.php berikut menjadi file komponen .vue di dalam direktori resources/js/Pages/:

Autentikasi: Halaman Login Peserta/Guru & Sesi Handler.

Dashboard / Beranda: Panel utama peserta dan panel pengawas/guru.

Modul Ujian (Halaman Inti):

Tampilan soal (pilihan ganda / essay).

Panel Navigasi Nomor Soal (status: belum dijawab, sudah dijawab, ragu-ragu) dengan interaksi reaktif Vue.

Countdown Timer ujian berbasis reactive state di sisi klien.

Sistem auto-save atau pengiriman jawaban sementara via Inertia form helper (useForm).

Modul Evaluasi / Hasil: Lembar rekap nilai dan analisis hasil ujian.

4. Technical Guidelines untuk AI Agent
Pertahankan Desain & Layout: Jangan mengubah struktur class Tailwind CSS yang ada agar tampilan visual tidak bergeser atau rusak. Salin elemen HTML dari Blade ke dalam tag <template> komponen Vue.

Gunakan Composition API: Tulis seluruh script komponen Vue 3 menggunakan sintaks <script setup>.

Manfaatkan Inertia Props: Data yang sebelumnya dikirim via return view('name', compact('data')) di Controller Laravel harus diubah menjadi return Inertia::render('Name', ['data' => $data]).

Form Handling: Gunakan useForm dari @inertiajs/vue3 untuk menangani proses submit form (seperti login, navigasi soal, dan submit lembar jawaban akhir) guna menangani loading state dan validasi error secara reaktif.

5. Acceptance Criteria (Definisi Selesai)
[ ] Aplikasi dapat berjalan tanpa error melalui perintah php artisan serve dan npm run dev (Vite build sukses).

[ ] Seluruh halaman utama (Login, Dashboard, Ruang Ujian, Rekap Nilai) berhasil dirender oleh Vue melalui Inertia.js.

[ ] Navigasi nomor soal ujian dan timer berjalan secara reaktif di browser tanpa memicu full page reload.

[ ] Tidak ada kerusakan layout visual (styling Tailwind CSS tetap konsisten seperti versi Blade sebelumnya).