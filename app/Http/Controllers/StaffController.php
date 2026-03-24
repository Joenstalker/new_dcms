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
            'allPermissions' => \Spatie\Permission\Models\Permission::all(),
            'initialTab' => $request->query('tab', 'list')
        ]);
    }

    public function schedules()
    {
        $staff = User::role(['Dentist', 'Assistant'])->with('roles')->get();
        return Inertia::render('Tenant/Staff/Schedules', [
            'staff' => $staff,
            'api_key' => config('services.google.calendar_api_key')
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Staff/Create', [
            'roles' => Role::whereIn('name', ['Dentist', 'Assistant'])->get()
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
        ]);

        $user->assignRole($request->role);

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

    public function edit(User $staff)
    {
        return Inertia::render('Tenant/Staff/Edit', [
            'staff' => $staff->load('roles'),
            'roles' => Role::whereIn('name', ['Dentist', 'Assistant'])->get()
        ]);
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
            'permissions' => 'required|array',
        ]);

        $staffMembers = User::whereIn('id', $request->staff_ids)->get();
        
        foreach ($staffMembers as $staff) {
            $staff->syncPermissions($request->permissions);
        }

        return redirect()->back()->with('success', 'Permissions updated for ' . $staffMembers->count() . ' staff members.');
    }
}
