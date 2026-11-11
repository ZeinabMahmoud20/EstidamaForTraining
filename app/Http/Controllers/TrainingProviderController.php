<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrainingProviderRequest;
use App\Http\Requests\UpdateTrainingProviderRequest;
use App\Models\TrainingProvider;
use App\Services\TrainingProviderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingProviderController extends Controller
{
    public function __construct(private readonly TrainingProviderService $providers) {}

    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $providers = TrainingProvider::query()
            ->withCount(['trainers', 'courseSessions'])
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(fn ($q) => $q
                    ->where('name', 'like', $like)
                    ->orWhere('contact_person', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like));
            })
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('providers.index', compact('providers'));
    }

    public function create(): View
    {
        return view('providers.create');
    }

    public function store(StoreTrainingProviderRequest $request): RedirectResponse
    {
        $provider = $this->providers->create($request->validated());

        return redirect()
            ->route('providers.show', $provider)
            ->with('success', 'تمت إضافة جهة التدريب بنجاح.');
    }

    public function show(TrainingProvider $provider): View
    {
        $provider->load([
            'trainers' => fn ($query) => $query->orderBy('full_name'),
            'courseSessions' => fn ($query) => $query->orderByDesc('start_date'),
            'courseSessions.course',
        ])->loadCount(['traineeProviders', 'nominations', 'invoices']);

        return view('providers.show', compact('provider'));
    }

    public function edit(TrainingProvider $provider): View
    {
        return view('providers.edit', compact('provider'));
    }

    public function update(UpdateTrainingProviderRequest $request, TrainingProvider $provider): RedirectResponse
    {
        $this->providers->update($provider, $request->validated());

        return redirect()
            ->route('providers.show', $provider)
            ->with('success', 'تم تحديث جهة التدريب بنجاح.');
    }

    public function destroy(TrainingProvider $provider): RedirectResponse
    {
        if ($blockers = $this->providers->deletionBlockers($provider)) {
            return redirect()
                ->route('providers.show', $provider)
                ->with('error', 'لا يمكن حذف جهة التدريب لارتباطها بـ: '.implode('، ', $blockers).'.');
        }

        $this->providers->delete($provider);

        return redirect()
            ->route('providers.index')
            ->with('success', 'تم حذف جهة التدريب بنجاح.');
    }
}
