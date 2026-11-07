<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * All foreign keys are added last to avoid ordering problems and circular references
 * (users <-> trainers/trainees, trainee_warnings -> course_sessions, ...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_accreditations', function (Blueprint $table) {
            $table->foreign('branch_id', 'branch_accreditations_branch_id_foreign')->references('id')->on('branches');
            $table->foreign('accreditation_id', 'branch_accreditations_accreditation_id_foreign')->references('id')->on('accreditations');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('branch_id', 'users_branch_id_foreign')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('trainer_id', 'users_trainer_id_foreign')->references('id')->on('trainers')->nullOnDelete();
            $table->foreign('trainee_id', 'users_trainee_id_foreign')->references('id')->on('trainees')->nullOnDelete();
        });
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('user_id', 'audit_logs_user_id_foreign')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('attachments', function (Blueprint $table) {
            $table->foreign('uploaded_by', 'attachments_uploaded_by_foreign')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('course_categories', function (Blueprint $table) {
            $table->foreign('parent_id', 'course_categories_parent_id_foreign')->references('id')->on('course_categories')->nullOnDelete();
        });
        Schema::table('trainers', function (Blueprint $table) {
            $table->foreign('provider_id', 'trainers_provider_id_foreign')->references('id')->on('training_providers')->nullOnDelete();
            $table->foreign('branch_id', 'trainers_branch_id_foreign')->references('id')->on('branches')->nullOnDelete();
        });
        Schema::table('trainer_certifications', function (Blueprint $table) {
            $table->foreign('trainer_id', 'trainer_certifications_trainer_id_foreign')->references('id')->on('trainers');
        });
        Schema::table('trainer_experiences', function (Blueprint $table) {
            $table->foreign('trainer_id', 'trainer_experiences_trainer_id_foreign')->references('id')->on('trainers');
        });
        Schema::table('trainer_specialties', function (Blueprint $table) {
            $table->foreign('trainer_id', 'trainer_specialties_trainer_id_foreign')->references('id')->on('trainers');
            $table->foreign('category_id', 'trainer_specialties_category_id_foreign')->references('id')->on('course_categories');
        });
        Schema::table('trainer_course_approvals', function (Blueprint $table) {
            $table->foreign('trainer_id', 'trainer_course_approvals_trainer_id_foreign')->references('id')->on('trainers');
            $table->foreign('course_id', 'trainer_course_approvals_course_id_foreign')->references('id')->on('courses');
            $table->foreign('approved_by', 'trainer_course_approvals_approved_by_foreign')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('trainees', function (Blueprint $table) {
            $table->foreign('education_level_id', 'trainees_education_level_id_foreign')->references('id')->on('education_levels')->nullOnDelete();
            $table->foreign('job_grade_id', 'trainees_job_grade_id_foreign')->references('id')->on('job_grades')->nullOnDelete();
        });
        Schema::table('trainee_providers', function (Blueprint $table) {
            $table->foreign('trainee_id', 'trainee_providers_trainee_id_foreign')->references('id')->on('trainees');
            $table->foreign('provider_id', 'trainee_providers_provider_id_foreign')->references('id')->on('training_providers');
        });
        Schema::table('trainee_warnings', function (Blueprint $table) {
            $table->foreign('trainee_id', 'trainee_warnings_trainee_id_foreign')->references('id')->on('trainees');
            $table->foreign('session_id', 'trainee_warnings_session_id_foreign')->references('id')->on('course_sessions')->nullOnDelete();
            $table->foreign('issued_by', 'trainee_warnings_issued_by_foreign')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('courses', function (Blueprint $table) {
            $table->foreign('category_id', 'courses_category_id_foreign')->references('id')->on('course_categories')->nullOnDelete();
            $table->foreign('course_type_id', 'courses_course_type_id_foreign')->references('id')->on('course_types')->nullOnDelete();
        });
        Schema::table('course_prerequisites', function (Blueprint $table) {
            $table->foreign('course_id', 'course_prerequisites_course_id_foreign')->references('id')->on('courses');
            $table->foreign('prerequisite_course_id', 'course_prerequisites_prerequisite_course_id_foreign')->references('id')->on('courses');
        });
        Schema::table('course_modules', function (Blueprint $table) {
            $table->foreign('course_id', 'course_modules_course_id_foreign')->references('id')->on('courses');
        });
        Schema::table('course_criteria', function (Blueprint $table) {
            $table->foreign('course_id', 'course_criteria_course_id_foreign')->references('id')->on('courses');
            $table->foreign('criteria_type_id', 'course_criteria_criteria_type_id_foreign')->references('id')->on('criteria_types');
        });
        Schema::table('halls', function (Blueprint $table) {
            $table->foreign('branch_id', 'halls_branch_id_foreign')->references('id')->on('branches');
        });
        Schema::table('hall_equipment', function (Blueprint $table) {
            $table->foreign('hall_id', 'hall_equipment_hall_id_foreign')->references('id')->on('halls');
            $table->foreign('equipment_id', 'hall_equipment_equipment_id_foreign')->references('id')->on('equipment');
        });
        Schema::table('hall_blocks', function (Blueprint $table) {
            $table->foreign('hall_id', 'hall_blocks_hall_id_foreign')->references('id')->on('halls');
        });
        Schema::table('course_sessions', function (Blueprint $table) {
            $table->foreign('course_id', 'course_sessions_course_id_foreign')->references('id')->on('courses');
            $table->foreign('provider_id', 'course_sessions_provider_id_foreign')->references('id')->on('training_providers')->nullOnDelete();
            $table->foreign('branch_id', 'course_sessions_branch_id_foreign')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('accreditation_id', 'course_sessions_accreditation_id_foreign')->references('id')->on('accreditations')->nullOnDelete();
            $table->foreign('created_by', 'course_sessions_created_by_foreign')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('session_days', function (Blueprint $table) {
            $table->foreign('session_id', 'session_days_session_id_foreign')->references('id')->on('course_sessions');
            $table->foreign('hall_id', 'session_days_hall_id_foreign')->references('id')->on('halls')->nullOnDelete();
        });
        Schema::table('hall_change_log', function (Blueprint $table) {
            $table->foreign('session_day_id', 'hall_change_log_session_day_id_foreign')->references('id')->on('session_days');
            $table->foreign('old_hall_id', 'hall_change_log_old_hall_id_foreign')->references('id')->on('halls')->nullOnDelete();
            $table->foreign('new_hall_id', 'hall_change_log_new_hall_id_foreign')->references('id')->on('halls')->nullOnDelete();
            $table->foreign('changed_by', 'hall_change_log_changed_by_foreign')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('session_day_modules', function (Blueprint $table) {
            $table->foreign('session_day_id', 'session_day_modules_session_day_id_foreign')->references('id')->on('session_days');
            $table->foreign('module_id', 'session_day_modules_module_id_foreign')->references('id')->on('course_modules');
            $table->foreign('trainer_id', 'session_day_modules_trainer_id_foreign')->references('id')->on('trainers')->nullOnDelete();
        });
        Schema::table('session_trainers', function (Blueprint $table) {
            $table->foreign('session_id', 'session_trainers_session_id_foreign')->references('id')->on('course_sessions');
            $table->foreign('trainer_id', 'session_trainers_trainer_id_foreign')->references('id')->on('trainers');
            $table->foreign('assigned_by', 'session_trainers_assigned_by_foreign')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('session_day_trainers', function (Blueprint $table) {
            $table->foreign('session_day_id', 'session_day_trainers_session_day_id_foreign')->references('id')->on('session_days');
            $table->foreign('trainer_id', 'session_day_trainers_trainer_id_foreign')->references('id')->on('trainers');
            $table->foreign('replaced_trainer_id', 'session_day_trainers_replaced_trainer_id_foreign')->references('id')->on('trainers')->nullOnDelete();
        });
        Schema::table('training_kits', function (Blueprint $table) {
            $table->foreign('session_id', 'training_kits_session_id_foreign')->references('id')->on('course_sessions');
            $table->foreign('module_id', 'training_kits_module_id_foreign')->references('id')->on('course_modules')->nullOnDelete();
            $table->foreign('trainer_id', 'training_kits_trainer_id_foreign')->references('id')->on('trainers');
            $table->foreign('reviewed_by', 'training_kits_reviewed_by_foreign')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('nominations', function (Blueprint $table) {
            $table->foreign('session_id', 'nominations_session_id_foreign')->references('id')->on('course_sessions');
            $table->foreign('trainee_id', 'nominations_trainee_id_foreign')->references('id')->on('trainees');
            $table->foreign('nominated_by_provider_id', 'nominations_nominated_by_provider_id_foreign')->references('id')->on('training_providers')->nullOnDelete();
            $table->foreign('decision_by', 'nominations_decision_by_foreign')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('nomination_status_history', function (Blueprint $table) {
            $table->foreign('nomination_id', 'nomination_status_history_nomination_id_foreign')->references('id')->on('nominations');
            $table->foreign('changed_by', 'nomination_status_history_changed_by_foreign')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreign('nomination_id', 'enrollments_nomination_id_foreign')->references('id')->on('nominations');
            $table->foreign('session_id', 'enrollments_session_id_foreign')->references('id')->on('course_sessions');
            $table->foreign('trainee_id', 'enrollments_trainee_id_foreign')->references('id')->on('trainees');
        });
        Schema::table('attendance_excuses', function (Blueprint $table) {
            $table->foreign('reviewed_by', 'attendance_excuses_reviewed_by_foreign')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('trainee_attendance', function (Blueprint $table) {
            $table->foreign('enrollment_id', 'trainee_attendance_enrollment_id_foreign')->references('id')->on('enrollments');
            $table->foreign('session_day_id', 'trainee_attendance_session_day_id_foreign')->references('id')->on('session_days');
            $table->foreign('check_in_by', 'trainee_attendance_check_in_by_foreign')->references('id')->on('users')->nullOnDelete();
            $table->foreign('check_out_by', 'trainee_attendance_check_out_by_foreign')->references('id')->on('users')->nullOnDelete();
            $table->foreign('excuse_id', 'trainee_attendance_excuse_id_foreign')->references('id')->on('attendance_excuses')->nullOnDelete();
        });
        Schema::table('trainer_attendance', function (Blueprint $table) {
            $table->foreign('session_day_id', 'trainer_attendance_session_day_id_foreign')->references('id')->on('session_days');
            $table->foreign('trainer_id', 'trainer_attendance_trainer_id_foreign')->references('id')->on('trainers');
            $table->foreign('check_in_by', 'trainer_attendance_check_in_by_foreign')->references('id')->on('users')->nullOnDelete();
            $table->foreign('check_out_by', 'trainer_attendance_check_out_by_foreign')->references('id')->on('users')->nullOnDelete();
            $table->foreign('excuse_id', 'trainer_attendance_excuse_id_foreign')->references('id')->on('attendance_excuses')->nullOnDelete();
        });
        Schema::table('question_banks', function (Blueprint $table) {
            $table->foreign('course_id', 'question_banks_course_id_foreign')->references('id')->on('courses');
            $table->foreign('module_id', 'question_banks_module_id_foreign')->references('id')->on('course_modules')->nullOnDelete();
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->foreign('bank_id', 'questions_bank_id_foreign')->references('id')->on('question_banks');
        });
        Schema::table('question_options', function (Blueprint $table) {
            $table->foreign('question_id', 'question_options_question_id_foreign')->references('id')->on('questions');
        });
        Schema::table('exams', function (Blueprint $table) {
            $table->foreign('session_id', 'exams_session_id_foreign')->references('id')->on('course_sessions');
            $table->foreign('created_by', 'exams_created_by_foreign')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->foreign('exam_id', 'exam_questions_exam_id_foreign')->references('id')->on('exams');
            $table->foreign('question_id', 'exam_questions_question_id_foreign')->references('id')->on('questions');
        });
        Schema::table('exam_results', function (Blueprint $table) {
            $table->foreign('exam_id', 'exam_results_exam_id_foreign')->references('id')->on('exams');
            $table->foreign('enrollment_id', 'exam_results_enrollment_id_foreign')->references('id')->on('enrollments');
            $table->foreign('graded_by', 'exam_results_graded_by_foreign')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('exam_answers', function (Blueprint $table) {
            $table->foreign('result_id', 'exam_answers_result_id_foreign')->references('id')->on('exam_results');
            $table->foreign('question_id', 'exam_answers_question_id_foreign')->references('id')->on('questions');
            $table->foreign('selected_option_id', 'exam_answers_selected_option_id_foreign')->references('id')->on('question_options')->nullOnDelete();
        });
        Schema::table('evaluation_forms', function (Blueprint $table) {
            $table->foreign('created_by', 'evaluation_forms_created_by_foreign')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('evaluation_sections', function (Blueprint $table) {
            $table->foreign('form_id', 'evaluation_sections_form_id_foreign')->references('id')->on('evaluation_forms');
        });
        Schema::table('evaluation_items', function (Blueprint $table) {
            $table->foreign('form_id', 'evaluation_items_form_id_foreign')->references('id')->on('evaluation_forms');
            $table->foreign('section_id', 'evaluation_items_section_id_foreign')->references('id')->on('evaluation_sections')->nullOnDelete();
        });
        Schema::table('evaluation_participation', function (Blueprint $table) {
            $table->foreign('form_id', 'evaluation_participation_form_id_foreign')->references('id')->on('evaluation_forms');
            $table->foreign('session_id', 'evaluation_participation_session_id_foreign')->references('id')->on('course_sessions');
            $table->foreign('enrollment_id', 'evaluation_participation_enrollment_id_foreign')->references('id')->on('enrollments')->nullOnDelete();
            $table->foreign('trainer_id', 'evaluation_participation_trainer_id_foreign')->references('id')->on('trainers')->nullOnDelete();
            $table->foreign('target_trainer_id', 'evaluation_participation_target_trainer_id_foreign')->references('id')->on('trainers')->nullOnDelete();
            $table->foreign('target_enrollment_id', 'evaluation_participation_target_enrollment_id_foreign')->references('id')->on('enrollments')->nullOnDelete();
            $table->foreign('target_hall_id', 'evaluation_participation_target_hall_id_foreign')->references('id')->on('halls')->nullOnDelete();
        });
        Schema::table('evaluation_submissions', function (Blueprint $table) {
            $table->foreign('form_id', 'evaluation_submissions_form_id_foreign')->references('id')->on('evaluation_forms');
            $table->foreign('session_id', 'evaluation_submissions_session_id_foreign')->references('id')->on('course_sessions');
            $table->foreign('evaluator_user_id', 'evaluation_submissions_evaluator_user_id_foreign')->references('id')->on('users')->nullOnDelete();
            $table->foreign('target_trainer_id', 'evaluation_submissions_target_trainer_id_foreign')->references('id')->on('trainers')->nullOnDelete();
            $table->foreign('target_enrollment_id', 'evaluation_submissions_target_enrollment_id_foreign')->references('id')->on('enrollments')->nullOnDelete();
            $table->foreign('target_hall_id', 'evaluation_submissions_target_hall_id_foreign')->references('id')->on('halls')->nullOnDelete();
        });
        Schema::table('evaluation_answers', function (Blueprint $table) {
            $table->foreign('submission_id', 'evaluation_answers_submission_id_foreign')->references('id')->on('evaluation_submissions');
            $table->foreign('item_id', 'evaluation_answers_item_id_foreign')->references('id')->on('evaluation_items');
        });
        Schema::table('certificates', function (Blueprint $table) {
            $table->foreign('enrollment_id', 'certificates_enrollment_id_foreign')->references('id')->on('enrollments');
            $table->foreign('template_id', 'certificates_template_id_foreign')->references('id')->on('certificate_templates')->nullOnDelete();
            $table->foreign('issued_by', 'certificates_issued_by_foreign')->references('id')->on('users')->nullOnDelete();
            $table->foreign('revoked_by', 'certificates_revoked_by_foreign')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('session_id', 'invoices_session_id_foreign')->references('id')->on('course_sessions');
            $table->foreign('trainee_id', 'invoices_trainee_id_foreign')->references('id')->on('trainees')->nullOnDelete();
            $table->foreign('provider_id', 'invoices_provider_id_foreign')->references('id')->on('training_providers')->nullOnDelete();
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('invoice_id', 'payments_invoice_id_foreign')->references('id')->on('invoices');
            $table->foreign('received_by', 'payments_received_by_foreign')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('trainer_payments', function (Blueprint $table) {
            $table->foreign('trainer_id', 'trainer_payments_trainer_id_foreign')->references('id')->on('trainers');
            $table->foreign('session_id', 'trainer_payments_session_id_foreign')->references('id')->on('course_sessions');
            $table->foreign('approved_by', 'trainer_payments_approved_by_foreign')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trainer_payments', function (Blueprint $table) {
            $table->dropForeign('trainer_payments_trainer_id_foreign');
            $table->dropForeign('trainer_payments_session_id_foreign');
            $table->dropForeign('trainer_payments_approved_by_foreign');
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign('payments_invoice_id_foreign');
            $table->dropForeign('payments_received_by_foreign');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign('invoices_session_id_foreign');
            $table->dropForeign('invoices_trainee_id_foreign');
            $table->dropForeign('invoices_provider_id_foreign');
        });
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign('certificates_enrollment_id_foreign');
            $table->dropForeign('certificates_template_id_foreign');
            $table->dropForeign('certificates_issued_by_foreign');
            $table->dropForeign('certificates_revoked_by_foreign');
        });
        Schema::table('evaluation_answers', function (Blueprint $table) {
            $table->dropForeign('evaluation_answers_submission_id_foreign');
            $table->dropForeign('evaluation_answers_item_id_foreign');
        });
        Schema::table('evaluation_submissions', function (Blueprint $table) {
            $table->dropForeign('evaluation_submissions_form_id_foreign');
            $table->dropForeign('evaluation_submissions_session_id_foreign');
            $table->dropForeign('evaluation_submissions_evaluator_user_id_foreign');
            $table->dropForeign('evaluation_submissions_target_trainer_id_foreign');
            $table->dropForeign('evaluation_submissions_target_enrollment_id_foreign');
            $table->dropForeign('evaluation_submissions_target_hall_id_foreign');
        });
        Schema::table('evaluation_participation', function (Blueprint $table) {
            $table->dropForeign('evaluation_participation_form_id_foreign');
            $table->dropForeign('evaluation_participation_session_id_foreign');
            $table->dropForeign('evaluation_participation_enrollment_id_foreign');
            $table->dropForeign('evaluation_participation_trainer_id_foreign');
            $table->dropForeign('evaluation_participation_target_trainer_id_foreign');
            $table->dropForeign('evaluation_participation_target_enrollment_id_foreign');
            $table->dropForeign('evaluation_participation_target_hall_id_foreign');
        });
        Schema::table('evaluation_items', function (Blueprint $table) {
            $table->dropForeign('evaluation_items_form_id_foreign');
            $table->dropForeign('evaluation_items_section_id_foreign');
        });
        Schema::table('evaluation_sections', function (Blueprint $table) {
            $table->dropForeign('evaluation_sections_form_id_foreign');
        });
        Schema::table('evaluation_forms', function (Blueprint $table) {
            $table->dropForeign('evaluation_forms_created_by_foreign');
        });
        Schema::table('exam_answers', function (Blueprint $table) {
            $table->dropForeign('exam_answers_result_id_foreign');
            $table->dropForeign('exam_answers_question_id_foreign');
            $table->dropForeign('exam_answers_selected_option_id_foreign');
        });
        Schema::table('exam_results', function (Blueprint $table) {
            $table->dropForeign('exam_results_exam_id_foreign');
            $table->dropForeign('exam_results_enrollment_id_foreign');
            $table->dropForeign('exam_results_graded_by_foreign');
        });
        Schema::table('exam_questions', function (Blueprint $table) {
            $table->dropForeign('exam_questions_exam_id_foreign');
            $table->dropForeign('exam_questions_question_id_foreign');
        });
        Schema::table('exams', function (Blueprint $table) {
            $table->dropForeign('exams_session_id_foreign');
            $table->dropForeign('exams_created_by_foreign');
        });
        Schema::table('question_options', function (Blueprint $table) {
            $table->dropForeign('question_options_question_id_foreign');
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign('questions_bank_id_foreign');
        });
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropForeign('question_banks_course_id_foreign');
            $table->dropForeign('question_banks_module_id_foreign');
        });
        Schema::table('trainer_attendance', function (Blueprint $table) {
            $table->dropForeign('trainer_attendance_session_day_id_foreign');
            $table->dropForeign('trainer_attendance_trainer_id_foreign');
            $table->dropForeign('trainer_attendance_check_in_by_foreign');
            $table->dropForeign('trainer_attendance_check_out_by_foreign');
            $table->dropForeign('trainer_attendance_excuse_id_foreign');
        });
        Schema::table('trainee_attendance', function (Blueprint $table) {
            $table->dropForeign('trainee_attendance_enrollment_id_foreign');
            $table->dropForeign('trainee_attendance_session_day_id_foreign');
            $table->dropForeign('trainee_attendance_check_in_by_foreign');
            $table->dropForeign('trainee_attendance_check_out_by_foreign');
            $table->dropForeign('trainee_attendance_excuse_id_foreign');
        });
        Schema::table('attendance_excuses', function (Blueprint $table) {
            $table->dropForeign('attendance_excuses_reviewed_by_foreign');
        });
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign('enrollments_nomination_id_foreign');
            $table->dropForeign('enrollments_session_id_foreign');
            $table->dropForeign('enrollments_trainee_id_foreign');
        });
        Schema::table('nomination_status_history', function (Blueprint $table) {
            $table->dropForeign('nomination_status_history_nomination_id_foreign');
            $table->dropForeign('nomination_status_history_changed_by_foreign');
        });
        Schema::table('nominations', function (Blueprint $table) {
            $table->dropForeign('nominations_session_id_foreign');
            $table->dropForeign('nominations_trainee_id_foreign');
            $table->dropForeign('nominations_nominated_by_provider_id_foreign');
            $table->dropForeign('nominations_decision_by_foreign');
        });
        Schema::table('training_kits', function (Blueprint $table) {
            $table->dropForeign('training_kits_session_id_foreign');
            $table->dropForeign('training_kits_module_id_foreign');
            $table->dropForeign('training_kits_trainer_id_foreign');
            $table->dropForeign('training_kits_reviewed_by_foreign');
        });
        Schema::table('session_day_trainers', function (Blueprint $table) {
            $table->dropForeign('session_day_trainers_session_day_id_foreign');
            $table->dropForeign('session_day_trainers_trainer_id_foreign');
            $table->dropForeign('session_day_trainers_replaced_trainer_id_foreign');
        });
        Schema::table('session_trainers', function (Blueprint $table) {
            $table->dropForeign('session_trainers_session_id_foreign');
            $table->dropForeign('session_trainers_trainer_id_foreign');
            $table->dropForeign('session_trainers_assigned_by_foreign');
        });
        Schema::table('session_day_modules', function (Blueprint $table) {
            $table->dropForeign('session_day_modules_session_day_id_foreign');
            $table->dropForeign('session_day_modules_module_id_foreign');
            $table->dropForeign('session_day_modules_trainer_id_foreign');
        });
        Schema::table('hall_change_log', function (Blueprint $table) {
            $table->dropForeign('hall_change_log_session_day_id_foreign');
            $table->dropForeign('hall_change_log_old_hall_id_foreign');
            $table->dropForeign('hall_change_log_new_hall_id_foreign');
            $table->dropForeign('hall_change_log_changed_by_foreign');
        });
        Schema::table('session_days', function (Blueprint $table) {
            $table->dropForeign('session_days_session_id_foreign');
            $table->dropForeign('session_days_hall_id_foreign');
        });
        Schema::table('course_sessions', function (Blueprint $table) {
            $table->dropForeign('course_sessions_course_id_foreign');
            $table->dropForeign('course_sessions_provider_id_foreign');
            $table->dropForeign('course_sessions_branch_id_foreign');
            $table->dropForeign('course_sessions_accreditation_id_foreign');
            $table->dropForeign('course_sessions_created_by_foreign');
        });
        Schema::table('hall_blocks', function (Blueprint $table) {
            $table->dropForeign('hall_blocks_hall_id_foreign');
        });
        Schema::table('hall_equipment', function (Blueprint $table) {
            $table->dropForeign('hall_equipment_hall_id_foreign');
            $table->dropForeign('hall_equipment_equipment_id_foreign');
        });
        Schema::table('halls', function (Blueprint $table) {
            $table->dropForeign('halls_branch_id_foreign');
        });
        Schema::table('course_criteria', function (Blueprint $table) {
            $table->dropForeign('course_criteria_course_id_foreign');
            $table->dropForeign('course_criteria_criteria_type_id_foreign');
        });
        Schema::table('course_modules', function (Blueprint $table) {
            $table->dropForeign('course_modules_course_id_foreign');
        });
        Schema::table('course_prerequisites', function (Blueprint $table) {
            $table->dropForeign('course_prerequisites_course_id_foreign');
            $table->dropForeign('course_prerequisites_prerequisite_course_id_foreign');
        });
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign('courses_category_id_foreign');
            $table->dropForeign('courses_course_type_id_foreign');
        });
        Schema::table('trainee_warnings', function (Blueprint $table) {
            $table->dropForeign('trainee_warnings_trainee_id_foreign');
            $table->dropForeign('trainee_warnings_session_id_foreign');
            $table->dropForeign('trainee_warnings_issued_by_foreign');
        });
        Schema::table('trainee_providers', function (Blueprint $table) {
            $table->dropForeign('trainee_providers_trainee_id_foreign');
            $table->dropForeign('trainee_providers_provider_id_foreign');
        });
        Schema::table('trainees', function (Blueprint $table) {
            $table->dropForeign('trainees_education_level_id_foreign');
            $table->dropForeign('trainees_job_grade_id_foreign');
        });
        Schema::table('trainer_course_approvals', function (Blueprint $table) {
            $table->dropForeign('trainer_course_approvals_trainer_id_foreign');
            $table->dropForeign('trainer_course_approvals_course_id_foreign');
            $table->dropForeign('trainer_course_approvals_approved_by_foreign');
        });
        Schema::table('trainer_specialties', function (Blueprint $table) {
            $table->dropForeign('trainer_specialties_trainer_id_foreign');
            $table->dropForeign('trainer_specialties_category_id_foreign');
        });
        Schema::table('trainer_experiences', function (Blueprint $table) {
            $table->dropForeign('trainer_experiences_trainer_id_foreign');
        });
        Schema::table('trainer_certifications', function (Blueprint $table) {
            $table->dropForeign('trainer_certifications_trainer_id_foreign');
        });
        Schema::table('trainers', function (Blueprint $table) {
            $table->dropForeign('trainers_provider_id_foreign');
            $table->dropForeign('trainers_branch_id_foreign');
        });
        Schema::table('course_categories', function (Blueprint $table) {
            $table->dropForeign('course_categories_parent_id_foreign');
        });
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropForeign('attachments_uploaded_by_foreign');
        });
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign('audit_logs_user_id_foreign');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_branch_id_foreign');
            $table->dropForeign('users_trainer_id_foreign');
            $table->dropForeign('users_trainee_id_foreign');
        });
        Schema::table('branch_accreditations', function (Blueprint $table) {
            $table->dropForeign('branch_accreditations_branch_id_foreign');
            $table->dropForeign('branch_accreditations_accreditation_id_foreign');
        });
    }
};
