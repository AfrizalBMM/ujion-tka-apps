# QA-FINDINGS — Gate 1 Revisi Landing Copy (Ujion TKA)

- Task: `t_c49adb5f` (T3 QA gate1 landing copy revisi)
- Branch dites: `feature/landing-copy-revisi-ui` @ `6fc0c8d` (berisi backend `feature/landing-copy-revisi` @ `6b0b65d`, diverifikasi `git merge-base --is-ancestor` = true)
- Environment: PHPUnit 11, SQLite in-memory (`phpunit.xml`), worktree `.worktrees/t_c49adb5f`
- Test tambahan QA (belum di-commit, mengikuti instruksi task): `tests/Feature/QaLandingCopyGate1Test.php` — 16 test, 263 assertion (semua PASS; 2 test di antaranya sengaja mengunci temuan F-1 & F-2)
- Output full suite NYATA:

```
php artisan test        -> Tests: 1 skipped, 300 passed (1267 assertions), 0 failed
php vendor/bin/pint --test -> {"result":"passed"}   (seluruh repo)
grep -riE "gratis|white-label|freemium|\bCBT\b|aligned" resources/views resources/js database/seeders -> 0 hasil
grep -riE "Tempel PNG|Arah mockup|Simulasi mengelola|menjawaban" resources/views resources/js database/seeders -> 0 hasil
```

Baseline sebelum test QA ditambahkan: `284 passed / 1 skipped / 1004 assertions / 0 failed`.

## Verdict per item

| # | Item | Hasil | Bukti |
|---|---|---|---|
| 1 | `/` 200, DB kosong, fallback lengkap, tanpa string bocor | PASS | `test_item_1_home_200_empty_db_fallback_without_leaked_strings` (12 fallback string + 4 string bocor dicek di HTML asli, bukan props) |
| 2 | CRUD 7 section baru (bank_soal, footer, reasons_head, audience_head, steps_head, features_head, faq_head) | PASS | `test_item_2_singleton_sections_crud` (store+update+toggle per section, 7 section × 4 request; section tak dikenal → 404; judul 221 → error `title`) |
| 2b | Toggle section berdampak ke landing | PASS | `test_item_2b_section_toggle_changes_landing_output` — hero/stats/faq mati → hilang dari HTML; faq hidup lagi → + nav `#tanya-jawab` |
| 3 | CRUD daftar generik 6 section + urutkan + hapus | PASS | `test_item_3_generic_item_sections_crud_and_ordering` — store/update/toggle/destroy per section; validasi title/kicker/caption/body/section; urutan `sort_order` terbukti di HTML |
| 4 | Landing baca DB, fallback saat baris dihapus | PASS | `test_item_4_landing_reads_db_and_falls_back_when_deleted` — marker admin muncul di `/`; item & FAQ dihapus → default kembali; row `landing_contents` dihapus → kicker default kembali |
| 5 | Kartu jumlah soal = hitungan nyata `materials`+`questions`, 0 soal → "Segera hadir" | PASS | `test_item_5_*` — 3 materi (SD 2, SMA 1), 2 soal aktif + 1 nonaktif: view `stats` = SD materi 2 / soal 2 / coming_soon false, SMA materi 1 / soal 0 / coming_soon true, SMP coming_soon true; HTML `<td>2</td>` + 2 badge "Segera hadir"; soal nonaktif tidak bocor |
| 5b | Baris jenjang tabel statistik | CATATAN | `test_item_5b` — hanya `SD, SMP, SMA` (isi tabel `jenjangs` + `config('ujion.jenjangs')`). **SMK tidak ada barisnya** padahal copy bilang "200-800 untuk SMA dan SMK" → temuan F-3 |
| 6 | Tanpa "gratis/white-label/freemium/CBT/aligned" | PASS | `test_item_6*` — regex word-boundary di HTML `/` (DB kosong & setelah seeder), 0 match; grep source juga 0 |
| 7 | Skala skor + BSKAP + disclaimer | PASS | `test_item_7` — "0-100 untuk SD dan SMP", "200-800 untuk SMA dan SMK", "0-100 untuk SD/SMP", "200-800 untuk SMA/SMK", "Nomor 47 Tahun 2025", "Nomor 45 Tahun 2025", "bukan penyelenggara ujian TKA resmi", nama panjang BSKAP |
| 8 | Batas panjang form | PASS + catatan | `test_item_8` — judul 90 char diterima utuh (aturan controller `max:220`, TrimStrings hanya buang spasi ekor); 221 ditolak & nilai lama tidak berubah; item `body` 2001 ditolak; `seo_title` 121 / `seo_description` 301 ditolak. Catatan: **section singleton `body` boleh 5000** (bukan 2000) → temuan F-4 |
| 9 | Teks admin tampil sebagai teks biasa | PASS | `test_item_9_admin_text_rendered_escaped` — input `<script>window.__qa_xss=1</script>` (kicker hero/head + item) keluar sebagai `&lt;script&gt;`, tag asli tidak ada di HTML. **Kecuali JSON-LD FAQ → temuan F-1** |
| 10 | Label halaman masuk siswa = "kode ujian" | PASS label / FAIL pesan error | `test_item_10` label `<label …>Kode Ujian</label>` + helper text ✓, tidak ada kata `token` di landing ✓. Tapi pesan error server masih "Token …" → temuan F-2 |

