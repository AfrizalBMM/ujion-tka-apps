<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\GlobalQuestion;
use App\Models\LandingBranding;
use App\Models\LandingContent;
use App\Models\LandingFaq;
use App\Models\LandingHeroMockup;
use App\Models\Material;
use App\Models\PricingPlan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class LandingSettingsController extends Controller
{
    private const SECTIONS = ['hero', 'faq', 'pricing', 'stats'];

    public function index(Request $request): View
    {
        $allowedTabs = ['hero', 'pricing', 'content', 'faq', 'stats', 'branding'];
        $tab = $request->query('tab', 'content');
        if (! in_array($tab, $allowedTabs, true)) {
            $tab = 'content';
        }

        $hasPricingTable = Schema::hasTable('pricing_plans');
        $hasJenjangColumn = $hasPricingTable && Schema::hasColumn('pricing_plans', 'jenjang');
        $hasQrisImageColumn = $hasPricingTable && Schema::hasColumn('pricing_plans', 'qris_image_path');

        $tarifJenjangs = collect();
        if ($hasPricingTable) {
            $tarifQuery = PricingPlan::query()->orderByDesc('is_active');

            if ($hasJenjangColumn) {
                $tarifQuery->orderByRaw("case when jenjang = 'SD' then 1 when jenjang = 'SMP' then 2 when jenjang = 'SMA' then 3 else 4 end");
            }

            $tarifJenjangs = $tarifQuery->orderBy('id')->get();
        }

        $sectionActives = [
            'hero' => true,
            'faq' => true,
            'pricing' => true,
            'stats' => true,
        ];

        if (Schema::hasTable('landing_contents')) {
            $sectionRows = LandingContent::query()
                ->whereIn('section', self::SECTIONS)
                ->get()
                ->keyBy('section');

            foreach (self::SECTIONS as $sectionKey) {
                if ($sectionRows->has($sectionKey)) {
                    $sectionActives[$sectionKey] = (bool) $sectionRows[$sectionKey]->is_active;
                }
            }
        }

        $hero = [
            'kicker' => 'Website pendamping guru untuk memantau kesiapan siswa menuju Tes Kemampuan Akademik (TKA).',
            'title' => 'Bantu guru memantau, menganalisis, dan menyiapkan siswa agar lebih siap menghadapi TKA.',
            'body' => 'Ujion TKA dirancang untuk guru/operator yang ingin melihat perkembangan akademik siswa dengan lebih jelas. Mulai dari latihan, paket soal, sesi ujian, sampai hasil akhir, semua disusun agar guru lebih mudah membaca kesiapan siswa, menemukan kelemahan belajar, dan mengambil langkah pembinaan sebelum TKA berlangsung.',
            'button_text' => 'Coba Sebagai Guru',
            'button_url' => null,
        ];

        if (Schema::hasTable('landing_contents')) {
            $heroContent = LandingContent::query()
                ->where('section', 'hero')
                ->first();

            if ($heroContent) {
                $hero['kicker'] = $heroContent->kicker ?: $hero['kicker'];
                $hero['title'] = $heroContent->title ?: $hero['title'];
                $hero['body'] = $heroContent->body ?: $hero['body'];
                $hero['button_text'] = $heroContent->button_text ?: $hero['button_text'];
                $hero['button_url'] = $heroContent->button_url ?: null;
            }
        }

        $defaultFaqs = [
            [
                'question' => 'Apakah platform ini cocok untuk pelaksanaan TKA?',
                'answer' => 'Ya. Platform ini cocok dipakai untuk membantu guru menyiapkan siswa menuju TKA melalui latihan, ujian, dan pembacaan hasil yang lebih terarah.',
            ],
            [
                'question' => 'Apakah guru bisa mengelola soal sendiri?',
                'answer' => 'Bisa. Guru memiliki workflow untuk bank soal, paket soal, mapel, dan sesi ujian sehingga persiapan siswa bisa dikelola dengan lebih rapi.',
            ],
            [
                'question' => 'Apakah siswa harus punya akun?',
                'answer' => 'Tidak. Siswa bisa masuk dengan token ujian, lalu mengisi identitas dan langsung mengikuti sesi yang tersedia.',
            ],
            [
                'question' => 'Apakah tetap relevan dengan arah resmi TKA?',
                'answer' => 'Ya. Platform ini dapat diposisikan selaras dengan semangat TKA karena membantu sekolah membaca capaian akademik siswa secara lebih terstruktur.',
            ],
            [
                'question' => 'Apakah bisa dipakai di HP siswa?',
                'answer' => 'Bisa. Tampilan dibuat responsif sehingga siswa tetap nyaman mengerjakan dari perangkat mobile, sementara guru tetap mengelola dari dashboard.',
            ],
            [
                'question' => 'Bagaimana jika koneksi internet tidak stabil?',
                'answer' => 'Sistem membantu meminimalkan risiko kehilangan jawaban dengan autosave, sehingga progres siswa tetap tercatat meski koneksi naik turun.',
            ],
        ];

        $faqs = collect($defaultFaqs);
        $editFaq = null;

        if (Schema::hasTable('landing_faqs')) {
            $faqs = LandingFaq::query()
                ->orderBy('sort_order')
                ->orderByDesc('is_active')
                ->orderBy('id')
                ->get();

            $editFaqId = $request->query('faq_id');
            if ($editFaqId) {
                $editFaq = $faqs->firstWhere('id', (int) $editFaqId);
            }
        }

        $logoUrl = asset('assets/img/logo.png');
        $branding = null;

        if (Schema::hasTable('landing_brandings')) {
            $branding = LandingBranding::query()
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();

            if ($branding && $branding->logo_path) {
                $logoUrl = Storage::disk('public')->url($branding->logo_path);
            }
        }

        $heroMockups = collect();
        $editHeroMockup = null;

        if (Schema::hasTable('landing_hero_mockups')) {
            $heroMockups = LandingHeroMockup::query()
                ->orderByDesc('is_featured')
                ->orderByDesc('is_active')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $editHeroMockupId = $request->query('hero_mockup_id');
            if ($editHeroMockupId) {
                $editHeroMockup = $heroMockups->firstWhere('id', (int) $editHeroMockupId);
            }
        }

        $materialTotal = Material::query()->count();
        $questionTotal = GlobalQuestion::query()->count();

        $faqTotal = Schema::hasTable('landing_faqs') ? LandingFaq::query()->count() : 0;
        $pricingTotal = $hasPricingTable ? PricingPlan::query()->count() : 0;

        $materialStats = Material::query()
            ->select('jenjang', 'mapel', DB::raw('count(*) as count'))
            ->groupBy('jenjang', 'mapel')
            ->get();

        $questionStats = GlobalQuestion::query()
            ->join('jenjangs', 'global_questions.jenjang_id', '=', 'jenjangs.id')
            ->select('jenjangs.kode as jenjang', 'material_mapel as mapel', DB::raw('count(*) as count'))
            ->groupBy('jenjangs.kode', 'material_mapel')
            ->get();

        $stats = [];

        foreach ($materialStats as $m) {
            $jenjang = $m->jenjang;
            $mapel = $m->mapel ?: 'Umum';
            $stats[$jenjang][$mapel]['materials'] = $m->count;
        }

        foreach ($questionStats as $q) {
            $jenjang = $q->jenjang;
            $mapel = $q->mapel ?: 'Umum';
            $stats[$jenjang][$mapel]['questions'] = $q->count;
        }

        return view('superadmin.landing-settings', [
            'tab' => $tab,
            'tarifJenjangs' => $tarifJenjangs,
            'hasJenjangColumn' => $hasJenjangColumn,
            'hasQrisImageColumn' => $hasQrisImageColumn,
            'sectionActives' => $sectionActives,
            'hero' => $hero,
            'faqs' => $faqs,
            'editFaq' => $editFaq,
            'logoUrl' => $logoUrl,
            'branding' => $branding,
            'heroMockups' => $heroMockups,
            'editHeroMockup' => $editHeroMockup,
            'materialTotal' => $materialTotal,
            'questionTotal' => $questionTotal,
            'faqTotal' => $faqTotal,
            'pricingTotal' => $pricingTotal,
            'stats' => $stats,
        ]);
    }

    public function toggleSection(Request $request, string $section): RedirectResponse
    {
        if (! in_array($section, self::SECTIONS, true)) {
            abort(404);
        }

        if (! Schema::hasTable('landing_contents')) {
            return back()->with('flash', [
                'type' => 'danger',
                'title' => 'Tabel landing_contents belum tersedia',
                'message' => 'Jalankan migrate dulu agar pengaturan section bisa diubah.',
            ]);
        }

        $content = LandingContent::query()->firstOrNew(['section' => $section]);
        $current = $content->exists ? (bool) $content->is_active : true;
        $content->is_active = ! $current;
        $content->sort_order = $content->sort_order ?? 0;
        $content->save();

        $tab = match ($section) {
            'hero' => 'content',
            'faq' => 'faq',
            'pricing' => 'pricing',
            'stats' => 'stats',
            default => 'content',
        };

        return redirect()->route('superadmin.landing-settings.index', ['tab' => $tab])->with('flash', [
            'type' => 'success',
            'title' => 'Status section diperbarui',
            'message' => $content->is_active ? 'Section diaktifkan.' : 'Section dinonaktifkan.',
        ]);
    }

    public function saveContent(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('landing_contents')) {
            return back()->with('flash', [
                'type' => 'danger',
                'title' => 'Tabel landing_contents belum tersedia',
                'message' => 'Jalankan migrate dulu agar konten landing dapat disimpan.',
            ]);
        }

        $validated = $request->validate([
            'kicker' => ['nullable', 'string', 'max:180'],
            'title' => ['nullable', 'string', 'max:220'],
            'body' => ['nullable', 'string', 'max:5000'],
            'button_text' => ['nullable', 'string', 'max:80'],
            'button_url' => ['nullable', 'string', 'max:255'],
        ]);

        LandingContent::query()->updateOrCreate(
            ['section' => 'hero'],
            [
                'kicker' => $validated['kicker'] ?? null,
                'title' => $validated['title'] ?? null,
                'body' => $validated['body'] ?? null,
                'button_text' => $validated['button_text'] ?? null,
                'button_url' => $validated['button_url'] ?? null,
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        return redirect()->route('superadmin.landing-settings.index', ['tab' => 'content'])->with('flash', [
            'type' => 'success',
            'title' => 'Konten landing disimpan',
            'message' => 'Hero section landing sudah diperbarui.',
        ]);
    }

    public function saveLogo(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('landing_brandings')) {
            return back()->with('flash', [
                'type' => 'danger',
                'title' => 'Tabel landing_brandings belum tersedia',
                'message' => 'Jalankan migrate dulu agar logo landing dapat disimpan.',
            ]);
        }

        $validated = $request->validate([
            'logo' => ['required', 'image', 'max:4096'],
        ]);

        $path = $request->file('logo')->store('landing', 'public');

        $branding = LandingBranding::query()->orderByDesc('id')->first();
        $oldPath = $branding?->logo_path;

        if (! $branding) {
            $branding = new LandingBranding();
        }

        $branding->fill([
            'logo_path' => $path,
            'is_active' => true,
        ]);
        $branding->save();

        LandingBranding::query()
            ->where('id', '!=', $branding->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->route('superadmin.landing-settings.index', ['tab' => 'branding'])->with('flash', [
            'type' => 'success',
            'title' => 'Logo diperbarui',
            'message' => 'Logo landing berhasil diupload dan disimpan.',
        ]);
    }

    public function toggleBranding(): RedirectResponse
    {
        if (! Schema::hasTable('landing_brandings')) {
            return back()->with('flash', [
                'type' => 'danger',
                'title' => 'Tabel landing_brandings belum tersedia',
                'message' => 'Jalankan migrate dulu agar branding bisa diubah.',
            ]);
        }

        $branding = LandingBranding::query()->orderByDesc('id')->first();
        if (! $branding) {
            return back()->with('flash', [
                'type' => 'warning',
                'title' => 'Belum ada logo custom',
                'message' => 'Upload logo terlebih dahulu untuk mengaktifkan/nonaktifkan logo custom.',
            ]);
        }

        $branding->update([
            'is_active' => ! $branding->is_active,
        ]);

        return redirect()->route('superadmin.landing-settings.index', ['tab' => 'branding'])->with('flash', [
            'type' => 'success',
            'title' => 'Status logo diperbarui',
            'message' => $branding->is_active ? 'Logo custom diaktifkan.' : 'Logo custom dinonaktifkan (fallback ke default).',
        ]);
    }

    public function storeHeroMockup(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('landing_hero_mockups')) {
            return back()->with('flash', [
                'type' => 'danger',
                'title' => 'Tabel landing_hero_mockups belum tersedia',
                'message' => 'Jalankan migrate dulu agar mockup Hero dapat disimpan.',
            ]);
        }

        $validated = $request->validate([
            'badge' => ['nullable', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        LandingHeroMockup::query()->create([
            'badge' => $validated['badge'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'image_path' => $request->file('image')->storePublicly('landing/hero', 'public'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('superadmin.landing-settings.index', ['tab' => 'hero'])->with('flash', [
            'type' => 'success',
            'title' => 'Mockup Hero ditambahkan',
            'message' => 'PNG mockup produk sudah tampil di landing jika statusnya aktif.',
        ]);
    }

    public function updateHeroMockup(Request $request, LandingHeroMockup $landingHeroMockup): RedirectResponse
    {
        $validated = $request->validate([
            'badge' => ['nullable', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $oldPath = $landingHeroMockup->image_path;
        $imagePath = $oldPath;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->storePublicly('landing/hero', 'public');
        }

        $landingHeroMockup->update([
            'badge' => $validated['badge'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'image_path' => $imagePath,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($oldPath && $oldPath !== $imagePath) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->route('superadmin.landing-settings.index', ['tab' => 'hero'])->with('flash', [
            'type' => 'success',
            'title' => 'Mockup Hero diperbarui',
            'message' => 'Perubahan mockup produk sudah disimpan.',
        ]);
    }

    public function toggleHeroMockup(LandingHeroMockup $landingHeroMockup): RedirectResponse
    {
        $landingHeroMockup->update([
            'is_active' => ! $landingHeroMockup->is_active,
        ]);

        return redirect()->route('superadmin.landing-settings.index', ['tab' => 'hero'])->with('flash', [
            'type' => 'success',
            'title' => 'Status mockup diperbarui',
            'message' => $landingHeroMockup->is_active ? 'Mockup aktif.' : 'Mockup nonaktif.',
        ]);
    }

    public function destroyHeroMockup(LandingHeroMockup $landingHeroMockup): RedirectResponse
    {
        $imagePath = $landingHeroMockup->image_path;
        $landingHeroMockup->delete();

        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        return redirect()->route('superadmin.landing-settings.index', ['tab' => 'hero'])->with('flash', [
            'type' => 'success',
            'title' => 'Mockup Hero dihapus',
            'message' => 'Mockup produk sudah dihapus dari landing.',
        ]);
    }

    public function storeFaq(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('landing_faqs')) {
            return back()->with('flash', [
                'type' => 'danger',
                'title' => 'Tabel landing_faqs belum tersedia',
                'message' => 'Jalankan migrate dulu agar FAQ landing dapat disimpan.',
            ]);
        }

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:220'],
            'answer' => ['required', 'string', 'max:8000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        LandingFaq::query()->create([
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return redirect()->route('superadmin.landing-settings.index', ['tab' => 'faq'])->with('flash', [
            'type' => 'success',
            'title' => 'FAQ ditambahkan',
            'message' => 'Item FAQ baru sudah masuk ke landing.',
        ]);
    }

    public function updateFaq(Request $request, LandingFaq $landingFaq): RedirectResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:220'],
            'answer' => ['required', 'string', 'max:8000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $landingFaq->update([
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? $landingFaq->is_active),
        ]);

        return redirect()->route('superadmin.landing-settings.index', ['tab' => 'faq'])->with('flash', [
            'type' => 'success',
            'title' => 'FAQ diperbarui',
            'message' => 'Perubahan FAQ sudah disimpan.',
        ]);
    }

    public function toggleFaq(LandingFaq $landingFaq): RedirectResponse
    {
        $landingFaq->update([
            'is_active' => ! $landingFaq->is_active,
        ]);

        return redirect()->route('superadmin.landing-settings.index', ['tab' => 'faq'])->with('flash', [
            'type' => 'success',
            'title' => 'Status FAQ diperbarui',
            'message' => $landingFaq->is_active ? 'FAQ aktif.' : 'FAQ nonaktif.',
        ]);
    }

    public function destroyFaq(LandingFaq $landingFaq): RedirectResponse
    {
        $landingFaq->delete();

        return redirect()->route('superadmin.landing-settings.index', ['tab' => 'faq'])->with('flash', [
            'type' => 'success',
            'title' => 'FAQ dihapus',
            'message' => 'Item FAQ sudah dihapus dari database.',
        ]);
    }
}
