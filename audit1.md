Laporan Audit Menyeluruh — Ujion TKA Apps
Tanggal Audit: 25 April 2026 Scope: Seluruh layer aplikasi — Routes, Controllers, Models, Middleware, Views, JS, dan Konfigurasi

Ringkasan Eksekutif
Kategori Jumlah Temuan
🔴 Kritis (Keamanan/Data loss) 7
🟠 Tinggi (Bug / Logic Error) 11
🟡 Sedang (Kelemahan Desain) 9
🟢 Rendah / Saran Peningkatan 8
Total 35
🔴 KRITIS — Keamanan & Data Loss
[K-01] APP_DEBUG=true di Environment Lokal — Risiko Saat Deployment
File: .env Baris: APP_DEBUG=true

Masalah: Jika file .env ini di-deploy ke produksi tanpa diubah, APP_DEBUG=true akan menampilkan stack trace, nama file, variabel, dan konfigurasi database secara langsung di browser saat terjadi error. Ini adalah kebocoran informasi paling berbahaya.

Rekomendasi:

APP_DEBUG=false # di production
APP_ENV=production
[K-02] APP_KEY Tersimpan di Repository — Potensi Session Hijacking
File: .env (yang ada di .git) Nilai: APP_KEY=base64:kP0O1upvoXjGwkz9c0wz4cuTlrJNPkVGtoadolMSKyI=

Masalah: .env tidak ada di .gitignore berdasarkan fakta bahwa file ini bisa dibaca dan di-commit. Jika .env masuk ke repo publik/push ke GitHub/GitLab, APP_KEY, DB_PASSWORD, GOPAY_MASTER_PAYLOAD, dan QRIS_ADMIN_WHATSAPP bisa terbaca oleh siapapun. APP_KEY dipakai untuk enkripsi session — siapapun yang memilikinya bisa memalsukan cookie session dan login sebagai siapapun.

Rekomendasi:

Verifikasi .gitignore mengandung .env
Rotate APP_KEY segera: php artisan key:generate
Gunakan .env.example untuk template, jangan masukkan nilai nyata

[K-03] Siswa/Token Auth Tanpa Rate Limiting — Brute Force
File: routes/web.php, app/Http/Controllers/Siswa/AuthController.php Baris: Route::post('/siswa/login', ...)

Masalah: Endpoint login siswa (/siswa/login) tidak dilindungi rate limiting apapun. Karena token ujian hanya 6–10 karakter (min:6|max:10), ruang kemungkinannya kecil dan dapat di-brute force untuk menebak token ujian aktif. Tidak ada middleware throttle: atau captcha.

Rekomendasi:

php
Route::post('/siswa/login', [SiswaAuthController::class, 'validateToken'])
->name('siswa.token.validate')
->middleware('throttle:10,1'); // maks 10 percobaan per menit

[K-04] Endpoint Check Email & WA — Enumeration Attack
File: app/Http/Controllers/RegisterGuruController.php Method: checkEmail(), checkWa()

Masalah: API publik (tidak memerlukan autentikasi) yang merespons dengan jelas apakah email atau nomor WA sudah terdaftar:

json
{"exists": true, "message": "Email ini sudah terdaftar..."}
Ini memungkinkan attacker melakukan user enumeration — memverifikasi daftar email/nomor WA untuk mengetahui akun mana yang terdaftar di sistem, kemudian digunakan untuk social engineering atau spam.

Rekomendasi:

php
// Tambahkan throttle
Route::get('/register/guru/check-email', ...)->middleware('throttle:30,1');
Route::get('/register/guru/check-wa', ...)->middleware('throttle:30,1');
// Atau ubah respons menjadi ambigu lebih lanjut

[K-05] destroyAll Bank Soal Global — Tidak Ada Konfirmasi Server-side
File: app/Http/Controllers/Superadmin/GlobalQuestionController.php Method: destroyAll()

Masalah:

