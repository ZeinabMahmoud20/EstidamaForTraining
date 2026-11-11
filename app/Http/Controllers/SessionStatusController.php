<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Http\Requests\UpdateSessionStatusRequest;
use App\Models\CourseSession;
use App\Services\CourseSessionService;
use Illuminate\Http\RedirectResponse;

class SessionStatusController extends Controller
{
    public function __construct(private readonly CourseSessionService $sessions) {}

    public function update(UpdateSessionStatusRequest $request, CourseSession $session): RedirectResponse
    {
        $this->sessions->transitionStatus(
            $session,
            SessionStatus::from($request->validated('status')),
        );

        return redirect()
            ->route('sessions.show', $session)
            ->with('success', 'تم تحديث حالة الجلسة بنجاح.');
    }
}
