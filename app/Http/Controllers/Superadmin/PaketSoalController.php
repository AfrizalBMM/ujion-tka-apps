<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaketSoalRequest;
use App\Http\Requests\UpdatePaketSoalRequest;
use App\Models\Jenjang;
use App\Models\MapelPaket;
use App\Models\PaketSoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaketSoalController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', PaketSoal::class);

        $jenjangs = Jenjang::orderBy('urutan')->get();
        $paketSoals = PaketSoal::query()
            ->with(['jenjang', 'createdBy', 'mapelPakets'])
            ->when($request->filled('jenjang_id'), fn ($query) => $query->where('jenjang_id', $request->integer('jenjang_id')))
            ->when($request->filled('tahun_ajaran'), fn ($query) => $query->where('tahun_ajaran', $request->string('tahun_ajaran')))
            ->latest()
            ->get();

        return view('superadmin.paket-soal.index', compact('paketSoals', 'jenjangs'));
    }

    public function create(): View
    {
        $this->authorize('create', PaketSoal::class);

        $jenjangs = Jenjang::orderBy('urutan')->get();

        return view('superadmin.paket-soal.create', compact('jenjangs'));
    }

    public function store(StorePaketSoalRequest $request): RedirectResponse
    {
        $this->authorize('create', PaketSoal::class);

        $paket = DB::transaction(function () use ($request) {
            $paket = PaketSoal::create([
                'jenjang_id' => $request->integer('jenjang_id'),
                'nama' => $request->string('nama')->toString(),
                'tahun_ajaran' => $request->string('tahun_ajaran')->toString(),
                'is_active' => $request->boolean('is_active'),
                'created_by' => $request->user()->id,
            ]);

            collect([
                [
                    'nama_mapel' => MapelPaket::NAMA_MATEMATIKA,
                    'kategori_komponen' => 'akademik',
                    'mode_penilaian' => 'score',
                    'kode_komponen' => 'MAT',
                    'petunjuk_khusus' => 'Kerjakan soal numerasi dengan teliti dan manfaatkan waktu secara efektif.',
                    'urutan' => 1,
                ],
                [
                    'nama_mapel' => MapelPaket::NAMA_BAHASA_INDONESIA,
                    'kategori_komponen' => 'akademik',
                    'mode_penilaian' => 'score',
                    'kode_komponen' => 'BIND',
                    'petunjuk_khusus' => 'Baca teks dan pertanyaan dengan cermat sebelum memilih jawaban.',
                    'urutan' => 2,
                ],
                [
                    'nama_mapel' => MapelPaket::NAMA_SURVEY_KARAKTER,
                    'kategori_komponen' => 'survey',
                    'mode_penilaian' => 'profiling',
                    'kode_komponen' => 'SK',
                    'petunjuk_khusus' => 'Pilih jawaban yang paling menggambarkan kebiasaan dan sikap Anda sehari-hari.',
                    'urutan' => 3,
                ],
                [
                    'nama_mapel' => MapelPaket::NAMA_SURVEY_LINGKUNGAN,
                    'kategori_komponen' => 'survey',
                    'mode_penilaian' => 'profiling',
                    'kode_komponen' => 'SLB',
                    'petunjuk_khusus' => 'Jawab sesuai kondisi belajar yang Anda rasakan, bukan berdasarkan jawaban yang dianggap ideal.',
                    'urutan' => 4,
                ],
            ])->each(fn (array $item) => MapelPaket::create([
                'paket_soal_id' => $paket->id,
                'nama_mapel' => $item['nama_mapel'],
                'kategori_komponen' => $item['kategori_komponen'],
                'mode_penilaian' => $item['mode_penilaian'],
                'kode_komponen' => $item['kode_komponen'],
                'is_wajib' => true,
                'petunjuk_khusus' => $item['petunjuk_khusus'],
                'jumlah_soal' => 30,
                'durasi_menit' => 75,
                'urutan' => $item['urutan'],
            ]));

            return $paket;
        });

        return redirect()->route('superadmin.paket-soal.show', $paket)
            ->with('flash', ['type' => 'success', 'message' => 'Paket soal berhasil dibuat.']);
    }

    public function show(PaketSoal $paket): View
    {
        $this->authorize('view', $paket);

        $paket->load([
            'jenjang',
            'createdBy',
            'mapelPakets.teksBacaans',
            'mapelPakets.soals.pilihanJawabans',
            'mapelPakets.soals.pasanganMenjodohkans',
            'exams' => fn ($q) => $q->with('examMapelTokens.mapelPaket')->orderByDesc('tanggal_terbit'),
        ]);

        return view('superadmin.paket-soal.show', compact('paket'));
    }

    public function edit(PaketSoal $paket): View
    {
        $this->authorize('update', $paket);

        $jenjangs = Jenjang::orderBy('urutan')->get();

        return view('superadmin.paket-soal.edit', compact('paket', 'jenjangs'));
    }

    public function update(UpdatePaketSoalRequest $request, PaketSoal $paket): RedirectResponse
    {
        $this->authorize('update', $paket);

        $paket->update([
            'jenjang_id' => $request->integer('jenjang_id'),
            'nama' => $request->string('nama')->toString(),
            'tahun_ajaran' => $request->string('tahun_ajaran')->toString(),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('superadmin.paket-soal.show', $paket)
            ->with('flash', ['type' => 'success', 'message' => 'Metadata paket soal diperbarui.']);
    }

    public function destroy(PaketSoal $paket): RedirectResponse
    {
        $this->authorize('delete', $paket);

        $hasExamDependencies = $paket->exams()
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhereHas('ujianSesis');
            })
            ->exists();

        if ($hasExamDependencies) {
            return back()->with('flash', [
                'type' => 'warning',
                'message' => 'Paket soal tidak bisa dihapus karena sudah dipakai oleh ujian aktif atau memiliki riwayat sesi ujian.',
            ]);
        }

        $paket->delete();

        return redirect()->route('superadmin.paket-soal.index')
            ->with('flash', ['type' => 'success', 'message' => 'Paket soal dihapus.']);
    }

    public function toggleAktif(PaketSoal $paket): RedirectResponse
    {
        $this->authorize('toggleAktif', $paket);

        $paket->update(['is_active' => ! $paket->is_active]);

        return back()->with('flash', ['type' => 'success', 'message' => 'Status aktif paket diperbarui.']);
    }
}