php
public function destroyAll(): RedirectResponse
{
$count = GlobalQuestion::count();
GlobalQuestion::query()->delete(); // Hapus SEMUA tanpa filter
...
}
Tidak ada validasi tambahan (misalnya konfirmasi password, atau token CSRF yang lebih ketat). Satu POST request cukup untuk menghapus seluruh bank soal. Ini juga bisa terjadi akibat CSRF jika proteksi lemah, atau kesalahan admin.

Rekomendasi:

Tambahkan soft delete agar bisa di-recover
Atau minta konfirmasi teks (misal: ketik nama database) sebelum menghapus massal
Tambahkan audit log dengan snapshot jumlah data

[K-06] saveBuilder Guru — Path Traversal Tidak Dicegah
File: app/Http/Controllers/Guru/PersonalQuestionController.php Method: saveBuilder()

Masalah:

php
$imagePath = $q['image_path'] ?? ($q['image'] ?? null);
// ...
$q['image_path'] = $imagePath; // langsung simpan ke DB
Walaupun ada pengecekan untuk URL external:

php
if (str_contains($candidate, '://') || str_starts_with($candidate, '/')) {
return null;
}
Nilai image_path dari payload JSON bisa berisi path relatif seperti ../../config/database.php. Path ini disimpan langsung ke database dan bisa digunakan untuk membocorkan file jika kemudian dibaca via Storage::disk('public')->path($path).

Rekomendasi:

php
// Validasi bahwa path hanya berisi karakter yang diharapkan dan ada di direktori yang benar
abort_if(!str_starts_with($imagePath, 'personal-question-images/'), 422);
abort_unless(Storage::disk('public')->exists($imagePath), 422);

[K-07] Guru ExamController — Session Linking via no_wa — Impersonation Risk
File: app/Http/Controllers/Guru/ExamController.php Method: sessionQueryForUser(), join()

Masalah:

php
private function sessionQueryForUser($user)
{
return UjianSesi::query()->where('nomor_wa', $user->no_wa);
}
Sesi ujian diidentifikasi berdasarkan no_wa, bukan user_id. Jika dua guru memiliki nomor WA yang sama (duplikat karena normalisasi berbeda), atau jika nomor WA guru berubah, mereka akan melihat/mengakses hasil ujian guru lain. Selain itu, siswa yang kebetulan memasukkan nomor WA sama dengan guru saat mulai() di siswa controller, bisa mengkontaminasi data.

Rekomendasi: Gunakan user_id untuk linking session guru, bukan no_wa.

🟠 TINGGI — Bug & Logic Error
[B-01] Route Conflict — personal-questions/builder vs personal-questions/{question}
File: routes/guru.php Baris: 39–44

Masalah:

php
Route::post('/personal-questions/{question}', ...)->name('personal-questions.update'); // L39
Route::get('/personal-questions/builder', ...)->name('personal-questions.builder'); // L41
Karena route {question} (parameter) terdaftar sebelum route builder (string literal), Laravel akan mencocokkan /personal-questions/builder sebagai request update dengan $question = 'builder'. Ini menghasilkan model binding failure (404 atau error karena tidak ada PersonalQuestion dengan ID "builder").

Dampak: Halaman builder bisa tidak bisa diakses via POST.

Rekomendasi: Pindahkan semua route spesifik (literal string) di atas route dengan parameter:

php
// BENAR: literal dulu
Route::get('/personal-questions/builder', ...);
Route::post('/personal-questions/builder/save', ...);
// BARU: parameter
Route::post('/personal-questions/{question}', ...);

[B-02] register() — Race Condition pada Duplikat Registrasi
File: app/Http/Controllers/RegisterGuruController.php Method: register()

Masalah:

php
$existingByEmail = User::query()->where('email', $validated['email'])->first();
$existingByWa = User::query()->where('no_wa', $normalizedWa)->first();
// ... validasi ...
$user = User::create([...]);
Antara pengecekan dan pembuatan user, dua request simultan (race condition) bisa melewati validasi duplicate dan membuat dua akun dengan email/WA yang sama. Tidak ada unique constraint yang dicek pada level database melalui validasi Laravel.

