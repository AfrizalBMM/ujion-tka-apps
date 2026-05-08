<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppBlast;
use App\Models\PaketSoal;
use App\Models\Participant;
use App\Models\User;
use App\Models\WhatsAppLog;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WhatsAppGatewayController extends Controller
{
    public function connection()
    {
        $waGatewayUrl = rtrim((string) env('WA_GATEWAY_URL', 'http://127.0.0.1:3000'), '/');
        $senderId = (string) env('WA_SENDER_ID', 'tka-admin');

        return view('superadmin.wa-koneksi', compact('waGatewayUrl', 'senderId'));
    }

    public function blastForm(Request $request)
    {
        $jenjangOptions = User::query()
            ->where('role', User::ROLE_GURU)
            ->whereNotNull('jenjang')
            ->where('jenjang', '!=', '')
            ->distinct()
            ->orderBy('jenjang')
            ->pluck('jenjang')
            ->values()
            ->all();

        $schoolOptions = User::query()
            ->where('role', User::ROLE_GURU)
            ->whereNotNull('satuan_pendidikan')
            ->where('satuan_pendidikan', '!=', '')
            ->distinct()
            ->orderBy('satuan_pendidikan')
            ->limit(50)
            ->pluck('satuan_pendidikan')
            ->values()
            ->all();

        $paketSoalOptions = PaketSoal::query()
            ->with('jenjang')
            ->latest()
            ->get();

        if (empty($jenjangOptions)) {
            $jenjangOptions = ['SD', 'SMP', 'SMA'];
        }

        $blastStats = WhatsAppLog::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $blastLogs = WhatsAppLog::query()
            ->latest()
            ->limit(10)
            ->get();

        return view('superadmin.wa-blast', [
            'jenjangOptions' => $jenjangOptions,
            'schoolOptions' => $schoolOptions,
            'paketSoalOptions' => $paketSoalOptions,
            'blastStats' => $blastStats,
            'blastLogs' => $blastLogs,
        ]);
    }

    public function sendBlast(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'target' => ['required', 'in:guru_all_active,guru_jenjang,guru_school,siswa_all,siswa_paket'],
            'jenjang' => ['nullable', 'string', 'max:20'],
            'school' => ['nullable', 'string', 'max:200'],
            'paket_soal_id' => ['nullable', 'integer', 'exists:paket_soals,id'],
            'scheduled_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
        ]);

        if ($validated['target'] === 'guru_jenjang' && blank($validated['jenjang'] ?? null)) {
            return back()->with('flash', [
                'type' => 'warning',
                'title' => 'Target belum lengkap',
                'message' => 'Silakan pilih jenjang untuk target blast.',
            ]);
        }

        if ($validated['target'] === 'guru_school' && blank($validated['school'] ?? null)) {
            return back()->with('flash', [
                'type' => 'warning',
                'title' => 'Target belum lengkap',
                'message' => 'Silakan isi nama sekolah/satuan pendidikan untuk target blast.',
            ]);
        }

        if ($validated['target'] === 'siswa_paket' && blank($validated['paket_soal_id'] ?? null)) {
            return back()->with('flash', [
                'type' => 'warning',
                'title' => 'Target belum lengkap',
                'message' => 'Silakan pilih paket soal untuk target blast siswa.',
            ]);
        }

        $scheduledAt = null;
        if (! blank($validated['scheduled_at'] ?? null)) {
            $scheduledAt = Carbon::createFromFormat('Y-m-d\TH:i', $validated['scheduled_at']);

            if ($scheduledAt === false) {
                return back()->with('flash', [
                    'type' => 'warning',
                    'title' => 'Jadwal tidak valid',
                    'message' => 'Silakan pilih tanggal dan waktu pengiriman blast yang valid.',
                ]);
            }

            if ($scheduledAt->isPast()) {
                return back()->with('flash', [
                    'type' => 'warning',
                    'title' => 'Jadwal tidak valid',
                    'message' => 'Jadwal blast harus di masa depan atau kosong untuk mengirim segera.',
                ]);
            }
        }

        $totalQueued = 0;

        if (str_starts_with($validated['target'], 'guru_')) {
            $teachersQuery = User::query()
                ->where('role', User::ROLE_GURU)
                ->where('account_status', User::STATUS_ACTIVE)
                ->whereNotNull('no_wa')
                ->where('no_wa', '!=', '');

            if ($validated['target'] === 'guru_jenjang') {
                $teachersQuery->where('jenjang', $validated['jenjang']);
            }

            if ($validated['target'] === 'guru_school') {
                $school = trim((string) ($validated['school'] ?? ''));
                $teachersQuery->where('satuan_pendidikan', 'like', '%' . $school . '%');
            }

            $teachersQuery
                ->orderBy('id')
                ->chunkById(200, function ($teachers) use (&$totalQueued, $validated, $scheduledAt) {
                    foreach ($teachers as $teacher) {
                        $delaySeconds = random_int(2, 7);
                        $delay = $scheduledAt ? $scheduledAt->copy()->addSeconds($delaySeconds) : now()->addSeconds($delaySeconds);

                        SendWhatsAppBlast::dispatch($teacher->no_wa, $validated['message'])
                            ->onQueue('low')
                            ->delay($delay);

                        $totalQueued++;
                    }
                });
        } else {
            $participantsQuery = Participant::query()
                ->whereNotNull('nomor_wa')
                ->where('nomor_wa', '!=', '');

            if ($validated['target'] === 'siswa_paket') {
                $paketSoalId = (int) ($validated['paket_soal_id'] ?? 0);
                $participantsQuery->whereHas('exam', function ($query) use ($paketSoalId) {
                    $query->where('paket_soal_id', $paketSoalId);
                });
            }

            $participantsQuery
                ->select('nomor_wa')
                ->distinct()
                ->orderBy('nomor_wa')
                ->chunk(200, function ($rows) use (&$totalQueued, $validated, $scheduledAt) {
                    foreach ($rows as $row) {
                        $delaySeconds = random_int(2, 7);
                        $delay = $scheduledAt ? $scheduledAt->copy()->addSeconds($delaySeconds) : now()->addSeconds($delaySeconds);

                        SendWhatsAppBlast::dispatch($row->nomor_wa, $validated['message'])
                            ->onQueue('low')
                            ->delay($delay);

                        $totalQueued++;
                    }
                });
        }

        return back()->with('flash', [
            'type' => 'success',
            'title' => 'Blast dijadwalkan',
            'message' => "Pesan berhasil dimasukkan ke antrean untuk {$totalQueued} penerima.",
            'description' => 'Pastikan queue worker berjalan agar pesan terkirim.',
        ]);
    }
}
