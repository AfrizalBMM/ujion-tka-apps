<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\LandingExam;
use App\Models\LandingExamMapel;
use App\Models\LandingExamOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingExamController extends Controller
{
    public function index(): View
    {
        $landingExams = LandingExam::with([
            'exam.paketSoal.jenjang',
            'mapels.mapelPaket',
        ])
            ->withCount(['orders as paid_orders_count' => fn ($q) => $q->where('status', '!=', LandingExamOrder::STATUS_PENDING_PAYMENT)])
            ->orderBy('sort_order')
            ->latest()
            ->get();

        $totalRevenue = LandingExamOrder::where('status', '!=', LandingExamOrder::STATUS_PENDING_PAYMENT)
            ->where('status', '!=', LandingExamOrder::STATUS_FAILED)
            ->sum('amount');

        $totalOrders = LandingExamOrder::count();

        return view('superadmin.landing-exams.index', compact('landingExams', 'totalRevenue', 'totalOrders'));
    }

    public function create(): View
    {
        $exams = Exam::with(['paketSoal.jenjang', 'paketSoal.mapelPakets', 'examMapelTokens'])
            ->where('is_active', true)
            ->where('status', 'terbit')
            ->whereDoesntHave('landingExam')
            ->orderBy('judul')
            ->get();

        return view('superadmin.landing-exams.create', compact('exams'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'jenjang' => 'required|in:SD,SMP,SMA',
            'slug' => 'nullable|string|max:255|unique:landing_exams,slug',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'mapels' => 'required|array|min:1',
            'mapels.*.mapel_paket_id' => 'required|exists:mapel_pakets,id',
            'mapels.*.price' => 'required|numeric|min:0',
            'mapels.*.original_price' => 'nullable|numeric|min:0',
            'mapels.*.is_active' => 'boolean',
        ]);

        $exam = Exam::with(['paketSoal.mapelPakets', 'examMapelTokens'])->findOrFail($validated['exam_id']);

        $landingExam = LandingExam::create([
            'exam_id' => $validated['exam_id'],
            'jenjang' => $validated['jenjang'],
            'slug' => $validated['slug'] ?? null,
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? false,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $validMapelIds = $exam->paketSoal->mapelPakets->pluck('id')->all();
        $sortOrder = 0;

        foreach ($validated['mapels'] as $mapelData) {
            if (! in_array((int) $mapelData['mapel_paket_id'], $validMapelIds, true)) {
                continue;
            }

            $landingExam->mapels()->create([
                'mapel_paket_id' => $mapelData['mapel_paket_id'],
                'price' => $mapelData['price'],
                'original_price' => $mapelData['original_price'] ?? null,
                'is_active' => $mapelData['is_active'] ?? false,
                'sort_order' => $sortOrder++,
            ]);
        }

        return redirect()->route('superadmin.landing-exams.show', $landingExam)
            ->with('flash', ['type' => 'success', 'message' => 'Ujian publik berhasil dibuat.']);
    }

    public function show(LandingExam $landingExam): View
    {
        $landingExam->load([
            'exam.paketSoal.jenjang',
            'exam.paketSoal.mapelPakets',
            'exam.examMapelTokens',
            'mapels.mapelPaket',
        ]);

        $ordersCount = $landingExam->orders()->count();
        $paidOrdersCount = $landingExam->orders()
            ->where('status', '!=', LandingExamOrder::STATUS_PENDING_PAYMENT)
            ->where('status', '!=', LandingExamOrder::STATUS_FAILED)
            ->count();
        $revenue = $landingExam->orders()
            ->where('status', '!=', LandingExamOrder::STATUS_PENDING_PAYMENT)
            ->where('status', '!=', LandingExamOrder::STATUS_FAILED)
            ->sum('amount');

        return view('superadmin.landing-exams.show', compact('landingExam', 'ordersCount', 'paidOrdersCount', 'revenue'));
    }

    public function update(Request $request, LandingExam $landingExam)
    {
        $validated = $request->validate([
            'jenjang' => 'required|in:SD,SMP,SMA',
            'slug' => 'nullable|string|max:255|unique:landing_exams,slug,'.$landingExam->id,
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'mapels' => 'array',
            'mapels.*.id' => 'nullable|exists:landing_exam_mapels,id',
            'mapels.*.price' => 'required|numeric|min:0',
            'mapels.*.original_price' => 'nullable|numeric|min:0',
            'mapels.*.is_active' => 'boolean',
        ]);

        $landingExam->update([
            'jenjang' => $validated['jenjang'],
            'slug' => $validated['slug'] ?? $landingExam->slug,
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? false,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        if (isset($validated['mapels'])) {
            foreach ($validated['mapels'] as $mapelData) {
                if (! empty($mapelData['id'])) {
                    $mapel = LandingExamMapel::find($mapelData['id']);
                    if ($mapel && $mapel->landing_exam_id === $landingExam->id) {
                        $mapel->update([
                            'price' => $mapelData['price'],
                            'original_price' => $mapelData['original_price'] ?? null,
                            'is_active' => $mapelData['is_active'] ?? false,
                        ]);
                    }
                }
            }
        }

        return redirect()->route('superadmin.landing-exams.show', $landingExam)
            ->with('flash', ['type' => 'success', 'message' => 'Ujian publik diperbarui.']);
    }

    public function toggle(LandingExam $landingExam)
    {
        $landingExam->update(['is_active' => ! $landingExam->is_active]);

        return back()->with('flash', ['type' => 'success', 'message' => 'Status ujian publik diubah.']);
    }

    public function toggleMapel(LandingExam $landingExam, LandingExamMapel $mapel)
    {
        abort_if($mapel->landing_exam_id !== $landingExam->id, 404);

        $mapel->update(['is_active' => ! $mapel->is_active]);

        return back()->with('flash', ['type' => 'success', 'message' => 'Status mapel diubah.']);
    }

    public function destroy(LandingExam $landingExam)
    {
        $landingExam->delete();

        return redirect()->route('superadmin.landing-exams.index')
            ->with('flash', ['type' => 'success', 'message' => 'Ujian publik dihapus.']);
    }

    public function orders(LandingExam $landingExam): View
    {
        $orders = $landingExam->orders()
            ->with(['landingExamMapel.mapelPaket'])
            ->latest()
            ->paginate(20);

        $revenue = $landingExam->orders()
            ->where('status', '!=', LandingExamOrder::STATUS_PENDING_PAYMENT)
            ->where('status', '!=', LandingExamOrder::STATUS_FAILED)
            ->sum('amount');

        return view('superadmin.landing-exams.orders', compact('landingExam', 'orders', 'revenue'));
    }
}
