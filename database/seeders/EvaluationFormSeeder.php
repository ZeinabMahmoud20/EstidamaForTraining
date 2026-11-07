<?php

namespace Database\Seeders;

use App\Models\EvaluationForm;
use Illuminate\Database\Seeder;

/** Pre-programmed (system) evaluation forms. Re-running never touches a form that already exists. */
class EvaluationFormSeeder extends Seeder
{
    public function run(): void
    {
        $s = fn (string $t) => [$t, 'scale_1_5'];
        $t = fn (string $x) => [$x, 'text'];

        $forms = [
            ['TRAINEE_EVAL_TRAINER', 'تقييم المتدرب للمدرب', 'trainer', 'trainee', true, [
                'التمكّن العلمي' => [$s('أظهر المدرب إلمامًا كاملاً بالمادة العلمية'), $s('أجاب عن الأسئلة بوضوح ودقة')],
                'أسلوب العرض والتفاعل' => [$s('عرض المحتوى بأسلوب واضح ومشوّق'), $s('شجّع المشاركة والتفاعل داخل القاعة')],
                'الالتزام' => [$s('التزم بمواعيد البدء والانتهاء'), $t('ملاحظات إضافية على المدرب')],
            ]],
            ['TRAINEE_EVAL_PROGRAM', 'تقييم المتدرب للبرنامج التدريبي', 'program', 'trainee', true, [
                'المحتوى' => [$s('المحتوى مناسب لاحتياجاتي'), $s('المادة التدريبية واضحة ومنظمة')],
                'التنظيم' => [$s('مدة البرنامج مناسبة'), $t('اقتراحات لتطوير البرنامج')],
            ]],
            ['TRAINEE_EVAL_VENUE', 'تقييم المتدرب للقاعة والتجهيزات', 'venue', 'trainee', true, [
                'القاعة والتجهيزات' => [$s('نظافة القاعة وتهويتها'), $s('جودة الأجهزة والتجهيزات'), $t('ملاحظات على القاعة')],
            ]],
            ['TRAINEE_EVAL_OPERATIONS', 'تقييم المتدرب للخدمات الإدارية', 'operations', 'trainee', true, [
                'الخدمات الإدارية' => [$s('سهولة التسجيل والتواصل مع المركز'), $s('سرعة الاستجابة للاستفسارات'), $t('ملاحظات إدارية')],
            ]],
            ['TRAINER_EVAL_TRAINEE', 'تقييم المدرب للمتدرب', 'trainee', 'trainer', false, [
                'السلوك والمشاركة' => [$s('الانضباط والحضور'), $s('المشاركة الفعّالة'), $s('القدرة على الاستيعاب والتطبيق'), $t('ملاحظات المدرب')],
            ]],
        ];

        foreach ($forms as [$code, $title, $target, $evaluator, $anonymous, $sections]) {
            $form = EvaluationForm::firstOrCreate(['code' => $code], [
                'title' => $title,
                'target_type' => $target,
                'evaluator_type' => $evaluator,
                'is_anonymous' => $anonymous,
                'is_system' => true,
                'version' => 1,
                'is_active' => true,
            ]);

            if (! $form->wasRecentlyCreated) {
                continue;
            }

            foreach (array_keys($sections) as $si => $sectionTitle) {
                $section = $form->evaluationSections()->create(['title' => $sectionTitle, 'weight' => 1, 'sort_order' => $si + 1]);
                foreach ($sections[$sectionTitle] as $ii => [$text, $type]) {
                    $form->evaluationItems()->create([
                        'section_id' => $section->id,
                        'item_text' => $text,
                        'answer_type' => $type,
                        'weight' => 1,
                        'is_required' => $type !== 'text',
                        'sort_order' => $ii + 1,
                    ]);
                }
            }
        }
    }
}
