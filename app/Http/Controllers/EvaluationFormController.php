<?php

namespace App\Http\Controllers;

use App\Enums\EvalAnswerType;
use App\Enums\EvalEvaluator;
use App\Enums\EvalTarget;
use App\Http\Requests\StoreEvaluationFormRequest;
use App\Http\Requests\UpdateEvaluationFormRequest;
use App\Models\EvaluationForm;
use App\Services\EvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EvaluationFormController extends Controller
{
    public function __construct(private readonly EvaluationService $evaluations) {}

    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $forms = EvaluationForm::query()
            ->withCount(['evaluationSections', 'evaluationItems', 'evaluationSubmissions'])
            ->when($term !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('title', 'like', '%'.$term.'%')
                ->orWhere('code', 'like', '%'.$term.'%')))
            ->when($request->filled('target_type'), fn ($q) => $q->where('target_type', $request->input('target_type')))
            ->when($request->filled('evaluator_type'), fn ($q) => $q->where('evaluator_type', $request->input('evaluator_type')))
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('evaluations.index', [
            'forms' => $forms,
            'targets' => EvalTarget::options(),
            'evaluators' => EvalEvaluator::options(),
        ]);
    }

    public function create(): View
    {
        return view('evaluations.create', $this->formData());
    }

    public function store(StoreEvaluationFormRequest $request): RedirectResponse
    {
        $form = $this->evaluations->saveForm(new EvaluationForm, $request->validated(), $request->user());

        return redirect()
            ->route('evaluations.edit', $form)
            ->with('success', 'تم إنشاء نموذج التقييم بنجاح.');
    }

    public function show(EvaluationForm $evaluation): View
    {
        $evaluation->load([
            'createdBy',
            'evaluationSections' => fn ($query) => $query->orderBy('sort_order'),
            'evaluationSections.evaluationItems' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        return view('evaluations.show', ['form' => $evaluation]);
    }

    public function edit(EvaluationForm $evaluation): View
    {
        $evaluation->load([
            'evaluationSections' => fn ($query) => $query->orderBy('sort_order'),
            'evaluationSections.evaluationItems' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        return view('evaluations.edit', $this->formData() + ['form' => $evaluation]);
    }

    public function update(UpdateEvaluationFormRequest $request, EvaluationForm $evaluation): RedirectResponse
    {
        $this->evaluations->saveForm($evaluation, $request->validated(), $request->user());

        return redirect()
            ->route('evaluations.edit', $evaluation)
            ->with('success', 'تم تحديث نموذج التقييم بنجاح.');
    }

    public function destroy(EvaluationForm $evaluation): RedirectResponse
    {
        try {
            $this->evaluations->deleteForm($evaluation);
        } catch (ValidationException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('evaluations.index')
            ->with('success', 'تم حذف نموذج التقييم.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'targets' => EvalTarget::options(),
            'evaluators' => EvalEvaluator::options(),
            'answerTypes' => EvalAnswerType::options(),
        ];
    }
}
