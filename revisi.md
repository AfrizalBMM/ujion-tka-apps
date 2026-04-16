# Product Requirements Document (PRD)
## Revisi Sistem Ujian — Paket Soal per Jenjang

**Versi:** 1.0  
**Tanggal:** 16 April 2026  
**Status:** Draft  

---

## 1. Latar Belakang

Sistem ujian yang ada saat ini perlu direvisi secara menyeluruh untuk mendukung struktur **paket soal per jenjang** yang terdiri dari dua mata pelajaran (Matematika dan Bahasa Indonesia), masing-masing berisi 30 soal dengan durasi 75 menit. Selain itu, tipe soal diperluas dengan menambahkan soal **menjodohkan**, **teks bacaan**, **indikator**, dan dukungan **gambar opsional** pada setiap butir soal.

---

## 2. Tujuan

- Menyesuaikan struktur data agar mendukung paket soal yang terikat pada jenjang.
- Mendukung dua mata pelajaran dalam satu paket ujian.
- Memperluas tipe soal: pilihan ganda & menjodohkan.
- Memperbarui antarmuka siswa, guru, dan superadmin secara konsisten.
- Menyediakan navigasi soal yang intuitif dan timer per mata pelajaran.

---

## 3. Ruang Lingkup

### Dalam Ruang Lingkup
- Migrasi database (tabel baru & modifikasi)
- Model Eloquent (relasi, cast, scope)
- Controller (Superadmin & Guru)
- Blade views (Superadmin & Guru)
- Blade views (Siswa — halaman ujian)
- Navigasi soal & timer ujian

### Di Luar Ruang Lingkup
- Sistem autentikasi & manajemen user
- Laporan nilai / rekap hasil ujian (fase berikutnya)
- Integrasi dengan sistem eksternal

---

## 4. Struktur Data & Migrasi

### 4.1 Gambaran Relasi

```
Jenjang
  └── PaketSoal (1 paket per jenjang, berisi 2 mapel)
        └── MataPelajaran (Matematika | Bahasa Indonesia)
              └── Soal (30 soal per mapel)
                    ├── TipeSoal: pilihan_ganda | menjodohkan
                    ├── TeksBacaan (opsional, bisa digunakan oleh beberapa soal)
                    ├── Indikator (teks indikator per soal)
                    ├── Gambar (opsional)
                    ├── PilihanJawaban (A–D untuk pilihan ganda)
                    └── PasanganMenjodohkan (kiri–kanan untuk menjodohkan)
```

---

### 4.2 Detail Migrasi

#### Tabel: `paket_soals`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigIncrements | PK |
| `jenjang_id` | unsignedBigInteger | FK ke tabel `jenjangs` |
| `nama` | string | Nama paket soal |
| `tahun_ajaran` | string | Contoh: 2025/2026 |
| `is_active` | boolean | default false |
| `created_by` | unsignedBigInteger | FK ke `users` (guru/admin) |
| `timestamps` | | |

```php
// Migration: create_paket_soals_table
Schema::create('paket_soals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('jenjang_id')->constrained('jenjangs')->cascadeOnDelete();
    $table->string('nama');
    $table->string('tahun_ajaran');
    $table->boolean('is_active')->default(false);
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
});
```

---

#### Tabel: `mapel_pakets` (Mata Pelajaran dalam Paket)

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigIncrements | PK |
| `paket_soal_id` | unsignedBigInteger | FK ke `paket_soals` |
| `nama_mapel` | enum | `matematika`, `bahasa_indonesia` |
| `jumlah_soal` | unsignedInteger | default 30 |
| `durasi_menit` | unsignedInteger | default 75 |
| `urutan` | unsignedInteger | urutan pengerjaan |
| `timestamps` | | |