## Temuan

### F-1 — P1 — Stored XSS via jawaban FAQ di JSON-LD landing (pre-existing, bukan regresi branch ini)
- Lokasi: `resources/views/landing.blade.php:103` (`{!! json_encode([...], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}` di dalam `<script type="application/ld+json">`), sumber data `landing_faqs.answer` (editor superadmin).
- Kenapa pecah: `json_encode` tanpa `JSON_HEX_TAG` + `JSON_UNESCAPED_SLASHES` membiarkan `</script>` apa adanya → penutup tag script browser.
- Reproduce: simpan FAQ answer `</script><script>window.__qa_jsonld=1</script>` → `GET /` → potongan HTML `…</script><script>window.__qa_jsonld=1</script>…` (diekspos oleh `test_finding_p1_faq_json_ld_allows_script_breakout`).
- Dampak: script dijalankan di browser SEMUA pengunjung landing (bukan cuma admin). Perlu akses superadmin untuk menanam.
- Status: sudah ada di `origin/develop` (FAQPage JSON-LD + `storeFaq` ada di develop) → pre-existing, tidak dibuat oleh branch ini, tapi layak di-fix di gate ini karena editor FAQ ada di UI revisi.
- Suggested fix (untuk Harmoko): tambah `JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT` pada 3 blok `json_encode` di `landing.blade.php`, atau render jawaban via `htmlspecialchars` sebelum masuk JSON.

### F-2 — P1 — Pesan error login siswa masih memakai kata "Token"
- Lokasi: `app/Http/Controllers/Siswa/AuthController.php:34` `"Token tidak valid, ujian tidak aktif, atau belum diterbitkan."`, `AuthController.php:41`, `ExamController.php:42` & `:407` `"…Masukkan token kembali."`, `:49`, `:154`.
- Reproduce: `POST /siswa/login` token salah → redirect ke `/siswa/login` → box error di `resources/js/Pages/Siswa/Login.vue:31-32` merender `{{ form.errors.token }}` → siswa baca "Token tidak valid…". Dibuktikan `test_finding_p2_siswa_login_error_copy_still_says_token`.
- Spec item 10 menyasar halaman masuk siswa → label sudah benar, pesan error belum.
- Suggested fix (Harmoko): ubah string jadi "Kode ujian tidak valid, ujian tidak aktif, atau belum diterbitkan." / "Masukkan kode ujian kembali."
- Terkait (di luar scope ketat, grade P2): `resources/js/Pages/Siswa/Practice/Login.vue:28` masih "Masukkan token latihan materi", `Practice/Dashboard.vue:85` label "Token", `:198` "salah token", `MaterialPracticeAuthController.php:35` & `MaterialPracticeController.php:36,42` masih "Token …".

