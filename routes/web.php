<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\TrainerApprovalController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\TrainingProviderController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');

    // الدورات: الكتابة لمن يملك إدارة الدورات، والقراءة لمن يملك العرض أو الإدارة.
    Route::middleware('permission.any:COURSES_MANAGE')->group(function () {
        Route::get('courses/create', [CourseController::class, 'create'])->name('courses.create');
        Route::post('courses', [CourseController::class, 'store'])->name('courses.store');
        Route::get('courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
        Route::match(['put', 'patch'], 'courses/{course}', [CourseController::class, 'update'])->name('courses.update');
        Route::delete('courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
    });

    Route::middleware('permission.any:COURSES_VIEW,COURSES_MANAGE')->group(function () {
        Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
        Route::get('courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    });

    // القوائم المرجعية: تصنيفات وأنواع الدورات، معايير الالتحاق، المستويات التعليمية والدرجات الوظيفية.
    Route::middleware('permission.any:LOOKUPS_MANAGE')->group(function () {
        Route::get('lookups', [LookupController::class, 'index'])->name('lookups.index');
        Route::post('lookups/{type}', [LookupController::class, 'store'])
            ->name('lookups.store')
            ->whereIn('type', array_keys(LookupController::TYPES));
        Route::put('lookups/{type}/{lookup}', [LookupController::class, 'update'])
            ->name('lookups.update')
            ->whereIn('type', array_keys(LookupController::TYPES))
            ->whereNumber('lookup');
        Route::delete('lookups/{type}/{lookup}', [LookupController::class, 'destroy'])
            ->name('lookups.destroy')
            ->whereIn('type', array_keys(LookupController::TYPES))
            ->whereNumber('lookup');
    });

    // المدربون: إدارة الملفات لمن يملك TRAINERS_MANAGE.
    Route::resource('trainers', TrainerController::class)
        ->middleware('permission.any:TRAINERS_MANAGE');

    // جهات التدريب: إدارة الجهات المرتبطة بالمدربين الخارجيين والجلسات.
    Route::resource('providers', TrainingProviderController::class)
        ->middleware('permission.any:PROVIDERS_MANAGE');

    // اعتماد المدرب لتدريس دورة: صلاحية مستقلة عن إدارة ملف المدرب.
    Route::middleware('permission.any:TRAINER_APPROVE_COURSE')->group(function () {
        Route::post('trainers/{trainer}/approvals', [TrainerApprovalController::class, 'store'])
            ->name('trainers.approvals.store');
        Route::delete('trainers/{trainer}/approvals/{approval}', [TrainerApprovalController::class, 'destroy'])
            ->name('trainers.approvals.destroy');
    });
});
