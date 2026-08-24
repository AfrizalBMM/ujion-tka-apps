<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\Material;
use App\Models\UjianSesi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $availableExamsCount = Exam::query()
            ->where('status', 'terbit')
            ->where('is_active', true)
            ->whereHas('paketSoal.jenjang', fn ($query) => $query->where('kode', $user->jenjang))
            ->count();

        // Statistik siswa nyata: peserta ujian (non-simulasi) di paket jenjang guru.
        $siswaSesiQuery = UjianSesi::query()
            ->whereNull('user_id')
            ->whereHas('paketSoal.jenjang', fn ($query) => $query->where('kode', $user->jenjang));

        $totalPeserta = (clone $siswaSesiQuery)
            ->where('status', 'selesai')
            ->distinct('nomor_wa')
            ->count('nomor_wa');

        $rataRataKelas = (float) ((clone $siswaSesiQuery)
            ->where('status', 'selesai')
            ->whereNotNull('skor')
            ->avg('skor') ?? 0);

        $simulasiSelesai = UjianSesi::query()
            ->where('user_id', $user->id)
            ->where('status', 'selesai')
            ->count();

        $logs = AuditLog::where('user_id', $user->id)->latest()->limit(10)->get();
        $materialsCount = Material::query()
            ->when(
                Schema::hasColumn('materials', 'jenjang') && $user->jenjang,
                fn ($query) => $query->where(function ($inner) use ($user) {
                    $inner->whereNull('jenjang')->orWhere('jenjang', $user->jenjang);
                })
            )
            ->count();

        $pengumuman = array_values(array_filter([
            $availableExamsCount > 0
                ? "Terdapat {$availableExamsCount} ujian aktif untuk jenjang {$user->jenjang}."
                : "Belum ada ujian aktif untuk jenjang {$user->jenjang}.",
            $materialsCount > 0
                ? 'Materi belajar sudah tersedia dan dapat dibookmark dari menu Materi.'
                : null,
        ]));

        return view('guru.dashboard', compact('totalPeserta', 'rataRataKelas', 'simulasiSelesai', 'logs', 'pengumuman'));
    }
}