### F-3 — P2 — Tabel bank soal tidak punya baris SMK, copy menyebut SMK
- Lokasi: `app/Http/Controllers/LandingController.php:200` (`jenjangStats`) membaca `jenjangs` (migrasi insert SD/SMP + SMA) fallback `config('ujion.jenjangs')` = `['SD','SMP','SMA']`; tidak ada `SMK`.
- Dampak: copy landing "200-800 untuk SMA dan SMK" (hero/footer/FAQ/feature) tidak diimbangi baris SMK di tabel "Jenjang / Materi / Soal". Pembaca mengira SMK tidak didukung.
- Reproduce: `test_item_5b_stats_jenjang_list_matches_jenjangs_table` mengunci `['SD','SMP','SMA']`.
- Suggested fix (Harmoko + acc): tambah row `SMK` ke `jenjangs` (migrasi insert, urutan 4) atau tulis ulang copy jadi "SMA/MA/SMK" hanya di tempat yang didukung. Keputusan ini butuh ACC → lapor Mukidi.

### F-4 — P2 — Validasi `body` tidak konsisten: item 2000, section singleton 5000
- Lokasi: `LandingSettingsController::validateItem` (`'body' => max:2000`) vs `saveContent` (`'body' => max:5000`).
- Brief QA menyebut "input >2000 body ditolak" → terpenuhi untuk daftar generik; `reasons_head`/`audience_head`/`bank_soal`/`footer` menerima sampai 5000.
- Dampak: teks section panjang lolos lalu dipotong tampilan? Blade tidak memotong → aman, hanya inkonsistensi aturan. Perlu konfirmasi aturan: 2000 atau 5000.

### F-5 — P2 — Statistik landing di-cache 10 menit tanpa invalidasi
- Lokasi: `LandingController::jenjangStats()` → `Cache::remember('landing.stats.jenjang.v1', now()->addMinutes(10))`.
- Reproduce: `test_stats_cache_note` — GET `/`, tambah 1 soal aktif SD, GET `/` lagi → `stats['SD']['soal']` masih 0.
- Dampak: guru yang baru menambah soal tidak melihat angka naik sampai 10 menit (atau sampai `php artisan cache:clear`). Tidak ada bust saat soal/materi dibuat/dihapus.

### F-6 — P3 — `updateItem` mengosongkan `kicker`/`body`/`caption` bila tidak dikirim
- Lokasi: `LandingSettingsController::updateItem` — `'kicker' => $validated['kicker'] ?? null`, `body`, `caption` sama; `saveContent` juga menulis `subtitle`/`cta_*`/`note` jadi null kalau tidak dikirim.
- Kenapa belum pecah: form Vue (`LandingSettings.vue:347`) tidak pernah mengirim `kicker`/`caption` untuk item, dan seeder tidak mengisinya; form hero tidak mengirim `subtitle`/`cta_*`. Jadi saat ini nilai awalnya memang null.
- Risiko: begitu ada jalur isi (impor/API/seeder baru), edit parsial diam-diam menghapus data. Layak dicatat untuk fix defensif (`array_key_exists` seperti `is_active`).

## Yang TIDAK ditemukan (negatif result, dicek betulan)
- Tidak ada string bocor naskah lama di HTML maupun source (`Tempel PNG`, `Arah mockup`, `Simulasi mengelola`, `menjawaban`).
- Tidak ada kata `gratis`/`white-label`/`freemium`/`CBT`/`aligned` di landing (HTML final + source blade/Vue/seeders).
- HTML admin terekskap dengan benar untuk field teks biasa (item 9 lolos).
- `route:cache`/`ziggy` tidak berpengaruh untuk test ini; `resources/js/ziggy.js` stale (4 route `landing-settings.items.*` belum ter-generate) dicatat T2 → tetap wajib regenerasi sebelum deploy, di luar scope test PHPUnit (tidak diverifikasi ulang di sini).

## Cara mengulang
```
git worktree add .worktrees/t_c49adb5f feature/landing-copy-revisi-ui
cd .worktrees/t_c49adb5f
cp <repo>/tests/Feature/QaLandingCopyGate1Test.php tests/Feature/
# vendor harus copy sungguhan (jangan symlink) -> Application::inferBasePath() ikut symlink vendor
php artisan test
php vendor/bin/pint --test
```
