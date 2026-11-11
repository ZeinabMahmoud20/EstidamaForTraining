<?php

use App\Http\Controllers\AccreditationController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CertificateTemplateController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EvaluationFormController;
use App\Http\Controllers\EvaluationReportController;
use App\Http\Controllers\EvaluationSubmissionController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\HallBlockController;
use App\Http\Controllers\HallController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\NominationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\QuestionBankController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SessionHallController;
use App\Http\Controllers\SessionStatusController;
use App\Http\Controllers\TraineeController;
use App\Http\Controllers\TraineeWarningController;
use App\Http\Controllers\TrainerApprovalController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\TrainerPaymentController;
use App\Http\Controllers\TrainingProviderController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

// التحقق العلني من صحة الشهادة عبر رمز التحقق.
Route::get('certificates/verify/{code}', [CertificateController::class, 'verify'])->name('certificates.verify');

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

    // الجلسات المجدولة: الكتابة لمن يملك إدارة الجلسات، والقراءة لمن يديرها أو ينشرها أو يغيّر قاعاتها.
    Route::middleware('permission.any:SESSIONS_MANAGE')->group(function () {
        Route::get('sessions/create', [CourseSessionController::class, 'create'])->name('sessions.create');
        Route::post('sessions', [CourseSessionController::class, 'store'])->name('sessions.store');
        Route::get('sessions/{session}/edit', [CourseSessionController::class, 'edit'])->name('sessions.edit')->whereNumber('session');
        Route::match(['put', 'patch'], 'sessions/{session}', [CourseSessionController::class, 'update'])->name('sessions.update')->whereNumber('session');
        Route::delete('sessions/{session}', [CourseSessionController::class, 'destroy'])->name('sessions.destroy')->whereNumber('session');
    });

    Route::middleware('permission.any:SESSIONS_MANAGE,SESSIONS_PUBLISH,CHANGE_HALL')->group(function () {
        Route::get('sessions', [CourseSessionController::class, 'index'])->name('sessions.index');
        Route::get('sessions/{session}', [CourseSessionController::class, 'show'])->name('sessions.show')->whereNumber('session');
    });

    // نشر حالة الجلسة/إدارة دورة حياتها بصلاحية مستقلة عن تعديل الجدول.
    Route::middleware('permission.any:SESSIONS_PUBLISH')->group(function () {
        Route::patch('sessions/{session}/status', [SessionStatusController::class, 'update'])
            ->name('sessions.status.update')
            ->whereNumber('session');
    });

    // تغيير قاعة يوم محدد مع تسجيل التغيير في hall_change_log.
    Route::middleware('permission.any:CHANGE_HALL')->group(function () {
        Route::patch('sessions/{session}/hall', [SessionHallController::class, 'update'])
            ->name('sessions.hall.update')
            ->whereNumber('session');
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

    // المتدربون: إدارة الملفات لمن يملك TRAINEES_MANAGE، والقراءة متاحة أيضًا لمشرف الإنذارات.
    Route::middleware('permission.any:TRAINEES_MANAGE')->group(function () {
        Route::get('trainees/create', [TraineeController::class, 'create'])->name('trainees.create');
        Route::post('trainees', [TraineeController::class, 'store'])->name('trainees.store');
        Route::get('trainees/{trainee}/edit', [TraineeController::class, 'edit'])->name('trainees.edit')->whereNumber('trainee');
        Route::match(['put', 'patch'], 'trainees/{trainee}', [TraineeController::class, 'update'])->name('trainees.update')->whereNumber('trainee');
        Route::delete('trainees/{trainee}', [TraineeController::class, 'destroy'])->name('trainees.destroy')->whereNumber('trainee');
    });

    Route::middleware('permission.any:TRAINEES_MANAGE,WARNINGS_MANAGE')->group(function () {
        Route::get('trainees', [TraineeController::class, 'index'])->name('trainees.index');
        Route::get('trainees/{trainee}', [TraineeController::class, 'show'])->name('trainees.show')->whereNumber('trainee');
    });

    // إنذارات المتدربين: صلاحية WARNINGS_MANAGE مستقلة عن إدارة ملف المتدرب.
    Route::middleware('permission.any:WARNINGS_MANAGE')->group(function () {
        Route::post('trainees/{trainee}/warnings', [TraineeWarningController::class, 'store'])
            ->name('trainees.warnings.store')
            ->whereNumber('trainee');
        Route::delete('trainees/{trainee}/warnings/{warning}', [TraineeWarningController::class, 'destroy'])
            ->name('trainees.warnings.destroy')
            ->whereNumber('trainee')
            ->whereNumber('warning');
    });

    // الترشيحات: الإنشاء لمن يملك NOMINATE_TRAINEE، والقراءة لمن يراجع أو يعتمد أو يدير التسجيلات.
    Route::middleware('permission.any:NOMINATE_TRAINEE')->group(function () {
        Route::get('nominations/create', [NominationController::class, 'create'])->name('nominations.create');
        Route::post('nominations', [NominationController::class, 'store'])->name('nominations.store');
        Route::get('nominations/{nomination}/edit', [NominationController::class, 'edit'])->name('nominations.edit')->whereNumber('nomination');
        Route::match(['put', 'patch'], 'nominations/{nomination}', [NominationController::class, 'update'])->name('nominations.update')->whereNumber('nomination');
    });

    Route::middleware('permission.any:NOMINATE_TRAINEE,REVIEW_NOMINATION,APPROVE_NOMINATION,ENROLLMENTS_MANAGE')->group(function () {
        Route::get('nominations', [NominationController::class, 'index'])->name('nominations.index');
        Route::get('nominations/{nomination}', [NominationController::class, 'show'])->name('nominations.show')->whereNumber('nomination');
    });

    // نقل الترشيح بين الحالات: الصلاحية يحددها TransitionNominationRequest حسب الحالة المطلوبة.
    Route::middleware('permission.any:REVIEW_NOMINATION,APPROVE_NOMINATION')->group(function () {
        Route::patch('nominations/{nomination}/status', [NominationController::class, 'transition'])
            ->name('nominations.transition')
            ->whereNumber('nomination');
        Route::delete('nominations/{nomination}', [NominationController::class, 'destroy'])
            ->name('nominations.destroy')
            ->whereNumber('nomination');
    });

    // التسجيلات: إدارة كاملة لمن يملك ENROLLMENTS_MANAGE.
    Route::middleware('permission.any:ENROLLMENTS_MANAGE')->group(function () {
        Route::get('enrollments/create', [EnrollmentController::class, 'create'])->name('enrollments.create');
        Route::post('enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');
        Route::get('enrollments/{enrollment}/edit', [EnrollmentController::class, 'edit'])->name('enrollments.edit')->whereNumber('enrollment');
        Route::match(['put', 'patch'], 'enrollments/{enrollment}', [EnrollmentController::class, 'update'])->name('enrollments.update')->whereNumber('enrollment');
        Route::post('enrollments/{enrollment}/recalculate-attendance', [EnrollmentController::class, 'recalculate'])
            ->name('enrollments.recalculate')
            ->whereNumber('enrollment');
        Route::delete('enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy')->whereNumber('enrollment');
        Route::get('enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
        Route::get('enrollments/{enrollment}', [EnrollmentController::class, 'show'])->name('enrollments.show')->whereNumber('enrollment');
    });

    // الحضور: تسجيل الحضور لمن يملك ATTENDANCE_RECORD، ومراجعة الأعذار بصلاحية مستقلة.
    Route::middleware('permission.any:ATTENDANCE_RECORD,ATTENDANCE_EXCUSE_REVIEW')->group(function () {
        Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('attendance/{session}', [AttendanceController::class, 'session'])
            ->name('attendance.session')
            ->whereNumber('session');
    });

    Route::middleware('permission.any:ATTENDANCE_EXCUSE_REVIEW')->group(function () {
        Route::get('attendance/excuses', [AttendanceController::class, 'excuses'])->name('attendance.excuses.index');
    });

    Route::middleware('permission.any:ATTENDANCE_VIEW_OWN')->group(function () {
        Route::get('attendance/own', [AttendanceController::class, 'own'])->name('attendance.own');
    });

    Route::middleware('permission.any:ATTENDANCE_RECORD')->group(function () {
        Route::post('attendance/{session}/days/{day}', [AttendanceController::class, 'record'])
            ->name('attendance.record')
            ->whereNumber('session')
            ->whereNumber('day');
    });

    Route::middleware('permission.any:ATTENDANCE_EXCUSE_REVIEW')->group(function () {
        Route::patch('attendance/excuses/{excuse}', [AttendanceController::class, 'reviewExcuse'])
            ->name('attendance.excuses.review')
            ->whereNumber('excuse');
    });

    // الاختبارات: بنك الأسئلة بصلاحية مستقلة، وإدارة الاختبارات والتصحيح بصلاحيتين منفصلتين.
    Route::resource('question-banks', QuestionBankController::class)
        ->except(['show'])
        ->parameters(['question-banks' => 'bank'])
        ->middleware('permission.any:QUESTION_BANK_MANAGE');

    Route::middleware('permission.any:EXAMS_MANAGE')->group(function () {
        Route::get('exams/create', [ExamController::class, 'create'])->name('exams.create');
        Route::post('exams', [ExamController::class, 'store'])->name('exams.store');
        Route::get('exams/{exam}/edit', [ExamController::class, 'edit'])->name('exams.edit')->whereNumber('exam');
        Route::match(['put', 'patch'], 'exams/{exam}', [ExamController::class, 'update'])->name('exams.update')->whereNumber('exam');
        Route::delete('exams/{exam}', [ExamController::class, 'destroy'])->name('exams.destroy')->whereNumber('exam');
    });

    Route::middleware('permission.any:EXAMS_MANAGE,EXAMS_GRADE')->group(function () {
        Route::get('exams', [ExamController::class, 'index'])->name('exams.index');
        Route::get('exams/{exam}', [ExamController::class, 'show'])->name('exams.show')->whereNumber('exam');
    });

    Route::middleware('permission.any:EXAMS_GRADE')->group(function () {
        Route::post('exams/{exam}/results', [ExamController::class, 'grade'])->name('exams.grade')->whereNumber('exam');
    });

    // التقييمات: بناء النماذج بصلاحية مستقلة، والتعبئة والإرسال بصلاحية المقيّم، والتقارير بصلاحية العرض.
    Route::middleware('permission.any:EVALUATION_FORMS_MANAGE')->group(function () {
        Route::resource('evaluations', EvaluationFormController::class)->except(['index', 'show']);
    });

    Route::middleware('permission.any:EVALUATION_FORMS_MANAGE,EVALUATION_REPORTS_VIEW,EVALUATION_SUBMIT')->group(function () {
        Route::get('evaluations', [EvaluationFormController::class, 'index'])->name('evaluations.index');
    });

    Route::middleware('permission.any:EVALUATION_REPORTS_VIEW')->group(function () {
        Route::get('evaluations/reports', [EvaluationReportController::class, 'index'])->name('evaluations.reports.index');
    });

    Route::middleware('permission.any:EVALUATION_SUBMIT')->group(function () {
        Route::get('evaluations/{evaluation}/submit', [EvaluationSubmissionController::class, 'create'])
            ->name('evaluations.submissions.create')
            ->whereNumber('evaluation');
        Route::post('evaluations/{evaluation}/submit', [EvaluationSubmissionController::class, 'store'])
            ->name('evaluations.submissions.store')
            ->whereNumber('evaluation');
    });

    Route::middleware('permission.any:EVALUATION_FORMS_MANAGE,EVALUATION_REPORTS_VIEW,EVALUATION_SUBMIT')->group(function () {
        Route::get('evaluations/{evaluation}', [EvaluationFormController::class, 'show'])
            ->name('evaluations.show')
            ->whereNumber('evaluation');
    });

    // الشهادات: الإصدار وإدارة القوالب بصلاحية الإصدار، والإلغاء بصلاحية مستقلة، والعرض للقراءة.
    Route::middleware('permission.any:ISSUE_CERTIFICATE')->group(function () {
        Route::resource('certificate-templates', CertificateTemplateController::class)
            ->except(['show'])
            ->parameters(['certificate-templates' => 'template'])
            ->names('certificates.templates');

        Route::get('certificates/create', [CertificateController::class, 'create'])->name('certificates.create');
        Route::post('certificates', [CertificateController::class, 'store'])->name('certificates.store');
    });

    Route::middleware('permission.any:CERTIFICATES_VIEW,ISSUE_CERTIFICATE,REVOKE_CERTIFICATE')->group(function () {
        Route::get('certificates', [CertificateController::class, 'index'])->name('certificates.index');
        Route::get('certificates/{certificate}', [CertificateController::class, 'show'])
            ->name('certificates.show')
            ->whereNumber('certificate');
        Route::get('certificates/{certificate}/print', [CertificateController::class, 'print'])
            ->name('certificates.print')
            ->whereNumber('certificate');
    });

    Route::middleware('permission.any:CERTIFICATES_VIEW_OWN')->group(function () {
        Route::get('certificates/own', [CertificateController::class, 'own'])->name('certificates.own');
    });

    Route::middleware('permission.any:REVOKE_CERTIFICATE')->group(function () {
        Route::patch('certificates/{certificate}/revoke', [CertificateController::class, 'revoke'])
            ->name('certificates.revoke')
            ->whereNumber('certificate');
    });

    // المالية: الفواتير والدفعات ومستحقات المدربين بصلاحيات منفصلة.
    Route::middleware('permission.any:INVOICES_MANAGE')->group(function () {
        Route::get('invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
        Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::get('invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit')->whereNumber('invoice');
        Route::match(['put', 'patch'], 'invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update')->whereNumber('invoice');
        Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy')->whereNumber('invoice');
    });

    Route::middleware('permission.any:INVOICES_MANAGE,PAYMENTS_MANAGE')->group(function () {
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show')->whereNumber('invoice');
    });

    Route::middleware('permission.any:PAYMENTS_MANAGE')->group(function () {
        Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])
            ->name('invoices.payments.store')
            ->whereNumber('invoice');
    });

    Route::middleware('permission.any:TRAINER_PAYMENTS_MANAGE')->group(function () {
        Route::get('trainer-payments', [TrainerPaymentController::class, 'index'])->name('trainer-payments.index');
        Route::get('trainer-payments/create', [TrainerPaymentController::class, 'create'])->name('trainer-payments.create');
        Route::post('trainer-payments', [TrainerPaymentController::class, 'store'])->name('trainer-payments.store');
        Route::patch('trainer-payments/{trainerPayment}/status', [TrainerPaymentController::class, 'updateStatus'])
            ->name('trainer-payments.status')
            ->whereNumber('trainerPayment');
    });

    // المدربون: إدارة الملفات لمن يملك TRAINERS_MANAGE.
    Route::resource('trainers', TrainerController::class)
        ->middleware('permission.any:TRAINERS_MANAGE');

    // جهات التدريب: إدارة الجهات المرتبطة بالمدربين الخارجيين والجلسات.
    Route::resource('providers', TrainingProviderController::class)
        ->middleware('permission.any:PROVIDERS_MANAGE');

    // القاعات: إدارة القاعات والتجهيزات لمن يملك HALLS_MANAGE.
    Route::middleware('permission.any:HALLS_MANAGE')->group(function () {
        Route::get('halls/create', [HallController::class, 'create'])->name('halls.create');
        Route::post('halls', [HallController::class, 'store'])->name('halls.store');
        Route::get('halls/{hall}/edit', [HallController::class, 'edit'])->name('halls.edit')->whereNumber('hall');
        Route::match(['put', 'patch'], 'halls/{hall}', [HallController::class, 'update'])->name('halls.update')->whereNumber('hall');
        Route::delete('halls/{hall}', [HallController::class, 'destroy'])->name('halls.destroy')->whereNumber('hall');

        Route::post('equipment', [EquipmentController::class, 'store'])->name('equipment.store');
        Route::put('equipment/{equipment}', [EquipmentController::class, 'update'])->name('equipment.update')->whereNumber('equipment');
        Route::delete('equipment/{equipment}', [EquipmentController::class, 'destroy'])->name('equipment.destroy')->whereNumber('equipment');
    });

    // قراءة القاعات متاحة لمن يدير القاعات أو يغيّرها (مثل منسق التدريب).
    Route::middleware('permission.any:HALLS_MANAGE,CHANGE_HALL')->group(function () {
        Route::get('halls', [HallController::class, 'index'])->name('halls.index');
        Route::get('halls/{hall}', [HallController::class, 'show'])->name('halls.show')->whereNumber('hall');
    });

    // حجب إتاحة القاعة: صلاحية HALL_BLOCK مستقلة عن إدارة القاعات.
    Route::middleware('permission.any:HALL_BLOCK')->group(function () {
        Route::post('halls/{hall}/blocks', [HallBlockController::class, 'store'])
            ->name('halls.blocks.store')
            ->whereNumber('hall');
        Route::delete('halls/{hall}/blocks/{block}', [HallBlockController::class, 'destroy'])
            ->name('halls.blocks.destroy')
            ->whereNumber('hall')
            ->whereNumber('block');
    });

    // اعتماد المدرب لتدريس دورة: صلاحية مستقلة عن إدارة ملف المدرب.
    Route::middleware('permission.any:TRAINER_APPROVE_COURSE')->group(function () {
        Route::post('trainers/{trainer}/approvals', [TrainerApprovalController::class, 'store'])
            ->name('trainers.approvals.store');
        Route::delete('trainers/{trainer}/approvals/{approval}', [TrainerApprovalController::class, 'destroy'])
            ->name('trainers.approvals.destroy');
    });

    // الإدارة: المستخدمون والأدوار والفروع والاعتمادات وسجل التدقيق.
    Route::middleware('permission.any:USERS_MANAGE')->group(function () {
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit')->whereNumber('user');
        Route::match(['put', 'patch'], 'users/{user}', [UserController::class, 'update'])->name('users.update')->whereNumber('user');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->whereNumber('user');
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show')->whereNumber('user');
    });

    Route::middleware('permission.any:ROLES_MANAGE')->group(function () {
        Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit')->whereNumber('role');
        Route::match(['put', 'patch'], 'roles/{role}', [RoleController::class, 'update'])->name('roles.update')->whereNumber('role');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy')->whereNumber('role');
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    });

    Route::middleware('permission.any:BRANCHES_MANAGE')->group(function () {
        Route::get('branches/create', [BranchController::class, 'create'])->name('branches.create');
        Route::post('branches', [BranchController::class, 'store'])->name('branches.store');
        Route::get('branches/{branch}/edit', [BranchController::class, 'edit'])->name('branches.edit')->whereNumber('branch');
        Route::match(['put', 'patch'], 'branches/{branch}', [BranchController::class, 'update'])->name('branches.update')->whereNumber('branch');
        Route::delete('branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy')->whereNumber('branch');
        Route::get('branches', [BranchController::class, 'index'])->name('branches.index');
        Route::get('branches/{branch}', [BranchController::class, 'show'])->name('branches.show')->whereNumber('branch');

        Route::post('branches/{branch}/accreditations', [BranchController::class, 'storeAccreditation'])
            ->name('branches.accreditations.store')
            ->whereNumber('branch');
        Route::patch('branches/{branch}/accreditations/{branchAccreditation}', [BranchController::class, 'updateAccreditation'])
            ->name('branches.accreditations.update')
            ->whereNumber('branch')
            ->whereNumber('branchAccreditation');
        Route::delete('branches/{branch}/accreditations/{branchAccreditation}', [BranchController::class, 'destroyAccreditation'])
            ->name('branches.accreditations.destroy')
            ->whereNumber('branch')
            ->whereNumber('branchAccreditation');

        Route::get('accreditations/create', [AccreditationController::class, 'create'])->name('accreditations.create');
        Route::post('accreditations', [AccreditationController::class, 'store'])->name('accreditations.store');
        Route::get('accreditations/{accreditation}/edit', [AccreditationController::class, 'edit'])->name('accreditations.edit')->whereNumber('accreditation');
        Route::match(['put', 'patch'], 'accreditations/{accreditation}', [AccreditationController::class, 'update'])->name('accreditations.update')->whereNumber('accreditation');
        Route::delete('accreditations/{accreditation}', [AccreditationController::class, 'destroy'])->name('accreditations.destroy')->whereNumber('accreditation');
        Route::get('accreditations', [AccreditationController::class, 'index'])->name('accreditations.index');
    });

    Route::middleware('permission.any:AUDIT_VIEW')->group(function () {
        Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
    });
});
