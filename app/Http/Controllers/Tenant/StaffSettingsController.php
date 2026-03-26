<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StaffSettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('Owner')) {
            return redirect()->route('tenant.dashboard');
        }

        // Check if user has ANY of the staff settings permissions
        $hasAnySettingsPermission = $user->hasAnyPermission([
            'manage own calendar',
            'manage own notifications',
            'manage own working hours',
        ]);

        if (!$hasAnySettingsPermission) {
            abort(403, 'You do not have permission to access staff settings.');
        }

        return Inertia::render('Tenant/StaffSettings/Index', [
            'calendarColor' => $user->calendar_color,
            'notificationPreferences' => $user->notification_preferences ?? $this->defaultNotificationPreferences(),
            'workingHours' => $user->working_hours ?? $this->defaultWorkingHours(),
            'permissions' => [
                'calendar' => $user->hasPermissionTo('manage own calendar'),
                'notifications' => $user->hasPermissionTo('manage own notifications'),
                'workingHours' => $user->hasPermissionTo('manage own working hours'),
            ],
        ]);
    }

    public function updateCalendarColor(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('Owner')) {
            abort(403);
        }

        if (!$user->hasPermissionTo('manage own calendar')) {
            abort(403);
        }

        $request->validate([
            'calendar_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $user->update(['calendar_color' => $request->calendar_color]);

        return redirect()->back()->with('success', 'Calendar color updated successfully.');
    }

    public function updateNotificationPreferences(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('Owner')) {
            abort(403);
        }

        if (!$user->hasPermissionTo('manage own notifications')) {
            abort(403);
        }

        $request->validate([
            'notification_preferences' => 'required|array',
            'notification_preferences.*.email' => 'required|boolean',
            'notification_preferences.*.in_app' => 'required|boolean',
        ]);

        $user->update(['notification_preferences' => $request->notification_preferences]);

        return redirect()->back()->with('success', 'Notification preferences updated successfully.');
    }

    public function updateWorkingHours(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('Owner')) {
            abort(403);
        }

        if (!$user->hasPermissionTo('manage own working hours')) {
            abort(403);
        }

        $request->validate([
            'working_hours' => 'required|array',
            'working_hours.*.enabled' => 'required|boolean',
            'working_hours.*.start' => 'nullable|string',
            'working_hours.*.end' => 'nullable|string',
        ]);

        $user->update(['working_hours' => $request->working_hours]);

        return redirect()->back()->with('success', 'Working hours updated successfully.');
    }

    private function defaultNotificationPreferences(): array
    {
        return [
            'new_appointment' => ['email' => true, 'in_app' => true],
            'appointment_cancelled' => ['email' => true, 'in_app' => true],
            'appointment_rescheduled' => ['email' => false, 'in_app' => true],
            'treatment_reminder' => ['email' => false, 'in_app' => true],
            'system_announcements' => ['email' => false, 'in_app' => true],
        ];
    }

    private function defaultWorkingHours(): array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $hours = [];
        foreach ($days as $day) {
            $hours[$day] = [
                'enabled' => !in_array($day, ['saturday', 'sunday']),
                'start' => '09:00',
                'end' => '17:00',
            ];
        }
        return $hours;
    }
}
