<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /** Permission codes (same naming style as the ERD: CHANGE_HALL, APPROVE_NOMINATION, ...). */
    public const PERMISSIONS = [
        // administration
        'USERS_MANAGE', 'ROLES_MANAGE', 'BRANCHES_MANAGE', 'LOOKUPS_MANAGE', 'AUDIT_VIEW', 'REPORTS_VIEW',
        // courses & sessions
        'COURSES_VIEW', 'COURSES_MANAGE', 'SESSIONS_MANAGE', 'SESSIONS_PUBLISH', 'KITS_MANAGE',
        // trainers & providers
        'TRAINERS_MANAGE', 'TRAINER_APPROVE_COURSE', 'PROVIDERS_MANAGE',
        // trainees
        'TRAINEES_MANAGE', 'WARNINGS_MANAGE',
        // halls
        'HALLS_MANAGE', 'CHANGE_HALL', 'HALL_BLOCK',
        // nomination & enrollment
        'SELF_NOMINATE', 'NOMINATE_TRAINEE', 'REVIEW_NOMINATION', 'APPROVE_NOMINATION', 'OVERRIDE_CRITERIA', 'ENROLLMENTS_MANAGE',
        // attendance
        'ATTENDANCE_RECORD', 'ATTENDANCE_EXCUSE_REVIEW', 'ATTENDANCE_VIEW_OWN',
        // exams
        'QUESTION_BANK_MANAGE', 'EXAMS_MANAGE', 'EXAMS_GRADE',
        // evaluations
        'EVALUATION_FORMS_MANAGE', 'EVALUATION_SUBMIT', 'EVALUATION_REPORTS_VIEW',
        // certificates
        'CERTIFICATES_VIEW', 'CERTIFICATES_VIEW_OWN', 'ISSUE_CERTIFICATE', 'REVOKE_CERTIFICATE',
        // finance
        'INVOICES_MANAGE', 'PAYMENTS_MANAGE', 'TRAINER_PAYMENTS_MANAGE', 'FINANCE_REPORTS_VIEW',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $code) {
            Permission::findOrCreate($code, 'web');
        }

        $all = self::PERMISSIONS;

        $roles = [
            'super_admin' => $all,
            'center_manager' => array_values(array_diff($all, ['ROLES_MANAGE'])),
            'training_coordinator' => [
                'COURSES_VIEW', 'COURSES_MANAGE', 'SESSIONS_MANAGE', 'SESSIONS_PUBLISH', 'KITS_MANAGE',
                'TRAINERS_MANAGE', 'TRAINER_APPROVE_COURSE', 'PROVIDERS_MANAGE', 'CHANGE_HALL',
                'ATTENDANCE_RECORD', 'QUESTION_BANK_MANAGE', 'EXAMS_MANAGE', 'EXAMS_GRADE',
                'EVALUATION_REPORTS_VIEW', 'CERTIFICATES_VIEW', 'ISSUE_CERTIFICATE', 'REPORTS_VIEW',
            ],
            'admissions_officer' => [
                'COURSES_VIEW', 'TRAINEES_MANAGE', 'WARNINGS_MANAGE', 'NOMINATE_TRAINEE', 'REVIEW_NOMINATION',
                'APPROVE_NOMINATION', 'ENROLLMENTS_MANAGE', 'ATTENDANCE_EXCUSE_REVIEW', 'CERTIFICATES_VIEW',
            ],
            'hall_manager' => ['COURSES_VIEW', 'HALLS_MANAGE', 'CHANGE_HALL', 'HALL_BLOCK'],
            'finance_officer' => [
                'COURSES_VIEW', 'INVOICES_MANAGE', 'PAYMENTS_MANAGE', 'TRAINER_PAYMENTS_MANAGE', 'FINANCE_REPORTS_VIEW',
            ],
            'quality_officer' => [
                'COURSES_VIEW', 'EVALUATION_FORMS_MANAGE', 'EVALUATION_REPORTS_VIEW', 'AUDIT_VIEW', 'REPORTS_VIEW',
            ],
            'trainer' => ['COURSES_VIEW', 'ATTENDANCE_RECORD', 'EXAMS_GRADE', 'EVALUATION_SUBMIT'],
            'trainee' => [
                'COURSES_VIEW', 'SELF_NOMINATE', 'ATTENDANCE_VIEW_OWN', 'EVALUATION_SUBMIT', 'CERTIFICATES_VIEW_OWN',
            ],
        ];

        foreach ($roles as $name => $permissions) {
            Role::findOrCreate($name, 'web')->syncPermissions($permissions);
        }
    }
}
