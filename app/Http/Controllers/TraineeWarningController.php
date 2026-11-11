<?php

namespace App\Http\Controllers;

use App\Enums\WarningType;
use App\Models\Trainee;
use App\Models\TraineeWarning;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Records disciplinary warnings/notes for a trainee.
 * Access is guarded by the WARNINGS_MANAGE permission on the routes.
 */
class TraineeWarningController extends Controller
{
    public function store(Request $request, Trainee $trainee): RedirectResponse
    {
        $data = $request->validate([
            'warning_type' => ['required', Rule::enum(WarningType::class)],
            'session_id' => ['nullable', 'integer', 'exists:course_sessions,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'issued_at' => ['nullable', 'date', 'before_or_equal:today'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'required' => 'حقل :attribute مطلوب.',
            'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
            'boolean' => 'حقل :attribute يجب أن يكون نعم/لا.',
            'date' => 'حقل :attribute يجب أن يكون تاريخًا صحيحًا.',
            'before_or_equal' => 'حقل :attribute يجب أن يكون في الماضي أو اليوم.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.',
            'exists' => 'الجلسة المحددة غير موجودة.',
            'enum' => 'القيمة المحددة في :attribute غير صالحة.',
        ], [
            'warning_type' => 'نوع الإنذار',
            'session_id' => 'الجلسة المرتبطة',
            'description' => 'الوصف',
            'issued_at' => 'تاريخ الإصدار',
            'is_active' => 'حالة الإنذار',
        ]);

        $data['issued_by'] = $request->user()?->id;
        $data['issued_at'] = $data['issued_at'] ?? now()->toDateString();
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $trainee->traineeWarnings()->create($data);

        return back()->with('success', 'تم تسجيل الإنذار/الملاحظة بنجاح.');
    }

    public function destroy(Trainee $trainee, TraineeWarning $warning): RedirectResponse
    {
        abort_unless((int) $warning->trainee_id === (int) $trainee->id, 404);

        $warning->delete();

        return back()->with('success', 'تم حذف سجل الإنذار بنجاح.');
    }
}