```php
// Migration: create_mapel_pakets_table
Schema::create('mapel_pakets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('paket_soal_id')->constrained('paket_soals')->cascadeOnDelete();
    $table->enum('nama_mapel', ['matematika', 'bahasa_indonesia']);
    $table->unsignedInteger('jumlah_soal')->default(30);
    $table->unsignedInteger('durasi_menit')->default(75);
    $table->unsignedInteger('urutan')->default(1);
    $table->timestamps();

    $table->unique(['paket_soal_id', 'nama_mapel']);
});
```

---

#### Tabel: `teks_bacaans`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigIncrements | PK |
| `mapel_paket_id` | unsignedBigInteger | FK |
| `judul` | string nullable | Judul teks bacaan |
| `konten` | longText | Isi teks bacaan |
| `timestamps` | | |

```php
Schema::create('teks_bacaans', function (Blueprint $table) {
    $table->id();
    $table->foreignId('mapel_paket_id')->constrained('mapel_pakets')->cascadeOnDelete();
    $table->string('judul')->nullable();
    $table->longText('konten');
    $table->timestamps();
});
```

---

#### Tabel: `soals`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigIncrements | PK |
| `mapel_paket_id` | unsignedBigInteger | FK |
| `teks_bacaan_id` | unsignedBigInteger nullable | FK (opsional) |
| `nomor_soal` | unsignedInteger | 1–30 |
| `tipe_soal` | enum | `pilihan_ganda`, `menjodohkan` |
| `indikator` | text | Teks indikator soal |
| `pertanyaan` | text | Isi pertanyaan |
| `gambar` | string nullable | Path gambar |
| `bobot` | unsignedInteger | default 1 |
| `timestamps` | | |

```php
Schema::create('soals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('mapel_paket_id')->constrained('mapel_pakets')->cascadeOnDelete();
    $table->foreignId('teks_bacaan_id')->nullable()->constrained('teks_bacaans')->nullOnDelete();
    $table->unsignedInteger('nomor_soal');
    $table->enum('tipe_soal', ['pilihan_ganda', 'menjodohkan']);
    $table->text('indikator');
    $table->text('pertanyaan');
    $table->string('gambar')->nullable();
    $table->unsignedInteger('bobot')->default(1);
    $table->timestamps();

    $table->unique(['mapel_paket_id', 'nomor_soal']);
});
```

---

#### Tabel: `pilihan_jawabans`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigIncrements | PK |
| `soal_id` | unsignedBigInteger | FK |
| `kode` | enum | `A`, `B`, `C`, `D` |
| `teks` | text | Isi pilihan |
| `gambar` | string nullable | Gambar pada pilihan (opsional) |
| `is_benar` | boolean | Jawaban benar |
| `timestamps` | | |

```php
Schema::create('pilihan_jawabans', function (Blueprint $table) {
    $table->id();
    $table->foreignId('soal_id')->constrained('soals')->cascadeOnDelete();
    $table->enum('kode', ['A', 'B', 'C', 'D']);
    $table->text('teks');
    $table->string('gambar')->nullable();
    $table->boolean('is_benar')->default(false);
    $table->timestamps();

    $table->unique(['soal_id', 'kode']);
});
```

---

#### Tabel: `pasangan_menjodohkans`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigIncrements | PK |
| `soal_id` | unsignedBigInteger | FK |
| `teks_kiri` | text | Kolom kiri (pernyataan) |
| `teks_kanan` | text | Kolom kanan (jawaban) |
| `urutan` | unsignedInteger | urutan tampil |
| `timestamps` | | |

```php
Schema::create('pasangan_menjodohkans', function (Blueprint $table) {
    $table->id();
    $table->foreignId('soal_id')->constrained('soals')->cascadeOnDelete();
    $table->text('teks_kiri');
    $table->text('teks_kanan');
    $table->unsignedInteger('urutan')->default(1);
    $table->timestamps();
});
```

---

