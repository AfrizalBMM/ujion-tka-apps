<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\Superadmin\AuditLogController;
use App\Http\Controllers\Superadmin\DashboardController;
use App\Http\Controllers\Superadmin\GlobalQuestionController;
use App\Http\Controllers\Superadmin\MaterialController;
use App\Http\Controllers\Superadmin\MaterialPracticeController;
use App\Http\Controllers\Superadmin\MapelPaketController as SuperadminMapelPaketController;
use App\Http\Controllers\Superadmin\PaketSoalController;
use App\Http\Controllers\Superadmin\PricingPlanController;
use App\Http\Controllers\Superadmin\PaymentConfirmationController;
use App\Http\Controllers\Superadmin\LandingSettingsController;
use App\Http\Controllers\Superadmin\SoalController as SuperadminSoalController;
use App\Http\Controllers\Superadmin\TeksBacaanController as SuperadminTeksBacaanController;
use App\Http\Controllers\Superadmin\TeacherController;
use App\Http\Controllers\Superadmin\ProfileController as SuperadminProfileController;
use App\Http\Controllers\PaymentController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\OgImageController;

use App\Http\Controllers\Siswa\AuthController as SiswaAuthController;
use App\Http\Controllers\Siswa\MaterialPracticeAuthController;
use App\Http\Controllers\Siswa\MaterialPracticeController as SiswaMaterialPracticeController;
use App\Http\Controllers\Siswa\ExamController;

use App\Http\Controllers\AuthController as GeneralAuthController;

Route::get('/', [LandingController::class , 'index'])->name('landing');
Route::get('/payments/{referenceCode}', [PaymentController::class, 'show'])->name('payments.show');

