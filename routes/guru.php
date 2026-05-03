<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Guru\ExamResultController;
use App\Http\Controllers\RegisterGuruController;
use App\Http\Controllers\Guru\DashboardController;
use App\Http\Controllers\Guru\PaketSoalGuruController;
use App\Http\Controllers\Guru\ProfileController;
use App\Http\Controllers\Guru\MaterialController;
use App\Http\Controllers\Guru\MapelPaketGuruController;
use App\Http\Controllers\Guru\PersonalQuestionController;
use App\Http\Controllers\Guru\ExamController;
use App\Http\Controllers\Guru\ChatController;
use App\Http\Controllers\Guru\SoalGuruController;
use App\Http\Controllers\Guru\SoalUjionController;
use App\Http\Controllers\Guru\TeksBacaanGuruController;
use App\Http\Controllers\Guru\MaterialPracticePdfController;
use App\Http\Controllers\Guru\MaterialPracticeResultController;
use App\Http\Controllers\ChatImageController as SharedChatImageController;

Route::get('/register/guru', [RegisterGuruController::class, 'showForm'])->name('register.guru.form');
Route::post('/register/guru', [RegisterGuruController::class, 'register'])->name('register.guru');
Route::get('/register/guru/pending', [RegisterGuruController::class, 'showPending'])->name('register.guru.pending');
Route::get('/register/guru/check-wa', [RegisterGuruController::class, 'checkWa'])
    ->middleware('throttle:30,1')
    ->name('register.guru.check-wa');
Route::get('/register/guru/check-email', [RegisterGuruController::class, 'checkEmail'])
    ->middleware('throttle:30,1')
    ->name('register.guru.check-email');
Route::post('/register/guru/pending/resume', [RegisterGuruController::class, 'resumePending'])->name('register.guru.pending.resume');
Route::post('/register/guru/pending/payment', [RegisterGuruController::class, 'createPayment'])->name('register.guru.create-payment');
Route::post('/register/guru/pending/payment-data', [RegisterGuruController::class, 'paymentData'])->name('register.guru.payment-data');
Route::post('/register/guru/pending/payment-proof', [RegisterGuruController::class, 'uploadPaymentProof'])->name('register.guru.payment-proof');