#### Tabel: `jawaban_siswas`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigIncrements | PK |
| `ujian_sesi_id` | unsignedBigInteger | FK ke sesi ujian siswa |
| `soal_id` | unsignedBigInteger | FK |
| `tipe_soal` | enum | `pilihan_ganda`, `menjodohkan` |
| `jawaban_pg` | string nullable | Kode A/B/C/D |
| `jawaban_menjodohkan` | json nullable | `[{"kiri_id": 1, "kanan_id": 3}, ...]` |
| `is_ragu` | boolean | Flag ragu-ragu |
| `timestamps` | | |

---

## 5. Model Eloquent

### 5.1 `PaketSoal`

```php
class PaketSoal extends Model
{
    protected $fillable = ['jenjang_id', 'nama', 'tahun_ajaran', 'is_active', 'created_by'];

    protected $casts = ['is_active' => 'boolean'];

    public function jenjang(): BelongsTo
    {
        return $this->belongsTo(Jenjang::class);
    }

    public function mapelPakets(): HasMany
    {
        return $this->hasMany(MapelPaket::class)->orderBy('urutan');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scope: hanya paket aktif
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
```

---

### 5.2 `MapelPaket`

```php
class MapelPaket extends Model
{
    protected $fillable = ['paket_soal_id', 'nama_mapel', 'jumlah_soal', 'durasi_menit', 'urutan'];

    public function paketSoal(): BelongsTo
    {
        return $this->belongsTo(PaketSoal::class);
    }

    public function soals(): HasMany
    {
        return $this->hasMany(Soal::class)->orderBy('nomor_soal');
    }

    public function teksBacaans(): HasMany
    {
        return $this->hasMany(TeksBacaan::class);
    }

    // Label tampil
    public function getNamaLabelAttribute(): string
    {
        return match($this->nama_mapel) {
            'matematika'       => 'Matematika',
            'bahasa_indonesia' => 'Bahasa Indonesia',
            default            => ucfirst($this->nama_mapel),
        };
    }
}
```

---

### 5.3 `Soal`

```php
class Soal extends Model
{
    protected $fillable = [
        'mapel_paket_id', 'teks_bacaan_id', 'nomor_soal',
        'tipe_soal', 'indikator', 'pertanyaan', 'gambar', 'bobot'
    ];

    public function mapelPaket(): BelongsTo
    {
        return $this->belongsTo(MapelPaket::class);
    }

    public function teksBacaan(): BelongsTo
    {
        return $this->belongsTo(TeksBacaan::class);
    }

    public function pilihanJawabans(): HasMany
    {
        return $this->hasMany(PilihanJawaban::class)->orderBy('kode');
    }

    public function pasanganMenjodohkans(): HasMany
    {
        return $this->hasMany(PasanganMenjodohkan::class)->orderBy('urutan');
    }

    public function isPilihanGanda(): bool
    {
        return $this->tipe_soal === 'pilihan_ganda';
    }

    public function isMenjodohkan(): bool
    {
        return $this->tipe_soal === 'menjodohkan';
    }

    public function getGambarUrlAttribute(): ?string
    {
        return $this->gambar ? Storage::url($this->gambar) : null;
    }
}
```

---

## 6. Controller

### 6.1 Superadmin — `PaketSoalController`

**Routes:**
```
GET    /superadmin/paket-soal              → index
GET    /superadmin/paket-soal/create       → create
POST   /superadmin/paket-soal              → store
GET    /superadmin/paket-soal/{id}         → show
GET    /superadmin/paket-soal/{id}/edit    → edit
PUT    /superadmin/paket-soal/{id}         → update
DELETE /superadmin/paket-soal/{id}         → destroy
PATCH  /superadmin/paket-soal/{id}/toggle  → toggleAktif
```

**Method utama:**