Rekomendasi:

php
'email' => ['required', 'email', 'max:255', 'unique:users,email'],
'no_wa' => ['required', 'string', 'max:20', 'unique:users,no_wa'],
Dan pastikan ada unique index di migration database.

[B-03] approve() Payment — Tidak Ada Cek Apakah User Milik Superadmin yang Sama
File: app/Http/Controllers/Superadmin/PaymentConfirmationController.php Method: approve(Transaction $transaction)

Masalah: Tidak ada validasi bahwa $transaction->user tidak null sebelum akses:

php
$teacher = $transaction->user;
$token = $teacher->access_token ?: $this->generateUniqueToken(); // ERROR jika user dihapus
Jika user guru sudah dihapus dari database tetapi transaksinya masih ada (tidak ada cascade), ini akan throw null method call error dan expose stack trace.

Rekomendasi:

php
$teacher = $transaction->user;
if (!$teacher) {
return back()->with('flash', ['type' => 'warning', 'message' => 'Akun guru tidak ditemukan.']);
}

[B-04] saveBuilder — Delete All Then Re-create Tanpa Rollback Proper
File: app/Http/Controllers/Guru/PersonalQuestionController.php Method: saveBuilder()

Masalah:

php
DB::transaction(function () use ($user, $data): void {
    $existingQuery->delete(); // Hapus semua soal lama
    foreach ($data['questions'] as $q) {
        PersonalQuestion::create($q); // Buat ulang
}
});
Ini adalah pola "delete-all-then-recreate". Jika ada satu soal yang gagal di-create (misalnya data tidak valid yang lolos validasi), semua soal lama sudah terhapus dan hanya sebagian soal baru yang ada. Walaupun ada DB::transaction, jika exception bukan database-related tapi PHP-exception, rollback mungkin tidak terjadi dengan bersih.

Rekomendasi: Gunakan pola upsert (update yang ada, insert yang baru, hapus yang tidak ada lagi) daripada delete-all.

[B-05] ExamController::saveBuilder — Tipe Soal Tidak Divalidasi
File: app/Http/Controllers/Superadmin/ExamController.php Method: saveBuilder()

Masalah:

php
'questions.\*.tipe' => 'required', // Tidak ada 'in:PG,Checklist,Singkat'
Nilai tipe bisa berupa string apapun yang dikirim dari frontend. Ini bisa menyebabkan data kotor di database.

Rekomendasi:

php
'questions.\*.tipe' => 'required|in:PG,Checklist,Singkat,multiple_choice,matching',

[B-06] Guru Login — Identifikasi via name yang Tidak Unik
File: app/Http/Controllers/AuthController.php Method: login()

Masalah:

php
$user = User::where(function ($query) use ($request) {
$query->where('name', $request->login_identifier)
->orWhere('no_wa', $request->login_identifier);
})
->where('access_token', $request->access_token)
...->first();
Login menggunakan nama yang tidak unik. Dua guru bernama "Siti Rahayu" yang kebetulan token-nya sama (walau sangat kecil kemungkinannya) bisa login ke akun yang salah. Nama juga bisa typo. ->first() mengambil yang pertama ditemukan tanpa kepastian.

Rekomendasi: Gunakan no_wa (yang lebih unik) sebagai login identifier utama.

[B-07] Siswa ExamController — Timer Tidak Dipaksa Server-Side
File: app/Http/Controllers/Siswa/ExamController.php Method: apiSaveAnswer()

Masalah:

php
if (isset($validated['remaining_seconds'])) {
    $timerState[$validated['mapel_paket_id']]['remaining_seconds'] = $validated['remaining_seconds'];
// Timer berasal dari CLIENT dan langsung dipercaya
}
remaining_seconds berasal dari klien dan langsung disimpan ke database tanpa validasi server-side apapun. Siswa bisa mengirim remaining_seconds: 999999 untuk memanjangkan waktu ujian, atau mengubah waktu timer sesuka hati.

