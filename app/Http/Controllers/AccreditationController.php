<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccreditationRequest;
use App\Http\Requests\UpdateAccreditationRequest;
use App\Models\Accreditation;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccreditationController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $accreditations = Accreditation::query()
            ->withCount(['branchAccreditations', 'courseSessions'])
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(fn ($q) => $q
                    ->where('name', 'like', $like)
                    ->orWhere('issuing_body', 'like', $like));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('accreditations.index', compact('accreditations'));
    }

    public function create(): View
    {
        return view('accreditations.create');
    }

    public function store(StoreAccreditationRequest $request): RedirectResponse
    {
        $accreditation = DB::transaction(function () use ($request) {
            $accreditation = Accreditation::create($request->validated());

            $this->audit->record(
                'accreditation.created',
                $accreditation,
                null,
                $this->auditValues($accreditation),
                $request->user()
            );

            return $accreditation;
        });

        return redirect()
            ->route('accreditations.index')
            ->with('success', 'تمت إضافة الاعتماد بنجاح.');
    }

    public function edit(Accreditation $accreditation): View
    {
        return view('accreditations.edit', compact('accreditation'));
    }

    public function update(UpdateAccreditationRequest $request, Accreditation $accreditation): RedirectResponse
    {
        DB::transaction(function () use ($request, $accreditation) {
            $before = $this->auditValues($accreditation);

            $accreditation->update($request->validated());

            $this->audit->record(
                'accreditation.updated',
                $accreditation,
                $before,
                $this->auditValues($accreditation),
                $request->user()
            );
        });

        return redirect()
            ->route('accreditations.index')
            ->with('success', 'تم تحديث الاعتماد بنجاح.');
    }

    public function destroy(Request $request, Accreditation $accreditation): RedirectResponse
    {
        if ($accreditation->branchAccreditations()->exists() || $accreditation->courseSessions()->exists()) {
            return redirect()
                ->route('accreditations.index')
                ->with('error', 'لا يمكن حذف الاعتماد لارتباطه بفروع أو جلسات تدريبية.');
        }

        DB::transaction(function () use ($request, $accreditation) {
            $this->audit->record(
                'accreditation.deleted',
                $accreditation,
                $this->auditValues($accreditation),
                null,
                $request->user()
            );

            $accreditation->delete();
        });

        return redirect()
            ->route('accreditations.index')
            ->with('success', 'تم حذف الاعتماد بنجاح.');
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(Accreditation $accreditation): array
    {
        return [
            'name' => $accreditation->name,
            'issuing_body' => $accreditation->issuing_body,
            'description' => $accreditation->description,
        ];
    }
}
