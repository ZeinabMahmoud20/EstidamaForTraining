<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(): View
    {
        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get();

        return view('roles.index', [
            'roles' => $roles,
            'roleLabels' => config('menu.role_labels', []),
        ]);
    }

    public function create(): View
    {
        return view('roles.create', $this->formData());
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $role = DB::transaction(function () use ($data, $request) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($data['permissions'] ?? []);

            $this->audit->record('role.created', $role, null, $this->auditValues($role), $request->user());

            return $role;
        });

        return redirect()
            ->route('roles.index')
            ->with('success', 'تمت إضافة الدور بنجاح.');
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');

        return view('roles.edit', [
            'role' => $role,
            ...$this->formData($role),
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        if ($role->name === 'super_admin') {
            return redirect()
                ->route('roles.index')
                ->with('error', 'لا يمكن تعديل دور مدير النظام لأنه يملك جميع الصلاحيات دائمًا.');
        }

        $data = $request->validated();

        DB::transaction(function () use ($data, $request, $role) {
            $before = $this->auditValues($role);

            $role->update(['name' => $data['name']]);
            $role->syncPermissions($data['permissions'] ?? []);

            $this->audit->record('role.updated', $role, $before, $this->auditValues($role), $request->user());
        });

        return redirect()
            ->route('roles.index')
            ->with('success', 'تم تحديث الدور والصلاحيات بنجاح.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        if ($role->name === 'super_admin') {
            return redirect()
                ->route('roles.index')
                ->with('error', 'لا يمكن حذف دور مدير النظام.');
        }

        if ($role->users()->exists()) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'لا يمكن حذف الدور لارتباطه بمستخدمين.');
        }

        DB::transaction(function () use ($request, $role) {
            $this->audit->record('role.deleted', $role, $this->auditValues($role), null, $request->user());
            $role->delete();
        });

        return redirect()
            ->route('roles.index')
            ->with('success', 'تم حذف الدور بنجاح.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?Role $role = null): array
    {
        return [
            'permissionGroups' => config('permissions.groups', []),
            'selectedPermissions' => old(
                'permissions',
                $role?->permissions->pluck('name')->all() ?? []
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(Role $role): array
    {
        return [
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions' => $role->permissions()->pluck('name')->all(),
        ];
    }
}
