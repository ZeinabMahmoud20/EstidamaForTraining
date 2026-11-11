<?php

namespace App\Http\Controllers;

use App\Enums\QuestionType;
use App\Http\Requests\StoreQuestionBankRequest;
use App\Http\Requests\UpdateQuestionBankRequest;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\QuestionBank;
use App\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuestionBankController extends Controller
{
    public function __construct(private readonly ExamService $exams) {}

    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $banks = QuestionBank::query()
            ->with(['course', 'module'])
            ->withCount('questions')
            ->when($term !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', '%'.$term.'%')
                ->orWhereHas('course', fn ($course) => $course->where('title', 'like', '%'.$term.'%'))))
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->input('course_id')))
            ->orderBy('course_id')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('question-banks.index', [
            'banks' => $banks,
            'courses' => $this->courseOptions(),
        ]);
    }

    public function create(): View
    {
        return view('question-banks.create', $this->formData());
    }

    public function store(StoreQuestionBankRequest $request): RedirectResponse
    {
        $bank = $this->exams->saveBank(new QuestionBank, $request->validated());

        return redirect()
            ->route('question-banks.edit', $bank)
            ->with('success', 'تم إنشاء بنك الأسئلة بنجاح.');
    }

    public function edit(QuestionBank $bank): View
    {
        $bank->load(['questions' => fn ($query) => $query->orderBy('id'), 'questions.questionOptions']);

        return view('question-banks.edit', $this->formData() + ['bank' => $bank]);
    }

    public function update(UpdateQuestionBankRequest $request, QuestionBank $bank): RedirectResponse
    {
        $this->exams->saveBank($bank, $request->validated());

        return redirect()
            ->route('question-banks.edit', $bank)
            ->with('success', 'تم تحديث بنك الأسئلة بنجاح.');
    }

    public function destroy(QuestionBank $bank): RedirectResponse
    {
        try {
            $this->exams->deleteBank($bank);
        } catch (ValidationException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('question-banks.index')
            ->with('success', 'تم حذف بنك الأسئلة.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'courses' => $this->courseOptions(),
            'modules' => CourseModule::query()
                ->with('course')
                ->orderBy('course_id')
                ->get()
                ->mapWithKeys(fn (CourseModule $module) => [
                    $module->id => ($module->course?->title ?? 'دورة محذوفة').' — '.$module->title,
                ])
                ->all(),
            'questionTypes' => QuestionType::options(),
        ];
    }

    /** @return array<int, string> */
    private function courseOptions(): array
    {
        return Course::query()->orderBy('title')->pluck('title', 'id')->all();
    }
}
