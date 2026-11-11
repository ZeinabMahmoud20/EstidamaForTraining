<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeSessionHallRequest;
use App\Models\CourseSession;
use App\Services\CourseSessionService;
use Illuminate\Http\RedirectResponse;

class SessionHallController extends Controller
{
    public function __construct(private readonly CourseSessionService $sessions) {}

    public function update(ChangeSessionHallRequest $request, CourseSession $session): RedirectResponse
    {
        $day = $session->sessionDays()->findOrFail($request->integer('day_id'));

        $changed = $this->sessions->changeHall(
            $day,
            $request->filled('hall_id') ? $request->integer('hall_id') : null,
            $request->input('reason'),
            $request->user()?->id,
        );

        return redirect()
            ->route('sessions.show', $session)
            ->with('success', $changed
                ? 'تم تغيير قاعة اليوم وتسجيل التغيير في السجل.'
                : 'القاعة المحددة هي نفسها القاعة الحالية، ولم يُسجّل تغيير.');
    }
}
