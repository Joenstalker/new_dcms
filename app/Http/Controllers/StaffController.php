<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $staff = User::role(['Dentist', 'Assistant'])->with(['roles', 'permissions'])->get();
        return Inertia::render('Staff/Index', [
            'staff' => $staff,
            'allPermissions' => \Spatie\Permission\Models\Permission::all(),
            'initialTab' => $request->query('tab', 'list')
        ]);
    }

    public function schedules()
    {
        $staff = User::role(['Dentist', 'Assistant'])->with('roles')->get();
        return Inertia::render('Staff/Schedules', [
            'staff' => $staff,
            'api_key' => config('services.google.calendar_api_key')
        ]);
    }

    public function create()
    {
        return Inertia::render('Staff/Create', [
            'roles' => Role::whereIn('name', ['Dentist', 'Assistant'])->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $user->assignRole($request->role);

        return redirect()->back()->with('success', 'Staff member created successfully.');
    }

    public function edit(User $staff)
    {
        return Inertia::render('Staff/Edit', [
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

    public function updatePermissions(Request $request, User $staff)
    {
        $request->validate([
            'permissions' => 'required|array',
        ]);

        $staff->syncPermissions($request->permissions);

        return redirect()->back()->with('success', 'Permissions updated successfully.');
    }
}
