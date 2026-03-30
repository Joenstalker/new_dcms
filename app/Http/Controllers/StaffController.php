<?php

namespace App\Http\Controllers;

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

        return redirect()->route('staff.index')->with('success', 'Staff member updated successfully.');
    }

    public function destroy(User $staff)
    {
        $staff->delete();
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
        }

        return redirect()->back()->with('success', 'Permissions updated for ' . $staffMembers->count() . ' staff members.');
    }
}
