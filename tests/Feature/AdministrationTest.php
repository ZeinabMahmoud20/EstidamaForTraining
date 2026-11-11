<?php

namespace Tests\Feature;

use App\Models\Accreditation;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\BranchAccreditation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_administration(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
        $this->get(route('roles.index'))->assertRedirect(route('login'));
        $this->get(route('branches.index'))->assertRedirect(route('login'));
        $this->get(route('accreditations.index'))->assertRedirect(route('login'));
        $this->get(route('audit.index'))->assertRedirect(route('login'));
    }

    public function test_administration_permissions_are_enforced(): void
    {
        $user = User::factory()->create();

        foreach (['users.index', 'roles.index', 'branches.index', 'accreditations.index', 'audit.index'] as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }

        $this->actingAs($this->actor(['USERS_MANAGE']))->get(route('users.index'))->assertOk();
        $this->actingAs($this->actor(['USERS_MANAGE']))->get(route('roles.index'))->assertForbidden();

        $this->actingAs($this->actor(['ROLES_MANAGE']))->get(route('roles.index'))->assertOk();
        $this->actingAs($this->actor(['ROLES_MANAGE']))->get(route('users.index'))->assertForbidden();

        $this->actingAs($this->actor(['BRANCHES_MANAGE']))->get(route('branches.index'))->assertOk();
        $this->actingAs($this->actor(['BRANCHES_MANAGE']))->get(route('accreditations.index'))->assertOk();
        $this->actingAs($this->actor(['BRANCHES_MANAGE']))->get(route('audit.index'))->assertForbidden();

        $this->actingAs($this->actor(['AUDIT_VIEW']))->get(route('audit.index'))->assertOk();
    }

    public function test_user_is_created_with_hashed_password_and_roles(): void
    {
        $branch = Branch::factory()->create();
        $actor = $this->actor(['USERS_MANAGE', 'ROLES_MANAGE']);

        $this->actingAs($actor)
            ->post(route('users.store'), [
                'username' => ' New.User ',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'full_name' => 'مستخدم جديد',
                'email' => 'new.user@example.com',
                'branch_id' => $branch->id,
                'is_active' => 1,
                'roles' => ['trainee'],
            ])
            ->assertRedirect();

        $user = User::where('username', 'new.user')->firstOrFail();
        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertTrue($user->hasRole('trainee'));
        $this->assertSame($branch->id, $user->branch_id);

        $log = AuditLog::where('action', 'user.created')->firstOrFail();
        $this->assertSame('App\Models\User', $log->entity_type);
        $this->assertSame($user->id, $log->entity_id);
        $this->assertArrayNotHasKey('password', $log->new_value);
    }

    public function test_user_roles_are_ignored_without_roles_permission(): void
    {
        $this->actingAs($this->actor(['USERS_MANAGE']))
            ->post(route('users.store'), [
                'username' => 'limited.user',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'is_active' => 1,
                'roles' => ['super_admin'],
            ])
            ->assertRedirect();

        $user = User::where('username', 'limited.user')->firstOrFail();
        $this->assertFalse($user->hasRole('super_admin'));
        $this->assertCount(0, $user->roles);
    }

    public function test_user_can_be_updated_without_changing_the_password(): void
    {
        $user = User::factory()->create([
            'username' => 'existing.user',
            'password' => 'old-password',
        ]);
        $user->assignRole('trainee');

        $this->actingAs($this->actor(['USERS_MANAGE', 'ROLES_MANAGE']))
            ->put(route('users.update', $user), [
                'username' => 'existing.user',
                'full_name' => 'اسم محدّث',
                'is_active' => 0,
                'roles' => ['trainer'],
            ])
            ->assertRedirect(route('users.show', $user));

        $user->refresh();
        $this->assertSame('اسم محدّث', $user->full_name);
        $this->assertFalse($user->is_active);
        $this->assertTrue(Hash::check('old-password', $user->password));
        $this->assertTrue($user->hasRole('trainer'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.updated', 'entity_id' => $user->id]);
    }

    public function test_user_cannot_deactivate_or_delete_self(): void
    {
        $actor = $this->actor(['USERS_MANAGE', 'ROLES_MANAGE']);

        $this->actingAs($actor)
            ->put(route('users.update', $actor), [
                'username' => $actor->username,
                'is_active' => 0,
                'roles' => [],
            ])
            ->assertSessionHas('error');

        $this->assertTrue($actor->refresh()->is_active);

        $this->actingAs($actor)
            ->delete(route('users.destroy', $actor))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $actor->id]);
    }

    public function test_other_users_can_be_deleted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->actor(['USERS_MANAGE']))
            ->delete(route('users.destroy', $user))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.deleted', 'entity_id' => $user->id]);
    }

    public function test_role_can_be_created_updated_and_deleted(): void
    {
        $actor = $this->actor(['ROLES_MANAGE']);

        $this->actingAs($actor)
            ->post(route('roles.store'), [
                'name' => 'supervisor',
                'permissions' => ['USERS_MANAGE', 'AUDIT_VIEW'],
            ])
            ->assertRedirect(route('roles.index'));

        $role = Role::where('name', 'supervisor')->firstOrFail();
        $this->assertTrue($role->hasPermissionTo('USERS_MANAGE'));

        $this->actingAs($actor)
            ->put(route('roles.update', $role), [
                'name' => 'quality_supervisor',
                'permissions' => ['EVALUATION_REPORTS_VIEW'],
            ])
            ->assertRedirect(route('roles.index'));

        $role->refresh();
        $this->assertSame('quality_supervisor', $role->name);
        $this->assertFalse($role->hasPermissionTo('USERS_MANAGE'));
        $this->assertTrue($role->hasPermissionTo('EVALUATION_REPORTS_VIEW'));

        $this->actingAs($actor)
            ->delete(route('roles.destroy', $role))
            ->assertRedirect(route('roles.index'));

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'role.created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'role.updated']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'role.deleted']);
    }

    public function test_system_roles_and_roles_with_users_cannot_be_deleted(): void
    {
        $superAdmin = Role::findByName('super_admin');
        $traineeRole = Role::findByName('trainee');
        User::factory()->create()->assignRole('trainee');
        $actor = $this->actor(['ROLES_MANAGE']);

        $this->actingAs($actor)
            ->delete(route('roles.destroy', $superAdmin))
            ->assertSessionHas('error');

        $this->actingAs($actor)
            ->delete(route('roles.destroy', $traineeRole))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $superAdmin->id]);
        $this->assertDatabaseHas('roles', ['id' => $traineeRole->id]);
    }

    public function test_branch_is_created_and_accreditations_are_linked_updated_and_removed(): void
    {
        $actor = $this->actor(['BRANCHES_MANAGE']);
        $accreditation = Accreditation::factory()->create();

        $this->actingAs($actor)
            ->post(route('branches.store'), [
                'code' => 'br01',
                'name' => 'فرع الرياض',
                'city' => 'الرياض',
                'phone' => '0500000000',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $branch = Branch::where('code', 'BR01')->firstOrFail();
        $this->assertSame('فرع الرياض', $branch->name);

        $this->actingAs($actor)
            ->post(route('branches.accreditations.store', $branch), [
                'accreditation_id' => $accreditation->id,
                'issue_date' => '2026-01-01',
                'expiry_date' => '2026-12-31',
                'is_active' => 1,
            ])
            ->assertRedirect(route('branches.show', $branch));

        $link = BranchAccreditation::firstOrFail();
        $this->assertTrue($link->is_active);

        $this->actingAs($actor)
            ->post(route('branches.accreditations.store', $branch), [
                'accreditation_id' => $accreditation->id,
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('accreditation_id');

        $this->actingAs($actor)
            ->patch(route('branches.accreditations.update', [$branch, $link]), [
                'accreditation_id' => $accreditation->id,
                'issue_date' => '2026-02-01',
                'is_active' => 0,
            ])
            ->assertRedirect(route('branches.show', $branch));

        $this->assertFalse($link->refresh()->is_active);

        $this->actingAs($actor)
            ->delete(route('branches.accreditations.destroy', [$branch, $link]))
            ->assertRedirect(route('branches.show', $branch));

        $this->assertDatabaseCount('branch_accreditations', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'branch.created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'branch_accreditation.created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'branch_accreditation.updated']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'branch_accreditation.deleted']);
    }

    public function test_branch_with_relations_cannot_be_deleted(): void
    {
        $branch = Branch::factory()->create();
        User::factory()->create(['branch_id' => $branch->id]);
        $actor = $this->actor(['BRANCHES_MANAGE']);

        $this->actingAs($actor)
            ->delete(route('branches.destroy', $branch))
            ->assertRedirect(route('branches.show', $branch))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('branches', ['id' => $branch->id]);

        $branch->users()->update(['branch_id' => null]);

        $this->actingAs($actor)
            ->delete(route('branches.destroy', $branch))
            ->assertRedirect(route('branches.index'));

        $this->assertDatabaseMissing('branches', ['id' => $branch->id]);
    }

    public function test_accreditation_can_be_created_and_updated_but_not_deleted_while_linked(): void
    {
        $actor = $this->actor(['BRANCHES_MANAGE']);
        $branch = Branch::factory()->create();

        $this->actingAs($actor)
            ->post(route('accreditations.store'), [
                'name' => 'اعتماد مهني',
                'issuing_body' => 'الهيئة المهنية',
            ])
            ->assertRedirect(route('accreditations.index'));

        $accreditation = Accreditation::where('name', 'اعتماد مهني')->firstOrFail();

        $this->actingAs($actor)
            ->put(route('accreditations.update', $accreditation), [
                'name' => 'اعتماد مهني معتمد',
                'issuing_body' => 'الهيئة الوطنية',
                'description' => 'وصف',
            ])
            ->assertRedirect(route('accreditations.index'));

        BranchAccreditation::create([
            'branch_id' => $branch->id,
            'accreditation_id' => $accreditation->id,
            'is_active' => true,
        ]);

        $this->actingAs($actor)
            ->delete(route('accreditations.destroy', $accreditation))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('accreditations', ['id' => $accreditation->id]);
    }

    public function test_audit_log_page_lists_and_filters_records(): void
    {
        $actor = User::factory()->create(['full_name' => 'مدير النظام']);
        AuditLog::create([
            'user_id' => $actor->id,
            'action' => 'user.created',
            'entity_type' => 'App\Models\User',
            'entity_id' => 99,
            'new_value' => ['username' => 'from-audit'],
        ]);

        $this->actingAs($this->actor(['AUDIT_VIEW']))
            ->get(route('audit.index'))
            ->assertOk()
            ->assertSee('إضافة مستخدم')
            ->assertSee('from-audit');

        $this->actingAs($this->actor(['AUDIT_VIEW']))
            ->get(route('audit.index', ['entity_type' => 'App\Models\Branch']))
            ->assertOk()
            ->assertDontSee('إضافة مستخدم');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function actor(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }
}
