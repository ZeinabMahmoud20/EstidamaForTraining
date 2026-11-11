<?php

namespace App\Http\Controllers;

use App\Enums\ExamType;
use App\Http\Requests\GradeExamRequest;
use App\Http\Requests\StoreExamRequest;
use App\Http\Requests\UpdateExamRequest;
use App\Models\Exam;
use App\Models\Question;
use App\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function __construct(private readonly ExamService $exams) {}

    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $exams = Exam::query()
            ->with(['session.course'])
            ->withCount(['questions', 'examResults'])
            ->when($term !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('title', 'like', '%'.$term.'%')
                ->orWhereHas('session', fn ($session) => $session
                    ->where('session_code', 'like', '%'.$term.'%')
                    ->orWhereHas('course', fn ($course) => $course->where('title', 'like', '%'.$term.'%')))))
            ->when($request->filled('session_id'), fn ($q) => $q->where('session_id', $request->input('session_id')))
            ->when($request->filled('exam_type'), fn ($q) => $q->where('exam_type', $request->input('exam_type')))
            ->orderByDesc('scheduled_start')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('exams.index', [
            'exams' => $exams,
            'sessions' => $this->exams->sessionOptions(),
            'examTypes' => ExamType::options(),
        ]);
    }

    public function create(): View
    {
        return view('exams.create', $this->formData());
    }

    public function store(StoreExamRequest $request): RedirectResponse
    {
        $exam = $this->exams->saveExam(new Exam, $request->validated(), $request->user());

        return redirect()
            ->route('exams.show', $exam)
            ->with('success', 'تم إنشاء الاختبار بنجاح.');
    }

    public function show(Exam $exam): View
    {
        $exam->load([
            'session.course',
            'createdBy',
            'questions' => fn ($query) => $query->orderBy('exam_questions.sort_order'),
            'questions.bank.course',
            'examResults.enrollment.trainee',
            'examResults.gradedBy',
        ]);

        $enrollments = $exam->session
            ? $exam->session->enrollments()
                ->with('trainee')
                ->whereNotIn('status', ['withdrawn', 'dismissed'])
                ->orderBy('id')
                ->get()
            : collect();

        return view('exams.show', [
            'exam' => $exam,
            'enrollments' => $enrollments,
            'results' => $exam->examResults->keyBy('enrollment_id'),
        ]);
    }

    public function edit(Exam $exam): View
    {
        $exam->load('questions');

        return view('exams.edit', $this->formData() + ['exam' => $exam]);
    }

    public function update(UpdateExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->exams->saveExam($exam, $request->validated(), $request->user());

        return redirect()
            ->route('exams.show', $exam)
            ->with('success', 'تم تحديث الاختبار بنجاح.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        try {
            $this->exams->deleteExam($exam);
        } catch (ValidationException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('exams.index')
            ->with('success', 'تم حذف الاختبار.');
    }

    public function grade(GradeExamRequest $request, Exam $exam): RedirectResponse
    {
        $result = $this->exams->grade($exam, $request->validated(), $request->user());

        return redirect()
            ->route('exams.show', $exam)
            ->with('success', 'تم حفظ النتائج: '.$result['graded'].' متدرب مصحح.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'sessions' => $this->exams->sessionOptions(),
            'examTypes' => ExamType::options(),
            'questions' => Question::query()
                ->with(['bank.course'])
                ->where('is_active', true)
                ->orderBy('bank_id')
                ->orderBy('id')
                ->get(),
        ];
    }
}