| Method | Deskripsi |
|---|---|
| `index()` | Tampil daftar semua paket soal, filter by jenjang & tahun ajaran |
| `create()` | Form buat paket soal baru + inisialisasi 2 mapel otomatis |
| `store()` | Simpan paket soal beserta 2 mapel |
| `show()` | Detail paket soal dengan daftar soal per mapel |
| `edit()` | Form edit metadata paket |
| `update()` | Update metadata paket |
| `destroy()` | Hapus paket soal (cascade ke mapel & soal) |
| `toggleAktif()` | Aktifkan/nonaktifkan paket soal |

---

### 6.2 Superadmin — `SoalController` (Superadmin)

**Routes:**
```
GET    /superadmin/paket-soal/{paket}/mapel/{mapel}/soal              → index
GET    /superadmin/paket-soal/{paket}/mapel/{mapel}/soal/create       → create
POST   /superadmin/paket-soal/{paket}/mapel/{mapel}/soal              → store
GET    /superadmin/paket-soal/{paket}/mapel/{mapel}/soal/{soal}/edit  → edit
PUT    /superadmin/paket-soal/{paket}/mapel/{mapel}/soal/{soal}       → update
DELETE /superadmin/paket-soal/{paket}/mapel/{mapel}/soal/{soal}       → destroy
```

**Catatan Controller:**
- `create()` menerima query param `tipe_soal=pilihan_ganda|menjodohkan`
- `store()` memvalidasi jumlah soal tidak melebihi `mapel.jumlah_soal` (30)
- Upload gambar dihandle via `StoreGambarSoal` Form Request
- Untuk soal pilihan ganda: validasi minimal 4 pilihan, tepat 1 jawaban benar
- Untuk soal menjodohkan: validasi minimal 3 pasangan

---

### 6.3 Guru — `PaketSoalGuruController`

**Routes:**
```
GET    /guru/paket-soal              → index (hanya paket di jenjang yang diajar)
GET    /guru/paket-soal/{id}         → show
GET    /guru/paket-soal/{id}/soal    → daftarSoal (per mapel)
```

Guru hanya bisa **melihat** paket soal yang sesuai dengan jenjang yang mereka ajar. Tidak dapat membuat/menghapus paket.

---

### 6.4 Guru — `SoalGuruController`

**Routes:**
```
GET    /guru/paket-soal/{paket}/mapel/{mapel}/soal              → index
GET    /guru/paket-soal/{paket}/mapel/{mapel}/soal/create       → create
POST   /guru/paket-soal/{paket}/mapel/{mapel}/soal              → store
GET    /guru/paket-soal/{paket}/mapel/{mapel}/soal/{soal}/edit  → edit
PUT    /guru/paket-soal/{paket}/mapel/{mapel}/soal/{soal}       → update
DELETE /guru/paket-soal/{paket}/mapel/{mapel}/soal/{soal}       → destroy
```

**Kebijakan akses:** Guru hanya bisa mengelola soal di mapel yang sesuai jenjang mereka (via `Policy: SoalPolicy`).

---

## 7. Blade Views

### 7.1 Struktur Direktori Views

```
resources/views/
├── superadmin/
│   ├── paket-soal/
│   │   ├── index.blade.php          ← Daftar semua paket soal
│   │   ├── create.blade.php         ← Form buat paket soal baru
│   │   ├── edit.blade.php           ← Form edit metadata paket
│   │   └── show.blade.php           ← Detail paket + ringkasan soal
│   └── soal/
│       ├── index.blade.php          ← Daftar soal per mapel
│       ├── create.blade.php         ← Form buat soal (2 tipe)
│       └── edit.blade.php           ← Form edit soal
│
├── guru/
│   ├── paket-soal/
│   │   ├── index.blade.php          ← Daftar paket relevan
│   │   └── show.blade.php           ← Detail paket
│   └── soal/
│       ├── index.blade.php          ← Daftar soal per mapel
│       ├── create.blade.php         ← Form buat soal
│       └── edit.blade.php           ← Form edit soal
│
└── ujian/                           ← View halaman ujian siswa
    ├── mulai.blade.php              ← Halaman konfirmasi mulai ujian
    ├── pengerjaan.blade.php         ← Halaman pengerjaan soal (utama)
    └── selesai.blade.php            ← Halaman konfirmasi selesai
```

