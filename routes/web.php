<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\Superadmin\AuditLogController;
use App\Http\Controllers\Superadmin\DashboardController;
use App\Http\Controllers\Superadmin\GlobalQuestionController;
use App\Http\Controllers\Superadmin\MaterialController;
use App\Http\Controllers\Superadmin\MapelPaketController as SuperadminMapelPaketController;
use App\Http\Controllers\Superadmin\PaymentQrController;
use App\Http\Controllers\Superadmin\PaketSoalController;
use App\Http\Controllers\Superadmin\PricingPlanController;
use App\Http\Controllers\Superadmin\SoalController as SuperadminSoalController;
use App\Http\Controllers\Superadmin\TeksBacaanController as SuperadminTeksBacaanController;
use App\Http\Controllers\Superadmin\TeacherController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Siswa\AuthController as SiswaAuthController;
use App\Http\Controllers\Siswa\ExamController;

use App\Http\Controllers\AuthController as GeneralAuthController;

Route::get('/', [LandingController::class , 'index'])->name('landing');

// Auth routes for Guru
Route::get('/login', [GeneralAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [GeneralAuthController::class, 'login']);
Route::post('/logout', [GeneralAuthController::class, 'logout'])->name('logout');

// Auth routes for Superadmin (Ngadimin)
Route::get('/ngadimin/login', [GeneralAuthController::class, 'showAdminLoginForm'])->name('admin.login');
Route::post('/ngadimin/login', [GeneralAuthController::class, 'adminLogin']);

// Siswa routes
Route::get('/siswa/login', [SiswaAuthController::class , 'showLoginForm'])->name('siswa.login');
Route::post('/siswa/login', [SiswaAuthController::class , 'validateToken'])->name('siswa.token.validate');
Route::get('/siswa/identitas', function () {
	return view('siswa.identitas');
})->name('siswa.identitas');

Route::post('/siswa/mulai', [ExamController::class , 'mulai'])->name('siswa.mulai');
Route::get('/siswa/petunjuk', [ExamController::class , 'petunjuk'])->name('siswa.petunjuk');
Route::get('/siswa/ujian', [ExamController::class , 'showUjian'])->name('siswa.ujian');
Route::post('/siswa/api/save-answer', [ExamController::class , 'apiSaveAnswer'])->name('siswa.api.save_answer');
Route::get('/siswa/selesai', [ExamController::class , 'selesai'])->name('siswa.selesai');

Route::prefix('superadmin')
	->name('superadmin.')
	->middleware('audit')
	->scopeBindings()
	->group(function () {
	    Route::get('/', [DashboardController::class , 'index'])->name('dashboard');

	    Route::get('/finance', [\App\Http\Controllers\Superadmin\FinanceController::class , 'index'])->name('finance.index');
	    Route::post('/payment-qrs', [PaymentQrController::class , 'store'])->name('payment-qrs.store');
	    Route::post('/payment-qrs/{paymentQr}', [PaymentQrController::class , 'update'])->name('payment-qrs.update');
	    Route::post('/payment-qrs/{paymentQr}/toggle', [PaymentQrController::class , 'toggle'])->name('payment-qrs.toggle');
	    Route::post('/payment-qrs/{paymentQr}/delete', [PaymentQrController::class , 'destroy'])->name('payment-qrs.destroy');

	    Route::post('/pricing-plans', [PricingPlanController::class , 'store'])->name('pricing-plans.store');
	    Route::post('/pricing-plans/{pricingPlan}', [PricingPlanController::class , 'update'])->name('pricing-plans.update');
	    Route::post('/pricing-plans/{pricingPlan}/toggle-active', [PricingPlanController::class , 'toggleActive'])->name('pricing-plans.toggle-active');
	    Route::post('/pricing-plans/{pricingPlan}/toggle-promo', [PricingPlanController::class , 'togglePromo'])->name('pricing-plans.toggle-promo');
	    Route::post('/pricing-plans/{pricingPlan}/delete', [PricingPlanController::class , 'destroy'])->name('pricing-plans.destroy');

	    Route::post('/teachers/{teacher}/activate', [TeacherController::class , 'activate'])->name('teachers.activate');
	    Route::post('/teachers/{teacher}/suspend', [TeacherController::class , 'suspend'])->name('teachers.suspend');
	    Route::post('/teachers/{teacher}/refresh-token', [TeacherController::class , 'refreshToken'])->name('teachers.refresh-token');

	    Route::post('/materials', [MaterialController::class , 'store'])->name('materials.store');
	    Route::post('/materials/{material}/delete', [MaterialController::class , 'destroy'])->name('materials.destroy');

	    Route::get('/global-questions', [GlobalQuestionController::class , 'index'])->name('global-questions.index');
	    Route::post('/global-questions', [GlobalQuestionController::class , 'store'])->name('global-questions.store');
	    Route::post('/global-questions/{globalQuestion}/delete', [GlobalQuestionController::class , 'destroy'])->name('global-questions.destroy');
	    Route::post('/global-questions/import', [GlobalQuestionController::class , 'import'])->name('global-questions.import');
	    Route::get('/global-questions/template', [GlobalQuestionController::class , 'template'])->name('global-questions.template');

	    Route::get('/audit-logs', [AuditLogController::class , 'index'])->name('audit-logs.index');

	    Route::get('/chat', [\App\Http\Controllers\Superadmin\ChatController::class , 'index'])->name('chat.index');
	    Route::post('/chat', [\App\Http\Controllers\Superadmin\ChatController::class , 'store'])->name('chat.store');
	    Route::post('/chat/{chat}/destroy', [\App\Http\Controllers\Superadmin\ChatController::class , 'destroy'])->name('chat.destroy');
	    Route::post('/chat/{chat}/read', [\App\Http\Controllers\Superadmin\ChatController::class , 'markRead'])->name('chat.read');

	    Route::get('/teachers', [TeacherController::class , 'index'])->name('teachers.index');
	    Route::get('/materials', [MaterialController::class , 'index'])->name('materials.index');

	    Route::get('/questions', [\App\Http\Controllers\Superadmin\QuestionController::class , 'index'])->name('questions.index');
	    Route::post('/questions', [\App\Http\Controllers\Superadmin\QuestionController::class , 'store'])->name('questions.store');
	    Route::post('/questions/{question}/destroy', [\App\Http\Controllers\Superadmin\QuestionController::class , 'destroy'])->name('questions.destroy');
	    Route::post('/questions/{question}/toggle', [\App\Http\Controllers\Superadmin\QuestionController::class , 'toggle'])->name('questions.toggle');

	    Route::get('/exams', [\App\Http\Controllers\Superadmin\ExamController::class , 'index'])->name('exams.index');
	    Route::post('/exams', [\App\Http\Controllers\Superadmin\ExamController::class , 'store'])->name('exams.store');
	    Route::post('/exams/{exam}/destroy', [\App\Http\Controllers\Superadmin\ExamController::class , 'destroy'])->name('exams.destroy');
	    Route::post('/exams/{exam}/toggle', [\App\Http\Controllers\Superadmin\ExamController::class , 'toggle'])->name('exams.toggle');
	    Route::get('/exams/{exam}/builder', [\App\Http\Controllers\Superadmin\ExamController::class , 'builder'])->name('exams.builder');
	    Route::post('/exams/{exam}/builder/save', [\App\Http\Controllers\Superadmin\ExamController::class , 'saveBuilder'])->name('exams.builder.save');
	    Route::get('/exams/{exam}', [\App\Http\Controllers\Superadmin\ExamController::class , 'show'])->name('exams.show');
	    Route::post('/exams/{exam}/import-bank', [\App\Http\Controllers\Superadmin\ExamController::class , 'importBankQuestions'])->name('exams.import-bank');
	    Route::get('/exams/{exam}/analysis', [\App\Http\Controllers\Superadmin\ExamAnalysisController::class , 'show'])->name('exams.analysis');

        Route::get('/paket-soal', [PaketSoalController::class, 'index'])->name('paket-soal.index');
        Route::get('/paket-soal/create', [PaketSoalController::class, 'create'])->name('paket-soal.create');
        Route::post('/paket-soal', [PaketSoalController::class, 'store'])->name('paket-soal.store');
        Route::get('/paket-soal/{paket}', [PaketSoalController::class, 'show'])->name('paket-soal.show');
        Route::get('/paket-soal/{paket}/edit', [PaketSoalController::class, 'edit'])->name('paket-soal.edit');
        Route::put('/paket-soal/{paket}', [PaketSoalController::class, 'update'])->name('paket-soal.update');
        Route::delete('/paket-soal/{paket}', [PaketSoalController::class, 'destroy'])->name('paket-soal.destroy');
        Route::patch('/paket-soal/{paket}/toggle', [PaketSoalController::class, 'toggleAktif'])->name('paket-soal.toggle');
        Route::put('/paket-soal/{paket}/mapel/{mapel}', [SuperadminMapelPaketController::class, 'update'])->name('mapel.update');

        Route::get('/paket-soal/{paket}/mapel/{mapel}/soal', [SuperadminSoalController::class, 'index'])->name('soal.index');
        Route::get('/paket-soal/{paket}/mapel/{mapel}/soal/create', [SuperadminSoalController::class, 'create'])->name('soal.create');
        Route::post('/paket-soal/{paket}/mapel/{mapel}/soal', [SuperadminSoalController::class, 'store'])->name('soal.store');
        Route::get('/paket-soal/{paket}/mapel/{mapel}/soal/{soal}/edit', [SuperadminSoalController::class, 'edit'])->name('soal.edit');
        Route::put('/paket-soal/{paket}/mapel/{mapel}/soal/{soal}', [SuperadminSoalController::class, 'update'])->name('soal.update');
        Route::delete('/paket-soal/{paket}/mapel/{mapel}/soal/{soal}', [SuperadminSoalController::class, 'destroy'])->name('soal.destroy');

        Route::get('/paket-soal/{paket}/mapel/{mapel}/teks-bacaan', [SuperadminTeksBacaanController::class, 'index'])->name('teks-bacaan.index');
        Route::post('/paket-soal/{paket}/mapel/{mapel}/teks-bacaan', [SuperadminTeksBacaanController::class, 'store'])->name('teks-bacaan.store');
        Route::put('/paket-soal/{paket}/mapel/{mapel}/teks-bacaan/{bacaan}', [SuperadminTeksBacaanController::class, 'update'])->name('teks-bacaan.update');
        Route::delete('/paket-soal/{paket}/mapel/{mapel}/teks-bacaan/{bacaan}', [SuperadminTeksBacaanController::class, 'destroy'])->name('teks-bacaan.destroy');

	    Route::get('/guide', function () {
		    return view('superadmin.guide'); }
	    )->name('guide');
    });
