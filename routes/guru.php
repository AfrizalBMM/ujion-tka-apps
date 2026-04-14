<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegisterGuruController;
use App\Http\Controllers\Guru\DashboardController;
use App\Http\Controllers\Guru\ProfileController;
use App\Http\Controllers\Guru\MaterialController;
use App\Http\Controllers\Guru\PersonalQuestionController;
use App\Http\Controllers\Guru\ExamController;
use App\Http\Controllers\Guru\ChatController;
use App\Http\Controllers\Guru\LogController;

Route::get('/register/guru', [RegisterGuruController::class, 'showForm'])->name('register.guru.form');
Route::post('/register/guru', [RegisterGuruController::class, 'register'])->name('register.guru');

Route::middleware(['auth','role:guru'])->prefix('guru')->name('guru.')->group(function() {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::get('/materials', [MaterialController::class, 'index'])->name('materials');
    Route::post('/materials/{material}/bookmark', [MaterialController::class, 'bookmark'])->name('materials.bookmark');
    Route::post('/materials/{material}/unbookmark', [MaterialController::class, 'unbookmark'])->name('materials.unbookmark');
    Route::get('/personal-questions', [PersonalQuestionController::class, 'index'])->name('personal-questions');
    Route::post('/personal-questions', [PersonalQuestionController::class, 'store'])->name('personal-questions.store');
    Route::post('/personal-questions/{question}/destroy', [PersonalQuestionController::class, 'destroy'])->name('personal-questions.destroy');
    Route::get('/personal-questions/builder', [PersonalQuestionController::class, 'builder'])->name('personal-questions.builder');
    Route::post('/personal-questions/builder/save', [PersonalQuestionController::class, 'saveBuilder'])->name('personal-questions.builder.save');
    Route::get('/exams', [ExamController::class, 'index'])->name('exams');
    Route::post('/exams/join', [ExamController::class, 'join'])->name('exams.join');
    Route::get('/exams/{exam}/result', [ExamController::class, 'result'])->name('exams.result');
    Route::get('/chat', [ChatController::class, 'index'])->name('chat');
    Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');
    Route::get('/logs', [LogController::class, 'index'])->name('logs');
    Route::get('/guide', function() { return view('guru.guide'); })->name('guide');
});