Rekomendasi: Kalkulasi waktu tersisa di server berdasarkan started_at yang tersimpan, jangan percaya data timer dari klien.

[B-08] resumePending — Pencocokan Nama yang Terlalu Longgar
File: app/Http/Controllers/RegisterGuruController.php Method: pendingResumeNameMatches()

Masalah:

php
return $stored === $input
    || str_contains($stored, $input)
    || str_contains($input, $stored);
Matching menggunakan str_contains yang sangat longgar. Input nama sepanjang 3 karakter (syarat minimum mb_strlen($input) < 3 hanya filter < 3) sudah bisa cocok. Misalnya nama "Ana" akan cocok dengan semua guru yang namanya mengandung "ana" seperti "Renata", "Anastasia", dll. Ini bisa disalahgunakan untuk mendapatkan akses ke session guru lain.

Rekomendasi: Gunakan similarity threshold yang lebih ketat, atau minimal-maximum length matching yang lebih strict.

[B-09] ChatImageController — Akses Gambar Tidak Dicek Kepemilikan
File: app/Http/Controllers/ChatImageController.php

Masalah (perlu verifikasi): Route GET /chat/{chat}/image diakses oleh guru dan superadmin. Jika tidak ada validasi bahwa $chat milik user yang sedang login, seorang guru bisa mengakses gambar chat orang lain dengan menebak chat ID.

Rekomendasi: Pastikan ada authorization check:

php
abort_unless($chat->from_user_id === auth()->id() || auth()->user()->isSuperadmin(), 403);

[B-10] importBankQuestions — Tidak Ada Cek Duplikat Soal
File: app/Http/Controllers/Superadmin/ExamController.php Method: importBankQuestions()

Masalah: Saat mengimport soal dari bank ke ujian, tidak ada pengecekan apakah soal dari global_question_id yang sama sudah pernah diimport sebelumnya ke ujian yang sama. Ini menyebabkan duplikat soal di ujian jika admin mengklik import dua kali.

Rekomendasi: Cek apakah kombinasi exam_id + source_global_question_id sudah ada sebelum insert.

[B-11] EnsureGuruAccountIsActive — Tidak Menangani Superadmin yang Login sebagai Guru
File: app/Http/Middleware/EnsureGuruAccountIsActive.php

Masalah (perlu verifikasi): Middleware guru.active mungkin memblokir superadmin jika mereka mencoba mengakses route guru (misalnya untuk testing). Perlu dipastikan ada bypass untuk superadmin.

🟡 SEDANG — Kelemahan Desain
[D-01] Tidak Ada Pagination di Beberapa Query Besar
File: GlobalQuestionController::index()

Masalah:

php
$globalQuestions = GlobalQuestion::with('material')
...->get(); // Ambil SEMUA data sekaligus
Dengan ribuan soal di bank, ini akan menggunakan memory besar dan memperlambat halaman. Tidak ada pagination.

Rekomendasi:
ADA DROPDOWN PILIHAN PAGINATION ( 10, 20, 30, 50)
php
->paginate(10)->withQueryString();

[D-02] Redundansi Dua Entitas Review Pembayaran
File: TeacherController.php dan PaymentConfirmationController.php

Masalah: Ada dua tempat untuk approve/reject pembayaran:

TeacherController::approvePayment() — dari halaman Daftar Guru
PaymentConfirmationController::approve() — dari halaman Konfirmasi Pembayaran
Keduanya melakukan hal yang sama tapi dengan logika sedikit berbeda. Ini bisa menyebabkan inkonsistensi:

TeacherController::approvePayment() tidak mengupdate Transaction record sama sekali (hanya User)
PaymentConfirmationController::approve() mengupdate keduanya
Rekomendasi: Ekstrak ke satu Service class (PaymentApprovalService) yang dipakai keduanya.

