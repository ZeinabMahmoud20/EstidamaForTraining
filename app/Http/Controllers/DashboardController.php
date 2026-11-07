<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Nomination;
use App\Models\Trainee;
use App\Models\Trainer;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $stats = [];

        if ($user->can('COURSES_VIEW')) {
            $stats[] = ['label' => 'الدورات', 'value' => Course::count(), 'icon' => 'fa-book-open', 'tone' => ''];
        }
        if ($user->can('SESSIONS_MANAGE')) {
            $stats[] = ['label' => 'الجلسات المجدولة', 'value' => CourseSession::count(), 'icon' => 'fa-calendar-days', 'tone' => ''];
        }
        if ($user->can('TRAINEES_MANAGE')) {
            $stats[] = ['label' => 'المتدربون', 'value' => Trainee::count(), 'icon' => 'fa-users', 'tone' => 'ok'];
        }
        if ($user->can('TRAINERS_MANAGE')) {
            $stats[] = ['label' => 'المدربون', 'value' => Trainer::count(), 'icon' => 'fa-chalkboard-user', 'tone' => ''];
        }
        if ($user->can('REVIEW_NOMINATION')) {
            $stats[] = ['label' => 'ترشيحات بانتظار المراجعة', 'value' => Nomination::where('status', 'submitted')->count(), 'icon' => 'fa-user-check', 'tone' => 'accent'];
        }

        return view('dashboard', compact('user', 'stats'));
    }
}