Route::get('/og-image.png', OgImageController::class)->name('og.image');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
// Auth routes for Guru
Route::get('/login', [GeneralAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [GeneralAuthController::class, 'login'])->middleware('throttle:5,1');
Route::get('/lupa-token', [GeneralAuthController::class, 'showForgotTokenForm'])->name('guru.token-request.form');
Route::post('/lupa-token', [GeneralAuthController::class, 'requestForgotToken'])->name('guru.token-request.send');
Route::post('/logout', [GeneralAuthController::class, 'logout'])->name('logout');

// Auth routes for Superadmin (Ngadimin)
Route::get('/ngadimin/login', [GeneralAuthController::class, 'showAdminLoginForm'])->name('admin.login');
Route::post('/ngadimin/login', [GeneralAuthController::class, 'adminLogin']);

// Siswa routes
Route::get('/siswa/login', [SiswaAuthController::class , 'showLoginForm'])->name('siswa.login');
Route::post('/siswa/login', [SiswaAuthController::class , 'validateToken'])
	->middleware('throttle:10,1')
	->name('siswa.token.validate');
Route::get('/siswa/identitas', function () {
	return view('siswa.identitas');
})->name('siswa.identitas');

Route::post('/siswa/mulai', [ExamController::class , 'mulai'])->name('siswa.mulai');
Route::get('/siswa/petunjuk', [ExamController::class , 'petunjuk'])->name('siswa.petunjuk');
Route::get('/siswa/ujian', [ExamController::class , 'showUjian'])->name('siswa.ujian');
Route::post('/siswa/api/save-answer', [ExamController::class , 'apiSaveAnswer'])->name('siswa.api.save_answer');
Route::get('/siswa/selesai', [ExamController::class , 'selesai'])->name('siswa.selesai');

// Siswa - Latihan Materi (Telaah + Paket Latihan)
Route::get('/siswa/latihan/login', [MaterialPracticeAuthController::class, 'showLoginForm'])->name('siswa.practice.login');
Route::post('/siswa/latihan/login', [MaterialPracticeAuthController::class, 'validateToken'])
	->middleware('throttle:10,1')
	->name('siswa.practice.token.validate');
Route::get('/siswa/latihan/identitas', function () {
	return view('siswa.practice.identitas');
})->name('siswa.practice.identitas');
Route::post('/siswa/latihan/mulai', [SiswaMaterialPracticeController::class, 'mulai'])->name('siswa.practice.mulai');
Route::get('/siswa/latihan', [SiswaMaterialPracticeController::class, 'dashboard'])->name('siswa.practice.dashboard');
Route::post('/siswa/latihan/telaah/{globalQuestion}', [SiswaMaterialPracticeController::class, 'submitTelaah'])->name('siswa.practice.telaah.submit');
Route::get('/siswa/latihan/paket/{paketNo}', [SiswaMaterialPracticeController::class, 'showPaket'])->whereNumber('paketNo')->name('siswa.practice.paket.show');
Route::post('/siswa/latihan/paket/{paketNo}', [SiswaMaterialPracticeController::class, 'submitPaket'])->whereNumber('paketNo')->name('siswa.practice.paket.submit');

// Materi / Latihan (URL khusus, terpisah dari ujian)
Route::prefix('materi')->name('materi.')->group(function () {
	Route::get('/login', [MaterialPracticeAuthController::class, 'showLoginForm'])->name('login');
	Route::post('/login', [MaterialPracticeAuthController::class, 'validateToken'])
		->middleware('throttle:10,1')
		->name('token.validate');
	Route::get('/identitas', function () {
		return view('siswa.practice.identitas');
	})->name('identitas');
	Route::post('/mulai', [SiswaMaterialPracticeController::class, 'mulai'])->name('mulai');
	Route::get('/', [SiswaMaterialPracticeController::class, 'dashboard'])->name('dashboard');
	Route::post('/telaah/{globalQuestion}', [SiswaMaterialPracticeController::class, 'submitTelaah'])->name('telaah.submit');
	Route::get('/paket/{paketNo}', [SiswaMaterialPracticeController::class, 'showPaket'])->whereNumber('paketNo')->name('paket.show');
	Route::post('/paket/{paketNo}', [SiswaMaterialPracticeController::class, 'submitPaket'])->whereNumber('paketNo')->name('paket.submit');
});

Route::prefix('superadmin')
	->name('superadmin.')
	->middleware(['auth', 'role:superadmin', 'audit'])
	->scopeBindings()
	->group(function () {
	    Route::get('/', [DashboardController::class , 'index'])->name('dashboard');

	    Route::get('/landing-settings', [LandingSettingsController::class, 'index'])->name('landing-settings.index');
	    Route::post('/landing-settings/content', [LandingSettingsController::class, 'saveContent'])->name('landing-settings.content');
	    Route::post('/landing-settings/sections/{section}/toggle', [LandingSettingsController::class, 'toggleSection'])->name('landing-settings.sections.toggle');
	    Route::post('/landing-settings/branding/logo', [LandingSettingsController::class, 'saveLogo'])->name('landing-settings.logo');
	    Route::post('/landing-settings/branding/toggle', [LandingSettingsController::class, 'toggleBranding'])->name('landing-settings.branding.toggle');
	    Route::post('/landing-settings/hero/mockups', [LandingSettingsController::class, 'storeHeroMockup'])->name('landing-settings.hero-mockups.store');
	    Route::post('/landing-settings/hero/mockups/{landingHeroMockup}', [LandingSettingsController::class, 'updateHeroMockup'])->name('landing-settings.hero-mockups.update');
	    Route::post('/landing-settings/hero/mockups/{landingHeroMockup}/toggle', [LandingSettingsController::class, 'toggleHeroMockup'])->name('landing-settings.hero-mockups.toggle');
	    Route::post('/landing-settings/hero/mockups/{landingHeroMockup}/delete', [LandingSettingsController::class, 'destroyHeroMockup'])->name('landing-settings.hero-mockups.destroy');
	    Route::post('/landing-settings/faq', [LandingSettingsController::class, 'storeFaq'])->name('landing-settings.faq.store');
	    Route::post('/landing-settings/faq/{landingFaq}', [LandingSettingsController::class, 'updateFaq'])->name('landing-settings.faq.update');
	    Route::post('/landing-settings/faq/{landingFaq}/toggle', [LandingSettingsController::class, 'toggleFaq'])->name('landing-settings.faq.toggle');
	    Route::post('/landing-settings/faq/{landingFaq}/delete', [LandingSettingsController::class, 'destroyFaq'])->name('landing-settings.faq.destroy');
	    Route::get('/profile', [SuperadminProfileController::class , 'show'])->name('profile');
	    Route::post('/profile', [SuperadminProfileController::class , 'update'])->name('profile.update');
	    Route::post('/profile/password', [SuperadminProfileController::class , 'password'])->name('profile.password');

	    Route::get('/finance', [\App\Http\Controllers\Superadmin\FinanceController::class , 'index'])->name('finance.index');
	    Route::post('/finance/settings', [\App\Http\Controllers\Superadmin\FinanceController::class , 'saveSettings'])->name('finance.settings');
	    Route::get('/payment-confirmations', [PaymentConfirmationController::class, 'index'])->name('payment-confirmations.index');
	    Route::post('/payment-confirmations/{transaction}/approve', [PaymentConfirmationController::class, 'approve'])->name('payment-confirmations.approve');
	    Route::post('/payment-confirmations/{transaction}/reject', [PaymentConfirmationController::class, 'reject'])->name('payment-confirmations.reject');

	    Route::post('/tarif-jenjang', [PricingPlanController::class , 'store'])->name('tarif-jenjang.store');
	    Route::post('/tarif-jenjang/{pricingPlan}', [PricingPlanController::class , 'update'])->name('tarif-jenjang.update');
	    Route::post('/tarif-jenjang/{pricingPlan}/toggle-active', [PricingPlanController::class , 'toggleActive'])->name('tarif-jenjang.toggle-active');
	    Route::get('/tarif-jenjang/{pricingPlan}/print', [PricingPlanController::class , 'printLabel'])->name('tarif-jenjang.print');
	    Route::get('/tarif-jenjang/{pricingPlan}/image', [PricingPlanController::class , 'image'])->name('tarif-jenjang.image');
	    Route::post('/tarif-jenjang/{pricingPlan}/delete', [PricingPlanController::class , 'destroy'])->name('tarif-jenjang.destroy');

	    Route::post('/teachers/{teacher}/activate', [TeacherController::class , 'activate'])->name('teachers.activate');
	    Route::post('/teachers/{teacher}/suspend', [TeacherController::class , 'suspend'])->name('teachers.suspend');
	    Route::post('/teachers/{teacher}/refresh-token', [TeacherController::class , 'refreshToken'])->name('teachers.refresh-token');
	    Route::post('/teachers/{teacher}/approve-payment', [TeacherController::class , 'approvePayment'])->name('teachers.approve-payment');
	    Route::post('/teachers/{teacher}/reject-payment', [TeacherController::class , 'rejectPayment'])->name('teachers.reject-payment');

	    Route::post('/materials', [MaterialController::class , 'store'])->name('materials.store');
	    Route::post('/materials/import', [MaterialController::class , 'import'])->name('materials.import');
	    Route::get('/materials/template', [MaterialController::class , 'template'])->name('materials.template');
		Route::post('/materials/{material}/delete', [MaterialController::class , 'destroy'])->name('materials.destroy');
		Route::post('/materials/destroy-all', [MaterialController::class, 'destroyAll'])->name('materials.destroyAll');

		// Latihan Materi (Telaah + Paket Latihan)
		Route::get('/materials/{material}/practice', [MaterialPracticeController::class, 'show'])->name('materials.practice.show');
		Route::post('/materials/{material}/practice/telaah', [MaterialPracticeController::class, 'saveTelaah'])->name('materials.practice.telaah');
		Route::post('/materials/{material}/practice/token', [MaterialPracticeController::class, 'upsertToken'])->name('materials.practice.token');
		Route::post('/materials/{material}/practice/packages/regenerate', [MaterialPracticeController::class, 'regeneratePackages'])->name('materials.practice.packages.regenerate');

	    Route::get('/global-questions', [GlobalQuestionController::class , 'index'])->name('global-questions.index');
	    Route::post('/global-questions', [GlobalQuestionController::class , 'store'])->name('global-questions.store');
	    Route::post('/global-questions/import', [GlobalQuestionController::class , 'import'])->name('global-questions.import');
	    Route::post('/global-questions/import-pg', [GlobalQuestionController::class, 'importPG'])->name('global-questions.import-pg');
	    Route::post('/global-questions/import-menjodohkan', [GlobalQuestionController::class, 'importMenjodohkan'])->name('global-questions.import-menjodohkan');
	    Route::get('/global-questions/template', [GlobalQuestionController::class , 'template'])->name('global-questions.template');
	    Route::get('/global-questions/template-pg', [GlobalQuestionController::class, 'templatePG'])->name('global-questions.template-pg');
	    Route::get('/global-questions/template-menjodohkan', [GlobalQuestionController::class, 'templateMenjodohkan'])->name('global-questions.template-menjodohkan');
	    Route::post('/global-questions/destroy-all', [GlobalQuestionController::class , 'destroyAll'])->name('global-questions.destroyAll');
	    Route::post('/global-questions/{globalQuestion}', [GlobalQuestionController::class , 'update'])->name('global-questions.update');
	    Route::post('/global-questions/{globalQuestion}/delete', [GlobalQuestionController::class , 'destroy'])->name('global-questions.destroy');

	    Route::get('/audit-logs', [AuditLogController::class , 'index'])->name('audit-logs.index');
        Route::get('/dashboard/export/csv', [DashboardController::class, 'exportCsv'])->name('dashboard.export-csv');
        Route::get('/dashboard/print', [DashboardController::class, 'print'])->name('dashboard.print');

 	    Route::get('/chat', [\App\Http\Controllers\Superadmin\ChatController::class , 'index'])->name('chat.index');
 	    Route::post('/chat', [\App\Http\Controllers\Superadmin\ChatController::class , 'store'])->name('chat.store');
	    Route::get('/chat/{chat}/image', [\App\Http\Controllers\ChatImageController::class, 'show'])->name('chat.image');
		Route::post('/chat/{chat}/destroy', [\App\Http\Controllers\Superadmin\ChatController::class , 'destroy'])->name('chat.destroy');
		Route::post('/chat/{user}/destroy-all', [\App\Http\Controllers\Superadmin\ChatController::class , 'destroyAll'])->name('chat.destroyAll');
		Route::post('/chat/destroy-all-guru', [\App\Http\Controllers\Superadmin\ChatController::class , 'destroyAllGuru'])->name('chat.destroyAllGuru');
 	    Route::post('/chat/{chat}/read', [\App\Http\Controllers\Superadmin\ChatController::class , 'markRead'])->name('chat.read');

	    Route::get('/teachers', [TeacherController::class , 'index'])->name('teachers.index');
	    Route::get('/materials', [MaterialController::class , 'index'])->name('materials.index');

	    Route::get('/questions', function (): RedirectResponse {
            return redirect()->route('superadmin.global-questions.index');
        })->name('questions.index');

	    Route::get('/exams', [\App\Http\Controllers\Superadmin\ExamController::class , 'index'])->name('exams.index');
	    Route::post('/exams', [\App\Http\Controllers\Superadmin\ExamController::class , 'store'])->name('exams.store');
	    Route::post('/exams/import', [\App\Http\Controllers\Superadmin\ExamController::class , 'import'])->name('exams.import');
	    Route::get('/exams/template', [\App\Http\Controllers\Superadmin\ExamController::class , 'template'])->name('exams.template');
	    Route::post('/exams/{exam}/destroy', [\App\Http\Controllers\Superadmin\ExamController::class , 'destroy'])->name('exams.destroy');
	    Route::post('/exams/{exam}/toggle', [\App\Http\Controllers\Superadmin\ExamController::class , 'toggle'])->name('exams.toggle');
	    Route::get('/exams/{exam}/builder', [\App\Http\Controllers\Superadmin\ExamController::class , 'builder'])->name('exams.builder');
	    Route::get('/exams/{exam}/bank-questions', [\App\Http\Controllers\Superadmin\ExamController::class , 'bankQuestions'])->name('exams.bank-questions');
	    Route::post('/exams/{exam}/builder/save', [\App\Http\Controllers\Superadmin\ExamController::class , 'saveBuilder'])->name('exams.builder.save');
	    Route::get('/exams/{exam}', [\App\Http\Controllers\Superadmin\ExamController::class , 'show'])->name('exams.show');
	    Route::post('/exams/{exam}/import-bank', [\App\Http\Controllers\Superadmin\ExamController::class , 'importBankQuestions'])->name('exams.import-bank');
	    Route::get('/exams/{exam}/analysis', [\App\Http\Controllers\Superadmin\ExamAnalysisController::class , 'show'])->name('exams.analysis');
        Route::get('/exams/{exam}/analysis/export/csv', [\App\Http\Controllers\Superadmin\ExamAnalysisController::class, 'exportCsv'])->name('exams.analysis.export-csv');
        Route::get('/exams/{exam}/analysis/print', [\App\Http\Controllers\Superadmin\ExamAnalysisController::class, 'print'])->name('exams.analysis.print');

        Route::get('/paket-soal', [PaketSoalController::class, 'index'])->name('paket-soal.index');
        Route::get('/paket-soal/create', [PaketSoalController::class, 'create'])->name('paket-soal.create');
        Route::post('/paket-soal', [PaketSoalController::class, 'store'])->name('paket-soal.store');
        Route::get('/paket-soal/{paket}', [PaketSoalController::class, 'show'])->name('paket-soal.show');
        Route::get('/paket-soal/{paket}/edit', [PaketSoalController::class, 'edit'])->name('paket-soal.edit');
        Route::put('/paket-soal/{paket}', [PaketSoalController::class, 'update'])->name('paket-soal.update');
        Route::delete('/paket-soal/{paket}', [PaketSoalController::class, 'destroy'])->name('paket-soal.destroy');
        Route::patch('/paket-soal/{paket}/toggle', [PaketSoalController::class, 'toggleAktif'])->name('paket-soal.toggle');
        Route::put('/paket-soal/{paket}/mapel/{mapel}', [SuperadminMapelPaketController::class, 'update'])->name('mapel.update');
        Route::delete('/paket-soal/{paket}/mapel/{mapel}/soal-all', [SuperadminMapelPaketController::class, 'destroyAllSoals'])->name('mapel.soal.destroy-all');

        Route::get('/paket-soal/{paket}/mapel/{mapel}/soal', [SuperadminSoalController::class, 'index'])->name('soal.index');
        Route::get('/paket-soal/{paket}/mapel/{mapel}/soal/create', [SuperadminSoalController::class, 'create'])->name('soal.create');
        Route::post('/paket-soal/{paket}/mapel/{mapel}/soal', [SuperadminSoalController::class, 'store'])->name('soal.store');
        Route::get('/paket-soal/{paket}/mapel/{mapel}/soal/{soal}/edit', [SuperadminSoalController::class, 'edit'])->name('soal.edit');
        Route::put('/paket-soal/{paket}/mapel/{mapel}/soal/{soal}', [SuperadminSoalController::class, 'update'])->name('soal.update');
        Route::delete('/paket-soal/{paket}/mapel/{mapel}/soal/{soal}', [SuperadminSoalController::class, 'destroy'])->name('soal.destroy');

        Route::get('/paket-soal/{paket}/mapel/{mapel}/bank-builder', [SuperadminSoalController::class, 'bankBuilder'])->name('soal.bank-builder');
        Route::post('/paket-soal/{paket}/mapel/{mapel}/bank-builder/import', [SuperadminSoalController::class, 'importFromBank'])->name('soal.import-from-bank');

        Route::get('/paket-soal/{paket}/mapel/{mapel}/teks-bacaan', [SuperadminTeksBacaanController::class, 'index'])->name('teks-bacaan.index');
        Route::post('/paket-soal/{paket}/mapel/{mapel}/teks-bacaan', [SuperadminTeksBacaanController::class, 'store'])->name('teks-bacaan.store');
        Route::put('/paket-soal/{paket}/mapel/{mapel}/teks-bacaan/{bacaan}', [SuperadminTeksBacaanController::class, 'update'])->name('teks-bacaan.update');
        Route::delete('/paket-soal/{paket}/mapel/{mapel}/teks-bacaan/{bacaan}', [SuperadminTeksBacaanController::class, 'destroy'])->name('teks-bacaan.destroy');

	    Route::get('/guide', function () {
		    return view('superadmin.guide'); }
	    )->name('guide');
    });
