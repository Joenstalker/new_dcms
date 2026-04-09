<?php

namespace App\Http\Controllers;

use App\Events\TenantStaffChanged;
use App\Events\UserAccessChanged;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\StaffInvitation;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $staff = User::role(['Dentist', 'Assistant'])->with(['roles', 'permissions'])->get();
        return Inertia::render('Tenant/Staff/Index', [
            'staff' => $staff,
            'roles' => Role::whereIn('name', ['Dentist', 'Assistant'])->get(),
            'api_key' => config('services.google.calendar_api_key'),
            'allPermissions' => \Spatie\Permission\Models\Permission::all(),
            'initialTab' => $request->query('tab', 'list')
        ]);
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

        // Auto-assign base permissions for Guided Onboarding
        $user->syncPermissions(['view dashboard']);

        // Send Invitation Email
        try {
            $tenant = tenant();
            $tenantUrl = request()->getScheme() . '://' . request()->getHost();
            
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


    public function update(Request $request, User $staff)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $staff->id,
            'role' => 'required|string|exists:roles,name',
        ]);

        $staff->update($request->only('name', 'email'));
        
        $staff->syncRoles([$request->role]);

        $this->broadcastStaffChange($staff->fresh()->load(['roles', 'permissions']), 'updated');
        broadcast(new UserAccessChanged(
            (int) $staff->id,
            'role_updated',
            'Your role or profile access has been updated. Reloading your permissions now.'
        ));

        return redirect()->route('staff.index')->with('success', 'Staff member updated successfully.');
    }

    public function destroy(User $staff)
    {
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
}
