<?php

namespace App\Http\Controllers;

use App\Events\TenantStaffChanged;
use App\Events\UserAccessChanged;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\StaffInvitation;
use App\Services\TenantBrandingService;
use Database\Factories\StaffFactory;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class StaffController extends Controller
{
    private const ROLE_DEFAULT_KEYS = ['Dentist', 'Assistant'];

    public function index(Request $request)
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([
            'progress notes',
            'view progress notes',
            'create progress notes',
            'edit progress notes',
            'delete progress notes',
            'access support chat',
        ] as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        $staff = User::role(['Dentist', 'Assistant'])->with(['roles', 'permissions'])->get();
        $defaultPermissionMap = $this->resolveDefaultPermissionMap();

        return Inertia::render('Tenant/Staff/Index', [
            'staff' => $staff,
            'roles' => Role::whereIn('name', ['Dentist', 'Assistant'])->get(),
            'api_key' => config('services.google.calendar_api_key'),
            'allPermissions' => Permission::where('name', '!=', 'manage clinic')->get(),
            'defaultPermissionMap' => $defaultPermissionMap,
            'initialTab' => $request->query('tab', 'list')
        ]);
    }

    public function updateDefaultPermissions(Request $request)
    {
        if (!$request->user()?->can('edit staff')) {
            abort(403);
        }

        $validated = $request->validate([
            'default_permission_map' => 'required|array',
            'default_permission_map.Dentist' => 'required|array',
            'default_permission_map.Dentist.*' => 'string|exists:permissions,name',
            'default_permission_map.Assistant' => 'required|array',
            'default_permission_map.Assistant.*' => 'string|exists:permissions,name',
        ]);

        $normalized = [
            'Dentist' => $this->sanitizePermissionList($validated['default_permission_map']['Dentist']),
            'Assistant' => $this->sanitizePermissionList($validated['default_permission_map']['Assistant']),
        ];

        TenantBrandingService::set('staff_default_permissions', $normalized);

        return redirect()->back()->with('success', 'Default role permissions updated successfully.');
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => 'required|string|exists:roles,name',
        ]);

        $randomPassword = Str::random(12);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($randomPassword),
            'requires_password_change' => true,
        ]);

        $user->assignRole($request->role);

        // Auto-assign baseline permissions based on staff role.
        $user->syncPermissions($this->defaultPermissionsForRole($request->role));

        // Send Invitation Email
        try {
            $tenant = tenant();
            $tenantSubdomain = $tenant ? (string) $tenant->getTenantKey() : '';
            $tenantUrl = $tenantSubdomain !== ''
                ? Tenant::publicWebsiteUrlForSubdomain($tenantSubdomain)
                : request()->getSchemeAndHttpHost();
            
            Mail::to($user->email)->send(new StaffInvitation(
                name: $user->name,
                email: $user->email,
                password: $randomPassword,
                role: $request->role,
                tenantName: $tenant->clinic_name ?? $tenant->id,
                tenantUrl: $tenantUrl
            ));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Staff invitation email failed: ' . $e->getMessage());
        }

        $this->broadcastStaffChange($user->fresh()->load(['roles', 'permissions']), 'created');

        return redirect()->back()->with('success', 'Staff member created successfully and invitation sent.');
    }


    public function generateSamples(Request $request)
    {
        $validated = $request->validate([
            'count' => 'required|integer|min:1|max:50',
        ]);

        $count = (int) $validated['count'];

        \Illuminate\Support\Facades\DB::transaction(function () use ($count) {
            (new \Database\Seeders\TenantStaffSeeder())->run($count);
        });

        return redirect()->route('staff.index')->with('success', $count . ' staff members generated successfully.');
    }

    public function update(Request $request, User $staff)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $staff->id,
            'role' => 'required|string|exists:roles,name',
        ]);

        $staff->update($request->only('name', 'email'));
        
        $staff->syncRoles([$request->role]);

        // Ensure role updates retain a baseline access set if user currently has no direct permissions.
        if ($staff->permissions()->count() === 0) {
            $staff->syncPermissions($this->defaultPermissionsForRole($request->role));
        }

        $this->broadcastStaffChange($staff->fresh()->load(['roles', 'permissions']), 'updated');
        broadcast(new UserAccessChanged(
            (int) $staff->id,
            'role_updated',
            'Your role or profile access has been updated. Reloading your permissions now.'
        ));

        return redirect()->route('staff.index')->with('success', 'Staff member updated successfully.');
    }

    public function destroy(Request $request, $staff)
    {
        $staff = User::query()->find($staff);

        if (!$staff) {
            throw ValidationException::withMessages([
                'staff' => 'Staff member no longer exists or is inaccessible.',
            ]);
        }

        $actor = $request->user();

        if ($actor && (int) $actor->id === (int) $staff->id) {
            throw ValidationException::withMessages([
                'staff' => 'You cannot delete your own account.',
            ]);
        }

        if (!$staff->hasAnyRole(['Dentist', 'Assistant'])) {
            throw ValidationException::withMessages([
                'staff' => 'Only Dentist and Assistant accounts can be removed from Staff Management.',
            ]);
        }

        broadcast(new UserAccessChanged(
            (int) $staff->id,
            'account_deleted',
            'Your clinic account has been removed. You will be logged out.',
            true
        ));

        $deletedPayload = [
            'id' => $staff->id,
        ];

        $staff->delete();
        $this->broadcastRawStaffChange('deleted', $deletedPayload);

        return redirect()->route('staff.index')->with('success', 'Staff member removed successfully.');
    }

    public function bulkUpdatePermissions(Request $request)
    {
        $request->validate([
            'staff_ids' => 'required|array',
            'staff_ids.*' => 'exists:users,id',
            'permissions' => 'present|array',
        ]);

        $staffMembers = User::whereIn('id', $request->staff_ids)->get();
        
        foreach ($staffMembers as $staff) {
            \Illuminate\Support\Facades\Log::info("Syncing permissions for staff ID {$staff->id}", [
                'permissions' => $request->permissions,
                'user_guard' => $staff->guard_name ?? 'default (probably web)',
                'existing_permissions' => $staff->permissions->pluck('name')->toArray()
            ]);
            $staff->syncPermissions($request->permissions);
            $this->broadcastStaffChange($staff->fresh()->load(['roles', 'permissions']), 'permissions_updated');
            broadcast(new UserAccessChanged(
                (int) $staff->id,
                'permissions_updated',
                'Your permissions were updated. Reloading your access.'
            ));
        }

        return redirect()->back()->with('success', 'Permissions updated for ' . $staffMembers->count() . ' staff members.');
    }

    private function broadcastStaffChange(User $staff, string $action): void
    {
        if (!tenant()) {
            return;
        }

        $payload = [
            'id' => $staff->id,
            'name' => $staff->name,
            'email' => $staff->email,
            'profile_picture_url' => $staff->profile_picture_url,
            'roles' => $staff->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            })->values()->all(),
            'permissions' => $staff->permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ];
            })->values()->all(),
        ];

        $this->broadcastRawStaffChange($action, $payload);
    }

    private function broadcastRawStaffChange(string $action, array $staffPayload): void
    {
        if (!tenant()) {
            return;
        }

        broadcast(new TenantStaffChanged((string) tenant()->getTenantKey(), $action, $staffPayload));
    }

    private function defaultPermissionsForRole(string $role): array
    {
        $map = $this->resolveDefaultPermissionMap();

        return $map[$role] ?? ['view dashboard'];
    }

    private function resolveDefaultPermissionMap(): array
    {
        $baseMap = $this->baseDefaultPermissionMap();
        $storedMap = TenantBrandingService::get('staff_default_permissions', []);

        if (!is_array($storedMap)) {
            return $baseMap;
        }

        $resolved = $baseMap;

        foreach (self::ROLE_DEFAULT_KEYS as $role) {
            if (isset($storedMap[$role]) && is_array($storedMap[$role])) {
                $resolved[$role] = $this->sanitizePermissionList($storedMap[$role]);
            }
        }

        return $resolved;
    }

    private function sanitizePermissionList(array $permissions): array
    {
        $allowedPermissions = array_flip(Permission::pluck('name')->all());
        $sanitized = [];

        foreach ($permissions as $permission) {
            if (isset($allowedPermissions[$permission])) {
                $sanitized[] = $permission;
            }
        }

        return array_values(array_unique($sanitized));
    }

    private function baseDefaultPermissionMap(): array
    {
        return [
            'Dentist' => [
                'view dashboard',
                'view appointments',
                'create appointments',
                'edit appointments',
                'view patients',
                'create patients',
                'edit patients',
                'view treatments',
                'create treatments',
                'edit treatments',
                'progress notes',
                'view progress notes',
                'create progress notes',
                'edit progress notes',
                'delete progress notes',
                'view medical records',
                'view services',
                'create services',
                'edit services',
                'manage own calendar',
                'manage own notifications',
                'manage own working hours',
            ],
            'Assistant' => [
                'view dashboard',
                'view appointments',
                'create appointments',
                'edit appointments',
                'view patients',
                'create patients',
                'edit patients',
                'view billing',
                'create billing',
                'edit billing',
                'view medical records',
                'view services',
                'manage own calendar',
                'manage own notifications',
                'manage own working hours',
            ],
        ];
    }
}
