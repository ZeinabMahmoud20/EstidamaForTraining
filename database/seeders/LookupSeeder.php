<?php

namespace Database\Seeders;

use App\Models\CourseCategory;
use App\Models\CourseType;
use App\Models\CriteriaType;
use App\Models\EducationLevel;
use App\Models\JobGrade;
use Illuminate\Database\Seeder;

/** Starter values - all editable later from the admin screens. */
class LookupSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['ثانوية عامة', 'دبلوم متوسط', 'مؤهل عالٍ (بكالوريوس / ليسانس)', 'دبلوم دراسات عليا', 'ماجستير', 'دكتوراه'] as $i => $name) {
            EducationLevel::updateOrCreate(['name' => $name], ['rank_order' => $i + 1]);
        }

        foreach (['الدرجة الثالثة', 'الدرجة الثانية', 'الدرجة الأولى', 'كبير', 'مدير عام'] as $i => $name) {
            JobGrade::updateOrCreate(['name' => $name], ['rank_order' => $i + 1]);
        }

        foreach ([
            ['دورة تدريبية', 'برنامج تدريبي قصير'],
            ['ورشة عمل', 'تدريب عملي مكثف'],
            ['برنامج دبلومة', 'برنامج طويل المدى متعدد المحاور'],
            ['ندوة / محاضرة', 'لقاء علمي أو توعوي'],
        ] as [$name, $desc]) {
            CourseType::updateOrCreate(['name' => $name], ['description' => $desc]);
        }

        foreach ([
            'الإدارة والقيادة',
            'المالية والمحاسبة',
            'الموارد البشرية',
            'تقنية المعلومات',
            'السلامة والصحة المهنية',
            'المهارات الشخصية والتطوير الذاتي',
        ] as $name) {
            CourseCategory::updateOrCreate(['name' => $name], ['parent_id' => null]);
        }

        foreach ([
            ['MIN_EDUCATION', 'الحد الأدنى للمؤهل الدراسي', 'يجب ألا يقل المؤهل عن مستوى محدد'],
            ['MIN_EXPERIENCE', 'الحد الأدنى لسنوات الخبرة', null],
            ['JOB_TITLE', 'المسمى الوظيفي', 'المسمى ضمن قائمة محددة'],
            ['JOB_GRADE', 'الدرجة الوظيفية', null],
            ['MIN_AGE', 'الحد الأدنى للسن', null],
            ['MAX_AGE', 'الحد الأقصى للسن', null],
            ['PASSED_PREREQ', 'اجتياز المتطلب السابق', 'اجتياز الدورات المطلوبة قبل هذه الدورة'],
            ['NOT_TAKEN_WITHIN_YEARS', 'عدم الالتحاق بنفس الدورة خلال عدد من السنوات', null],
            ['NO_ACTIVE_WARNING', 'عدم وجود إنذار ساري', null],
        ] as [$code, $name, $desc]) {
            CriteriaType::updateOrCreate(['code' => $code], ['name' => $name, 'description' => $desc]);
        }
    }
}
