<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\ExcuseStatus;
use App\Enums\SessionStatus;
use App\Http\Requests\RecordAttendanceRequest;
use App\Http\Requests\ReviewExcuseRequest;
use App\Models\AttendanceExcuse;
use App\Models\CourseSession;
use App\Models\SessionDay;
use App\Models\SessionDayTrainer;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendance) {}

    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $sessions = CourseSession::query()
            ->with('course')
            ->withCount(['sessionDays', 'enrollments'])
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(fn ($q) => $q
                    ->where('session_code', 'like', $like)
                    ->orWhereHas('course', fn ($courseQuery) => $courseQuery
                        ->where('title', 'like', $like)
                        ->orWhere('code', 'like', $like)));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('attendance.index', [
            'sessions' => $sessions,
            'statuses' => SessionStatus::options(),
        ]);
    }

    public function session(Request $request, CourseSession $session): View
    {
        $session->load([
            'course',
            'sessionDays' => fn ($query) => $query->orderBy('day_no'),
            'sessionDays.hall',
            'enrollments' => fn ($query) => $query->whereNotIn('status', ['withdrawn', 'dismissed']),
            'enrollments.trainee',
            'sessionTrainers.trainer',
        ]);

        $day = $request->filled('day')
            ? $session->sessionDays->firstWhere('id', (int) $request->input('day'))
            : $session->sessionDays->first();

        $trainers = collect();
        $traineeAttendance = collect();
        $trainerAttendance = collect();

        if ($day) {
            $dayTrainers = SessionDayTrainer::query()
                ->with('trainer')
                ->where('session_day_id', $day->id)
                ->get();

            $trainers = $dayTrainers->isNotEmpty()
                ? $dayTrainers->pluck('trainer')->filter()
                : $session->sessionTrainers->pluck('trainer')->filter();

            $traineeAttendance = $day->traineeAttendance()
                ->with('excuse')
                ->get()
                ->keyBy('enrollment_id');

            $trainerAttendance = $day->trainerAttendance()
                ->with('excuse')
                ->get()
                ->keyBy('trainer_id');
        }

        return view('attendance.session', [
            'session' => $session,
            'day' => $day,
            'trainers' => $trainers,
            'traineeAttendance' => $traineeAttendance,
            'trainerAttendance' => $trainerAttendance,
            'statuses' => AttendanceStatus::options(),
        ]);
    }

    public function record(RecordAttendanceRequest $request, CourseSession $session, SessionDay $day): RedirectResponse
    {
        $result = $this->attendance->saveDay($session, $day, $request->validated(), $request->user());

        return redirect()
            ->route('attendance.session', ['session' => $session, 'day' => $day->id])
            ->with('success', 'تم حفظ الحضور: '.$result['trainees'].' متدرب و'.$result['trainers'].' مدرب.');
    }

    public function excuses(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $excuses = AttendanceExcuse::query()
            ->with([
                'reviewedBy',
                'traineeAttendance.enrollment.trainee',
                'traineeAttendance.sessionDay.session.course',
                'trainerAttendance.trainer',
                'trainerAttendance.sessionDay.session.course',
            ])
            ->when($term !== '', fn ($query) => $query->where('reason', 'like', '%'.$term.'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('attendance.excuses', [
            'excuses' => $excuses,
            'statuses' => ExcuseStatus::options(),
        ]);
    }

    public function reviewExcuse(ReviewExcuseRequest $request, AttendanceExcuse $excuse): RedirectResponse
    {
        $this->attendance->reviewExcuse($excuse, $request->validated(), $request->user());

        return back()->with('success', 'تم تحديث حالة العذر.');
    }

    public function own(Request $request): View
    {
        $user = $request->user();
        $traineeEnrollments = collect();
        $trainerAttendance = collect();

        if ($user?->trainee_id) {
            $traineeEnrollments = $user->trainee
                ?->enrollments()
                ->with(['session.course', 'traineeAttendance.sessionDay'])
                ->orderByDesc('created_at')
                ->get() ?? collect();
        }

        if ($user?->trainer_id) {
            $trainerAttendance = $user->trainer
                ?->trainerAttendance()
                ->with(['sessionDay.session.course'])
                ->orderByDesc('id')
                ->get() ?? collect();
        }

        return view('attendance.own', [
            'traineeEnrollments' => $traineeEnrollments,
            'trainerAttendance' => $trainerAttendance,
        ]);
    }
}