---

### 7.2 Komponen Blade: Form Soal (Shared)

Form soal menggunakan **tab/toggle** berdasarkan `tipe_soal`. Terdapat dua bagian form yang muncul secara kondisional:

**Bagian Umum (semua tipe soal):**
- Input `nomor_soal` (auto-increment, bisa override)
- Select `tipe_soal` (pilihan ganda / menjodohkan)
- Select `teks_bacaan_id` (dropdown teks bacaan yang tersedia, opsional)
- Textarea `indikator`
- Textarea/Rich Text `pertanyaan`
- File input `gambar` (opsional, preview langsung)

**Bagian Pilihan Ganda:**
- 4 baris input pilihan (A, B, C, D): teks + gambar opsional
- Radio button "Jawaban Benar" per pilihan

**Bagian Menjodohkan:**
- Tabel pasangan dinamis (tambah/hapus baris via JS)
- Kolom Kiri: `teks_kiri`
- Kolom Kanan: `teks_kanan`
- Minimal 3 pasangan

---

### 7.3 Halaman Pengerjaan Ujian (Siswa)

**Layout utama `pengerjaan.blade.php`:**

```
+------------------------------------------+
| Header: Nama Mapel | Nama Siswa  | TIMER  |
+------------------------------------------+
| [Teks Bacaan — jika ada]                  |
+------------------------------------------+
| Nomor & Indikator                         |
| Pertanyaan                                |
| [Gambar — jika ada]                       |
|                                           |
| Tipe Pilihan Ganda:                       |
|   ○ A. ...   ○ B. ...                    |
|   ○ C. ...   ○ D. ...                    |
|                                           |
| Tipe Menjodohkan:                         |
|   [Kolom Kiri]    [Kolom Kanan — select]  |
|   Pernyataan 1  → [Dropdown jawaban]      |
|   Pernyataan 2  → [Dropdown jawaban]      |
|   Pernyataan 3  → [Dropdown jawaban]      |
|                                           |
+------------------------------------------+
| [ < Sebelumnya ]  [ Tandai Ragu ]  [ Selanjutnya > ] |
+------------------------------------------+
| Navigasi Soal (grid nomor 1–30)           |
| ■ Dijawab  □ Belum  ◈ Ragu-ragu           |
+------------------------------------------+
```

**Fitur navigasi soal:**
- Grid 30 nomor soal (5 kolom × 6 baris atau menyesuaikan)
- Warna indikator: **hijau** = sudah dijawab, **putih/abu** = belum, **kuning** = ragu-ragu
- Klik nomor langsung lompat ke soal tersebut
- Tombol "Selanjutnya" dan "Sebelumnya"
- Tombol "Tandai Ragu-ragu" dengan toggle

**Timer:**
- Countdown 75 menit per mapel
- Disimpan ke `sessionStorage` untuk toleransi refresh
- Warning pop-up saat sisa waktu 5 menit
- Auto-submit saat waktu habis

---

## 8. Form Request & Validasi

### `StorePaketSoalRequest`

```php
public function rules(): array
{
    return [
        'jenjang_id'   => 'required|exists:jenjangs,id',
        'nama'         => 'required|string|max:255',
        'tahun_ajaran' => 'required|string',
    ];
}
```

### `StoreSoalRequest`