Route::middleware(['auth', 'role:guru', 'guru.active'])->prefix('guru')->name('guru.')->scopeBindings()->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/materials', [MaterialController::class, 'index'])->name('materials');
    Route::get('/materials/{material}', [MaterialController::class, 'show'])->name('materials.show');
    Route::post('/materials/{material}/bookmark', [MaterialController::class, 'bookmark'])->name('materials.bookmark');
    Route::post('/materials/{material}/unbookmark', [MaterialController::class, 'unbookmark'])->name('materials.unbookmark');
    Route::get('/personal-questions', [PersonalQuestionController::class, 'index'])->name('personal-questions');
    Route::post('/personal-questions', [PersonalQuestionController::class, 'store'])->name('personal-questions.store');
    Route::get('/personal-questions/builder', [PersonalQuestionController::class, 'builder'])->name('personal-questions.builder');
    Route::post('/personal-questions/builder/save', [PersonalQuestionController::class, 'saveBuilder'])->name('personal-questions.builder.save');
    Route::post('/personal-questions/builder/upload-image', [PersonalQuestionController::class, 'uploadBuilderImage'])->name('personal-questions.builder.upload-image');
    Route::get('/personal-questions/builder/image', [PersonalQuestionController::class, 'builderImage'])->name('personal-questions.builder.image');
    Route::post('/personal-questions/{question}', [PersonalQuestionController::class, 'update'])
        ->whereNumber('question')
        ->name('personal-questions.update');
    Route::post('/personal-questions/{question}/destroy', [PersonalQuestionController::class, 'destroy'])
        ->whereNumber('question')
        ->name('personal-questions.destroy');
    Route::get('/exams', [ExamController::class, 'index'])->name('exams');
    Route::post('/exams/join', [ExamController::class, 'join'])->name('exams.join');
    Route::get('/exams/{exam}/result', [ExamController::class, 'result'])->name('exams.result');

    // Student Results Analysis Routes
    Route::get('/results', [ExamResultController::class, 'index'])->name('results.index');

    // Latihan Materi Results (static routes before /results/{exam})
    Route::get('/results/latihan-materi', [MaterialPracticeResultController::class, 'index'])->name('results.practice.index');
    Route::get('/results/latihan-materi/{material}', [MaterialPracticeResultController::class, 'show'])->name('results.practice.show');
    Route::get('/results/latihan-materi/{material}/session/{session}', [MaterialPracticeResultController::class, 'student'])
        ->withoutScopedBindings()
        ->name('results.practice.student');
    Route::get('/results/latihan-materi/{material}/session/{session}/paket/{attempt}/pdf', [MaterialPracticePdfController::class, 'downloadStudentPackage'])
        ->whereNumber(['session', 'attempt'])
        ->withoutScopedBindings()
        ->name('results.practice.package-pdf');

    Route::get('/results/session/{session}', [ExamResultController::class, 'studentDetail'])->name('results.student');
    Route::get('/results/{exam}', [ExamResultController::class, 'show'])->name('results.show');
    Route::get('/results/{exam}/mapel/{mapel}', [ExamResultController::class, 'mapel'])->name('results.mapel');
    Route::get('/results/{exam}/mapel/{mapel}/export', [ExamResultController::class, 'export'])->name('results.export');

    // Latihan Materi PDF (guru only)
    Route::get('/materials/{material}/latihan/paket/{paketNo}/pdf', [MaterialPracticePdfController::class, 'downloadPackage'])
        ->whereNumber('paketNo')
        ->name('materials.practice.pdf');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat');
    Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');
    Route::get('/chat/{chat}/image', [SharedChatImageController::class, 'show'])->name('chat.image');
    Route::get('/paket-soal', [PaketSoalGuruController::class, 'index'])->name('paket-soal.index');
    Route::get('/paket-soal/{paket}', [PaketSoalGuruController::class, 'show'])->name('paket-soal.show');
    Route::get('/soal-ujion', [SoalUjionController::class, 'index'])->name('soal-ujion.index');
    Route::get('/soal-ujion/{question}', [SoalUjionController::class, 'show'])->name('soal-ujion.show');
    Route::post('/soal-ujion/{question}/bookmark', [SoalUjionController::class, 'bookmark'])->name('soal-ujion.bookmark');
    Route::post('/soal-ujion/{question}/unbookmark', [SoalUjionController::class, 'unbookmark'])->name('soal-ujion.unbookmark');
    Route::middleware('guru.jenjang')->group(function () {
        Route::get('/paket-soal/{paket}/mapel/{mapel}/soal', [SoalGuruController::class, 'index'])->name('soal.index');
        Route::post('/paket-soal/{paket}/mapel/{mapel}/soal/import-ujion', [SoalGuruController::class, 'importFromUjion'])->name('soal.import-ujion');
        Route::get('/paket-soal/{paket}/mapel/{mapel}/soal/create', [SoalGuruController::class, 'create'])->name('soal.create');
        Route::post('/paket-soal/{paket}/mapel/{mapel}/soal', [SoalGuruController::class, 'store'])->name('soal.store');
        Route::get('/paket-soal/{paket}/mapel/{mapel}/soal/{soal}/edit', [SoalGuruController::class, 'edit'])->name('soal.edit');
        Route::put('/paket-soal/{paket}/mapel/{mapel}/soal/{soal}', [SoalGuruController::class, 'update'])->name('soal.update');
        Route::delete('/paket-soal/{paket}/mapel/{mapel}/soal/{soal}', [SoalGuruController::class, 'destroy'])->name('soal.destroy');

        Route::get('/paket-soal/{paket}/mapel/{mapel}/teks-bacaan', [TeksBacaanGuruController::class, 'index'])->name('teks-bacaan.index');
        Route::post('/paket-soal/{paket}/mapel/{mapel}/teks-bacaan', [TeksBacaanGuruController::class, 'store'])->name('teks-bacaan.store');
        Route::put('/paket-soal/{paket}/mapel/{mapel}/teks-bacaan/{bacaan}', [TeksBacaanGuruController::class, 'update'])->name('teks-bacaan.update');
        Route::delete('/paket-soal/{paket}/mapel/{mapel}/teks-bacaan/{bacaan}', [TeksBacaanGuruController::class, 'destroy'])->name('teks-bacaan.destroy');
        Route::put('/paket-soal/{paket}/mapel/{mapel}', [MapelPaketGuruController::class, 'update'])->name('mapel.update');
    });
    Route::get('/guide', function () {
        return view('guru.guide');
    })->name('guide');
});
