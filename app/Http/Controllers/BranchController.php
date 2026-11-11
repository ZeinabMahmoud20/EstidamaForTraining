<?php

namespace App\Http\Controllers;

use App\Http\Requests\BranchAccreditationRequest;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Accreditation;
use App\Models\Branch;
use App\Models\BranchAccreditation;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $branches = Branch::query()
            ->withCount(['users', 'trainers', 'halls', 'courseSessions', 'branchAccreditations'])
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(fn ($q) => $q
                    ->where('code', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('phone', 'like', $like));
            })
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('branches.index', compact('branches'));
    }

    public function create(): View
    {
        return view('branches.create');
    }

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $branch = DB::transaction(function () use ($request) {
            $branch = Branch::create($request->validated());

            $this->audit->record('branch.created', $branch, null, $this->auditValues($branch), $request->user());

            return $branch;
        });

        return redirect()
            ->route('branches.show', $branch)
            ->with('success', 'تمت إضافة الفرع بنجاح.');
    }

    public function show(Branch $branch): View
    {
        $branch->load([
            'branchAccreditations.accreditation',
            'courseSessions.course',
        ])->loadCount(['users', 'trainers', 'halls', 'courseSessions']);

        return view('branches.show', [
            'branch' => $branch,
            'accreditations' => Accreditation::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function edit(Branch $branch): View
    {
        return view('branches.edit', compact('branch'));
    }

    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        DB::transaction(function () use ($request, $branch) {
            $before = $this->auditValues($branch);

            $branch->update($request->validated());

            $this->audit->record('branch.updated', $branch, $before, $this->auditValues($branch), $request->user());
        });

        return redirect()
            ->route('branches.show', $branch)
            ->with('success', 'تم تحديث الفرع بنجاح.');
    }

    public function destroy(Request $request, Branch $branch): RedirectResponse
    {
        if ($blockers = $this->deletionBlockers($branch)) {
            return redirect()
                ->route('branches.show', $branch)
                ->with('error', 'لا يمكن حذف الفرع لارتباطه بـ: '.implode('، ', $blockers).'.');
        }

        DB::transaction(function () use ($request, $branch) {
            $this->audit->record('branch.deleted', $branch, $this->auditValues($branch), null, $request->user());
            $branch->delete();
        });

        return redirect()
            ->route('branches.index')
            ->with('success', 'تم حذف الفرع بنجاح.');
    }

    public function storeAccreditation(BranchAccreditationRequest $request, Branch $branch): RedirectResponse
    {
        $link = DB::transaction(function () use ($request, $branch) {
            $link = $branch->branchAccreditations()->create($request->validated());
            $link->load('accreditation');

            $this->audit->record('branch_accreditation.created', $link, null, $this->auditValues($link), $request->user());

            return $link;
        });

        return redirect()
            ->route('branches.show', $branch)
            ->with('success', 'تم ربط الاعتماد بالفرع بنجاح.');
    }

    public function updateAccreditation(
        BranchAccreditationRequest $request,
        Branch $branch,
        BranchAccreditation $branchAccreditation,
    ): RedirectResponse {
        $this->ensureBelongsToBranch($branch, $branchAccreditation);

        DB::transaction(function () use ($request, $branchAccreditation) {
            $before = $this->auditValues($branchAccreditation);

            $branchAccreditation->update($request->validated());

            $this->audit->record(
                'branch_accreditation.updated',
                $branchAccreditation,
                $before,
                $this->auditValues($branchAccreditation),
                $request->user()
            );
        });

        return redirect()
            ->route('branches.show', $branch)
            ->with('success', 'تم تحديث اعتماد الفرع بنجاح.');
    }

    public function destroyAccreditation(
        Request $request,
        Branch $branch,
        BranchAccreditation $branchAccreditation,
    ): RedirectResponse {
        $this->ensureBelongsToBranch($branch, $branchAccreditation);

        DB::transaction(function () use ($request, $branchAccreditation) {
            $this->audit->record(
                'branch_accreditation.deleted',
                $branchAccreditation,
                $this->auditValues($branchAccreditation),
                null,
                $request->user()
            );

            $branchAccreditation->delete();
        });

        return redirect()
            ->route('branches.show', $branch)
            ->with('success', 'تم حذف اعتماد الفرع بنجاح.');
    }

    private function ensureBelongsToBranch(Branch $branch, BranchAccreditation $link): void
    {
        abort_unless((int) $link->branch_id === (int) $branch->id, 404);
    }

    /**
     * @return list<string>
     */
    private function deletionBlockers(Branch $branch): array
    {
        $blockers = [];

        if ($branch->users()->exists()) {
            $blockers[] = 'مستخدمون';
        }
        if ($branch->trainers()->exists()) {
            $blockers[] = 'مدربون';
        }
        if ($branch->halls()->exists()) {
            $blockers[] = 'قاعات';
        }
        if ($branch->courseSessions()->exists()) {
            $blockers[] = 'جلسات مجدولة';
        }
        if ($branch->branchAccreditations()->exists()) {
            $blockers[] = 'اعتمادات مرتبطة';
        }

        return array_values(array_unique($blockers));
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(Branch|BranchAccreditation $model): array
    {
        if ($model instanceof BranchAccreditation) {
            return [
                'branch_id' => $model->branch_id,
                'accreditation_id' => $model->accreditation_id,
                'issue_date' => $model->issue_date?->toDateString(),
                'expiry_date' => $model->expiry_date?->toDateString(),
                'is_active' => $model->is_active,
            ];
        }

        return [
            'code' => $model->code,
            'name' => $model->name,
            'city' => $model->city,
            'address' => $model->address,
            'phone' => $model->phone,
            'is_active' => $model->is_active,
        ];
    }
}