```php
public function rules(): array
{
    return [
        'nomor_soal'   => 'required|integer|min:1|max:30',
        'tipe_soal'    => 'required|in:pilihan_ganda,menjodohkan',
        'indikator'    => 'required|string',
        'pertanyaan'   => 'required|string',
        'gambar'       => 'nullable|image|max:2048',
        'teks_bacaan_id' => 'nullable|exists:teks_bacaans,id',

        // Pilihan ganda
        'pilihan'               => 'required_if:tipe_soal,pilihan_ganda|array|size:4',
        'pilihan.*.kode'        => 'required|in:A,B,C,D',
        'pilihan.*.teks'        => 'required|string',
        'pilihan.*.is_benar'    => 'boolean',
        'jawaban_benar'         => 'required_if:tipe_soal,pilihan_ganda|in:A,B,C,D',

        // Menjodohkan
        'pasangan'              => 'required_if:tipe_soal,menjodohkan|array|min:3',
        'pasangan.*.teks_kiri'  => 'required|string',
        'pasangan.*.teks_kanan' => 'required|string',
    ];
}
```

---

## 9. Kebijakan Akses (Policy)

| Policy | Superadmin | Guru |
|---|---|---|
| Lihat daftar paket soal | ✅ Semua | ✅ Jenjang sendiri |
| Buat paket soal | ✅ | ❌ |
| Edit metadata paket | ✅ | ❌ |
| Hapus paket soal | ✅ | ❌ |
| Toggle aktif paket | ✅ | ❌ |
| Lihat soal | ✅ | ✅ Jenjang sendiri |
| Buat/Edit/Hapus soal | ✅ | ✅ Jenjang sendiri |

---

## 10. Rencana Implementasi

### Fase 1 — Database & Model (Prioritas Tinggi)
- [ ] Buat migration: `paket_soals`, `mapel_pakets`, `teks_bacaans`
- [ ] Buat migration: `soals`, `pilihan_jawabans`, `pasangan_menjodohkans`
- [ ] Buat migration: `jawaban_siswas`, `ujian_sesis`
- [ ] Buat semua Model dengan relasi lengkap
- [ ] Buat Seeder/Factory untuk data dummy

### Fase 2 — Backend Superadmin (Prioritas Tinggi)
- [ ] `PaketSoalController` (CRUD + toggle aktif)
- [ ] `SoalController` (CRUD + upload gambar)
- [ ] Form Request & validasi
- [ ] Policy & Gate

### Fase 3 — Backend Guru (Prioritas Tinggi)
- [ ] `PaketSoalGuruController` (read-only)
- [ ] `SoalGuruController` (CRUD terbatas)
- [ ] Middleware validasi jenjang guru

### Fase 4 — Blade Views (Prioritas Tinggi)
- [ ] Views Superadmin: paket soal & soal
- [ ] Views Guru: paket soal & soal
- [ ] Form soal universal (pilihan ganda + menjodohkan)
- [ ] Halaman pengerjaan ujian siswa
- [ ] Komponen navigasi soal (grid)
- [ ] Komponen timer countdown

### Fase 5 — Ujian Siswa & Auto-save (Prioritas Tinggi)
- [ ] Controller ujian siswa
- [ ] Auto-save jawaban via AJAX/Livewire
- [ ] Timer dengan auto-submit
- [ ] Halaman konfirmasi selesai

### Fase 6 — QA & Finalisasi
- [ ] Unit test model
- [ ] Feature test controller
- [ ] UAT dengan guru & siswa
- [ ] Optimasi query (eager loading)

---

## 11. Catatan Teknis

- **Upload gambar:** Gunakan `storage/app/public/soal/` dan symlink `php artisan storage:link`
- **Auto-save:** Implementasi AJAX `POST /ujian/jawaban/save` setiap ganti soal
- **Timer:** Simpan `sisa_waktu` ke database setiap 30 detik sebagai backup
- **Menjodohkan randomize:** Kolom kanan di-shuffle saat tampil ke siswa
- **Eager loading wajib:** Semua query soal harus `with(['pilihanJawabans', 'pasanganMenjodohkans', 'teksBacaan'])`
- **Validasi nomor soal:** Pastikan tidak ada duplikat nomor soal dalam satu mapel paket

---

*PRD ini akan diperbarui seiring berjalannya development. Setiap perubahan scope harus didiskusikan bersama tim.*
