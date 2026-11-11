<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Branch;
use App\Models\Trainee;
use App\Models\Trainer;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q'));

        $users = User::query()
            ->with(['branch', 'trainer', 'trainee', 'roles'])
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(fn ($q) => $q
                    ->where('username', 'like', $like)
                    ->orWhere('full_name', 'like', $like)
                    ->orWhere('email', 'like', $like));
            })
            ->when($request->filled('role'), fn ($q) => $q
                ->whereHas('roles', fn ($r) => $r->where('name', $request->input('role'))))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->input('branch_id')))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('full_name')
            ->orderBy('username')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->pluck('name', 'name'),
            'branches' => Branch::orderBy('name')->pluck('name', 'id'),
            'roleLabels' => config('menu.role_labels', []),
        ]);
    }

    public function create(): View
    {
        return view('users.create', $this->formData());
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'username' => $data['username'],
                'password' => $data['password'],
                'full_name' => $data['full_name'] ?? null,
                'email' => $data['email'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'trainer_id' => $data['trainer_id'] ?? null,
                'trainee_id' => $data['trainee_id'] ?? null,
                'is_active' => $data['is_active'],
            ]);

            if ($request->user()->can('ROLES_MANAGE')) {
                $user->syncRoles($data['roles'] ?? []);
            }

            $this->audit->record('user.created', $user, null, $this->auditValues($user), $request->user());

            return $user;
        });

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'تمت إضافة المستخدم بنجاح.');
    }

    public function show(User $user): View
    {
        $user->load(['roles', 'branch', 'trainer', 'trainee']);

        return view('users.show', [
            'user' => $user,
            'roleLabels' => config('menu.role_labels', []),
        ]);
    }

    public function edit(User $user): View
    {
        $user->load('roles');

        return view('users.edit', [
            'user' => $user,
            ...$this->formData(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if ($user->is($request->user()) && ! $data['is_active']) {
            return back()
                ->withInput()
                ->with('error', 'لا يمكنك تعطيل حسابك الحالي.');
        }

        DB::transaction(function () use ($data, $request, $user) {
            $before = $this->auditValues($user);

            $attributes = Arr::only($data, [
                'username', 'full_name', 'email', 'branch_id', 'trainer_id', 'trainee_id', 'is_active',
            ]);

            if (filled($data['password'] ?? null)) {
                $attributes['password'] = $data['password'];
            }

            $user->update($attributes);

            if ($request->user()->can('ROLES_MANAGE')) {
                $user->syncRoles($data['roles'] ?? []);
            }

            $this->audit->record('user.updated', $user, $before, $this->auditValues($user), $request->user());
        });

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'تم تحديث المستخدم بنجاح.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return redirect()
                ->route('users.show', $user)
                ->with('error', 'لا يمكنك حذف حسابك الحالي.');
        }

        DB::transaction(function () use ($request, $user) {
            $this->audit->record('user.deleted', $user, $this->auditValues($user), null, $request->user());
            $user->delete();
        });

        return redirect()
            ->route('users.index')
            ->with('success', 'تم حذف المستخدم بنجاح.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'roles' => Role::orderBy('name')->get(['id', 'name']),
            'branches' => Branch::orderBy('name')->pluck('name', 'id'),
            'trainers' => Trainer::orderBy('full_name')->pluck('full_name', 'id'),
            'trainees' => Trainee::orderBy('full_name')->pluck('full_name', 'id'),
            'roleLabels' => config('menu.role_labels', []),
        ];
    }

    /**
     * Safe snapshot for the audit trail (never includes the password).
     *
     * @return array<string, mixed>
     */
    private function auditValues(User $user): array
    {
        return [
            'username' => $user->username,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'branch_id' => $user->branch_id,
            'trainer_id' => $user->trainer_id,
            'trainee_id' => $user->trainee_id,
            'is_active' => $user->is_active,
            'roles' => $user->roles()->pluck('name')->all(),
        ];
    }
}
