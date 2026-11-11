<?php

/**
 * Sidebar definition. An item shows when the user has ANY of its permissions (super_admin sees all).
 * `route` is a named route; until that route exists the item is rendered as "قريبًا" (disabled),
 * so each new module only has to register its routes to appear in the menu.
 */
return [
    'role_labels' => [
        'super_admin' => 'مدير النظام',
        'center_manager' => 'مدير المركز',
        'training_coordinator' => 'منسق التدريب',
        'admissions_officer' => 'مسؤول القبول',
        'hall_manager' => 'مسؤول القاعات',
        'finance_officer' => 'المسؤول المالي',
        'quality_officer' => 'مسؤول الجودة',
        'trainer' => 'مدرب',
        'trainee' => 'متدرب',
    ],

    'groups' => [
        ['title' => null, 'items' => [
            ['label' => 'لوحة التحكم', 'icon' => 'fa-gauge', 'route' => 'dashboard', 'can' => []],
        ]],
        ['title' => 'البرامج التدريبية', 'items' => [
            ['label' => 'الدورات', 'icon' => 'fa-book-open', 'route' => 'courses.index', 'can' => ['COURSES_VIEW', 'COURSES_MANAGE']],
            ['label' => 'الجلسات المجدولة', 'icon' => 'fa-calendar-days', 'route' => 'sessions.index', 'can' => ['SESSIONS_MANAGE', 'SESSIONS_PUBLISH', 'CHANGE_HALL']],
            ['label' => 'التصنيفات والأنواع', 'icon' => 'fa-tags', 'route' => 'lookups.index', 'can' => ['LOOKUPS_MANAGE']],
        ]],
        ['title' => 'المتدربون', 'items' => [
            ['label' => 'المتدربون', 'icon' => 'fa-users', 'route' => 'trainees.index', 'can' => ['TRAINEES_MANAGE', 'WARNINGS_MANAGE']],
            ['label' => 'الترشيحات', 'icon' => 'fa-user-check', 'route' => 'nominations.index', 'can' => ['REVIEW_NOMINATION', 'NOMINATE_TRAINEE']],
            ['label' => 'التسجيلات', 'icon' => 'fa-clipboard-list', 'route' => 'enrollments.index', 'can' => ['ENROLLMENTS_MANAGE']],
        ]],
        ['title' => 'المدربون والموارد', 'items' => [
            ['label' => 'المدربون', 'icon' => 'fa-chalkboard-user', 'route' => 'trainers.index', 'can' => ['TRAINERS_MANAGE']],
            ['label' => 'جهات التدريب', 'icon' => 'fa-building-columns', 'route' => 'providers.index', 'can' => ['PROVIDERS_MANAGE']],
            ['label' => 'القاعات', 'icon' => 'fa-door-open', 'route' => 'halls.index', 'can' => ['HALLS_MANAGE', 'CHANGE_HALL']],
        ]],
        ['title' => 'التنفيذ والمتابعة', 'items' => [
            ['label' => 'الحضور', 'icon' => 'fa-user-clock', 'route' => 'attendance.index', 'can' => ['ATTENDANCE_RECORD', 'ATTENDANCE_EXCUSE_REVIEW']],
            ['label' => 'الاختبارات', 'icon' => 'fa-file-pen', 'route' => 'exams.index', 'can' => ['EXAMS_MANAGE', 'EXAMS_GRADE']],
            ['label' => 'التقييمات', 'icon' => 'fa-star-half-stroke', 'route' => 'evaluations.index', 'can' => ['EVALUATION_FORMS_MANAGE', 'EVALUATION_REPORTS_VIEW']],
            ['label' => 'الشهادات', 'icon' => 'fa-certificate', 'route' => 'certificates.index', 'can' => ['CERTIFICATES_VIEW', 'ISSUE_CERTIFICATE']],
            ['label' => 'شهاداتي', 'icon' => 'fa-award', 'route' => 'certificates.own', 'can' => ['CERTIFICATES_VIEW_OWN']],
        ]],
        ['title' => 'المالية', 'items' => [
            ['label' => 'الفواتير والمدفوعات', 'icon' => 'fa-file-invoice-dollar', 'route' => 'invoices.index', 'can' => ['INVOICES_MANAGE', 'PAYMENTS_MANAGE']],
            ['label' => 'مستحقات المدربين', 'icon' => 'fa-wallet', 'route' => 'trainer-payments.index', 'can' => ['TRAINER_PAYMENTS_MANAGE']],
        ]],
        ['title' => 'الإدارة', 'items' => [
            ['label' => 'المستخدمون', 'icon' => 'fa-user-gear', 'route' => 'users.index', 'can' => ['USERS_MANAGE']],
            ['label' => 'الأدوار والصلاحيات', 'icon' => 'fa-shield-halved', 'route' => 'roles.index', 'can' => ['ROLES_MANAGE']],
            ['label' => 'الفروع', 'icon' => 'fa-sitemap', 'route' => 'branches.index', 'can' => ['BRANCHES_MANAGE']],
            ['label' => 'سجل التدقيق', 'icon' => 'fa-clock-rotate-left', 'route' => 'audit.index', 'can' => ['AUDIT_VIEW']],
        ]],
    ],
];
