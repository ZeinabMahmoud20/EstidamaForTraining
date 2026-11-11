<?php

namespace App\Http\Controllers;

use App\Enums\HallBlockReason;
use App\Http\Requests\StoreHallRequest;
use App\Http\Requests\UpdateHallRequest;
use App\Models\Branch;
use App\Models\Equipment;
use App\Models\Hall;
use App\Services\HallService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HallController extends Controller
{
    public function __construct(private readonly HallService $halls) {}

    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $tab = $request->input('tab') === 'equipment' && $request->user()?->can('HALLS_MANAGE')
            ? 'equipment'
            : 'halls';

        $halls = Hall::query()
            ->with(['branch'])
            ->withCount(['equipment', 'sessionDays'])
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(fn ($q) => $q
                    ->where('code', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('floor', 'like', $like));
            })
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->input('branch_id')))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('halls.index', [
            'tab' => $tab,
            'halls' => $halls,
            'equipment' => Equipment::withCount('halls')->orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(): View
    {
        return view('halls.create', $this->formData());
    }

    public function store(StoreHallRequest $request): RedirectResponse
    {
        $hall = $this->halls->create($request->validated());

        return redirect()
            ->route('halls.show', $hall)
            ->with('success', 'تمت إضافة القاعة بنجاح.');
    }

    public function show(Hall $hall): View
    {
        $hall->load([
            'branch',
            'equipment' => fn ($query) => $query->orderBy('name'),
            'hallBlocks' => fn ($query) => $query->orderByDesc('start_at'),
            'sessionDays' => fn ($query) => $query->orderBy('day_date')->orderBy('start_time'),
            'sessionDays.session.course',
        ]);

        return view('halls.show', [
            'hall' => $hall,
            'blockReasons' => HallBlockReason::options(),
        ]);
    }

    public function edit(Hall $hall): View
    {
        $hall->load('equipment');

        return view('halls.edit', [
            'hall' => $hall,
            ...$this->formData(),
        ]);
    }

    public function update(UpdateHallRequest $request, Hall $hall): RedirectResponse
    {
        $this->halls->update($hall, $request->validated());

        return redirect()
            ->route('halls.show', $hall)
            ->with('success', 'تم تحديث القاعة بنجاح.');
    }

    public function destroy(Hall $hall): RedirectResponse
    {
        if ($blockers = $this->halls->deletionBlockers($hall)) {
            return redirect()
                ->route('halls.show', $hall)
                ->with('error', 'لا يمكن حذف القاعة لارتباطها بـ: '.implode('، ', $blockers).'.');
        }

        $this->halls->delete($hall);

        return redirect()
            ->route('halls.index')
            ->with('success', 'تم حذف القاعة بنجاح.');
    }

    /**
     * Lookups required by the create/edit form.
     *
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'branches' => Branch::orderBy('name')->pluck('name', 'id'),
            'equipmentOptions' => Equipment::orderBy('name')->pluck('name', 'id'),
        ];
    }
}
