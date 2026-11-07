<?php

namespace App\Http\Controllers;

use App\Models\Trainer;
use App\Models\TrainerCourseApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Approves (or revokes) a trainer for a specific course.
 * Access is guarded by the TRAINER_APPROVE_COURSE permission on the routes.
 */
class TrainerApprovalController extends Controller
{
    public function store(Request $request, Trainer $trainer): RedirectResponse
    {
        $data = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'is_approved' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:300'],
        ], [
            'required' => 'حقل :attribute مطلوب.',
            'integer' => 'حقل :attribute يجب أن يكون عددًا صحيحًا.',
            'boolean' => 'حقل :attribute يجب أن يكون نعم/لا.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max حرفًا.',
            'exists' => 'الدورة المحددة غير موجودة.',
        ], [
            'course_id' => 'الدورة',
            'is_approved' => 'حالة الاعتماد',
            'notes' => 'ملاحظات الاعتماد',
        ]);

        $approved = (bool) $data['is_approved'];

        $trainer->trainerCourseApprovals()->updateOrCreate(
            ['course_id' => $data['course_id']],
            [
                'is_approved' => $approved,
                'approved_by' => $approved ? $request->user()?->id : null,
                'approved_at' => $approved ? now()->toDateString() : null,
                'notes' => $data['notes'] ?? null,
            ]
        );

        return back()->with('success', $approved
            ? 'تم اعتماد المدرب لتدريس الدورة.'
            : 'تم إلغاء اعتماد المدرب للدورة.');
    }

    public function destroy(Trainer $trainer, TrainerCourseApproval $approval): RedirectResponse
    {
        abort_unless((int) $approval->trainer_id === (int) $trainer->id, 404);

        $approval->delete();

        return back()->with('success', 'تم حذف سجل الاعتماد.');
    }
}