[D-03] access_token Tersimpan Plain-text di Database
File: app/Models/User.php, migration

Masalah: Token akses guru disimpan sebagai plain text di kolom access_token. Jika database bocor, semua token guru langsung bisa digunakan untuk login tanpa cracking.

Rekomendasi: Simpan hash dari token: Hash::make($token) saat disimpan, verifikasi dengan Hash::check() saat login. Atau gunakan Laravel Sanctum.

[D-04] SESSION_ENCRYPT=false — Session Data Tidak Dienkripsi
File: .env Baris: SESSION_ENCRYPT=false

Masalah: Session menyimpan data sensitif seperti pending_registration (berisi teacher_id), participant_token, dan siswa_exam_id. Dengan enkripsi session dinonaktifkan, data ini bisa dibaca dari cookie jika seseorang mendapatkan akses ke storage session.

Rekomendasi:

SESSION_ENCRYPT=true
[D-05] Tidak Ada HTTPS Enforcement
File: .env, config/

Masalah: Tidak ada konfigurasi untuk memaksa HTTPS (FORCE_HTTPS atau APP_URL=https://...). Di production, semua data termasuk token, password, dan bukti pembayaran akan dikirim via HTTP plain text jika tidak dikonfigurasi di web server.

Rekomendasi:

php
// app/Providers/AppServiceProvider.php
if (app()->environment('production')) {
URL::forceScheme('https');
}

[D-06] AuditRequest — Schema Check di Setiap Request
File: app/Http/Middleware/AuditRequest.php Baris: 21

Masalah:

php
if (! Schema::hasTable('audit_logs')) {
return $response;
}
Schema::hasTable() melakukan query database setiap kali ada request. Ini berarti setiap POST request ke superadmin melakukan satu query tambahan yang tidak perlu setelah migration selesai.

Rekomendasi: Cache hasil pengecekan schema atau gunakan config flag:

php
if (!config('ujion.audit_enabled', true)) return $response;

[D-07] generateReferenceCode — Potential Infinite Loop
File: app/Http/Controllers/RegisterGuruController.php Method: generateReferenceCode()

Masalah:

php
do {
$candidate = 'UJN-' . now()->format('ymd') . '-' . strtoupper(Str::random(8));
} while (Transaction::query()->where('reference_code', $candidate)->exists());
Loop ini tidak memiliki batas iterasi. Secara teoritis (sangat jarang tapi mungkin), jika semua kemungkinan kode untuk hari itu sudah terpakai, loop akan berjalan selamanya dan menyebabkan request timeout atau memory exhaustion.

Rekomendasi:

php
for ($attempts = 0; $attempts < 20; $attempts++) {
$candidate = ...;
if (!Transaction::query()->where('reference_code', $candidate)->exists()) {
return $candidate;
}
}
abort(500, 'Gagal generate kode referensi unik.');

[D-08] Duplikasi generateUniqueToken di Dua Controller
File: TeacherController.php (L212), PaymentConfirmationController.php (L136)

Masalah: Fungsi identik diduplikasi di dua controller. Perubahan di satu tidak otomatis direfleksikan di yang lain.

Rekomendasi: Ekstrak ke Trait atau Service:

php
// app/Support/TokenGenerator.php
class TokenGenerator {
public static function uniqueTeacherToken(): string { ... }
}

[D-09] Logika Scoring Menjodohkan — Asumsi Salah
File: app/Http/Controllers/Siswa/ExamController.php Method: calculateScore()

Masalah:

php
$allCorrect = $soal->pasanganMenjodohkans->every(function ($pair) use ($answers) {
    return (int) $answers->get($pair->id) === (int) $pair->id; // match_id harus === pair->id?
});
Logika ini mengasumsikan bahwa jawaban benar untuk soal menjodohkan adalah match_id === pair->id. Artinya setiap pasangan harus "dijodohkan dengan dirinya sendiri". Ini tidak konsisten dengan desain PasanganMenjodohkan yang memiliki teks_kiri dan teks_kanan sebagai pasangan berbeda. Ini kemungkinan bug scoring yang membuat semua jawaban menjodohkan selalu salah, atau logika matching tidak sesuai cara kerja sebenarnya.

🟢 RENDAH / Saran Peningkatan
[S-01] Tidak Ada Rate Limiting di Login Guru
Route POST /login (guru) tidak memiliki throttle. Perlu ditambahkan:

php
Route::post('/login', ...)->middleware('throttle:5,1');
[S-02] .env Berisi GOPAY Payload Sensitif
GOPAY_MASTER_PAYLOAD di .env adalah QRIS payload produksi yang berisi nomor merchant. Ini sebaiknya dipisahkan ke konfigurasi terproteksi atau environment variable di hosting, bukan di file .env yang mungkin di-commit.

[S-03] access_token Tidak Ada di $hidden di User Model
File: app/Models/User.php

access_token tidak ada di $hidden array. Jika User model di-serialize ke JSON (misalnya di API response atau log), token ini akan ikut terekspos.

Rekomendasi:

php
protected $hidden = [
'password',
'remember_token',
'access_token', // Tambahkan ini
];

[S-04] Tidak Ada MIMES Restriction yang Ketat untuk Import Excel
File: GlobalQuestionController::importPG()

php
'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
txt memungkinkan upload file teks apapun termasuk PHP script (walaupun tidak akan dieksekusi karena disimpan di storage). Pertimbangkan membatasi hanya csv,xlsx,xls.

[S-05] Email Verifikasi Tidak Diimplementasikan
MustVerifyEmail dikomentari di User model:

php
// use Illuminate\Contracts\Auth\MustVerifyEmail;
Akun guru bisa dibuat dengan email palsu. Tidak ada verifikasi email sama sekali.

[S-06] Soal Builder Exam — Tidak Ada Pagination Soal
Halaman exam-builder memuat semua soal dari bank soal global ke dalam satu <select multiple> tanpa pagination. Jika bank soal besar (ribuan soal), ini akan membuat halaman sangat lambat.

[S-07] storage:link — Tidak Ada di Dokumentasi Deployment
Bukti pembayaran dan gambar soal disimpan di storage/app/public dan diakses via Storage::url(). Tanpa menjalankan php artisan storage:link, semua gambar akan return 404. Ini kritis untuk deployment tapi tidak terdokumentasi.

[S-08] Tidak Ada Policy / Authorization untuk Beberapa Aksi Sensitif
Beberapa aksi sensitif seperti hapus semua soal global (destroyAll) dan import bank soal hanya dilindungi oleh middleware role, tanpa Laravel Policy yang lebih granular. Ini membuat semua superadmin memiliki akses sama ke semua aksi destruktif.

Prioritas Perbaikan
Prioritas Item Effort
Segera [K-01] Debug=false di production 1 menit
Segera [K-02] Audit .gitignore, rotate APP_KEY 10 menit
Segera [K-03] Tambah rate limiting login siswa 5 menit
Tinggi [B-01] Fix route conflict builder guru 5 menit
Tinggi [B-02] Tambah unique validation registrasi 10 menit
Tinggi [B-07] Fix timer server-side ujian siswa 1-2 jam
Tinggi [D-03] Hash access_token 2-3 jam
Tinggi [D-09] Fix scoring menjodohkan 1 jam
Sedang [K-04] Rate limit check email/WA 5 menit
Sedang [D-01] Pagination bank soal global 30 menit
Sedang [D-02] Satukan logika approve payment 1 jam
Sedang [S-03] Sembunyikan access_token dari serialisasi 5 menit
Laporan ini dibuat berdasarkan pembacaan static code. Testing dinamis dan penetration testing lebih lanjut direkomendasikan untuk memastikan semua temuan dan menemukan kerentanan tambahan.
