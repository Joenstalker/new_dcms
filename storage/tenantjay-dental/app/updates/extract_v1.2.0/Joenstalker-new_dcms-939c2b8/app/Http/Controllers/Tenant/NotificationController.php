<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantNotification;
use App\Models\NotificationSetting;
use App\Services\TenantNotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct()
    {
        $this->notificationService = app(TenantNotificationService::class);
    }

    /**
     * Display notifications list.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();

        $notifications = TenantNotification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $unreadCount = $this->notificationService->getUnreadCount($user);

        return Inertia::render('Tenant/Notifications/Index', [
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'filters' => [
                'read' => $request->query('read', 'all'),
            ],
        ]);
    }

    /**
     * Get unread count (API for bell).
     */
    public function getUnreadCount()
    {
        $user = auth()->user();
        return response()->json([
            'count' => $this->notificationService->getUnreadCount($user),
        ]);
    }

    /**
     * Get recent notifications for dropdown.
     */
    public function getRecent()
    {
        $user = auth()->user();

        $notifications = TenantNotification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $unreadCount = $this->notificationService->getUnreadCount($user);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(string $id)
    {
        $notification = TenantNotification::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $this->notificationService->markAsRead($notification);

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $user = auth()->user();
        $this->notificationService->markAllAsRead($user);

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Delete a notification.
     */
    public function destroy(string $id)
    {
        $notification = TenantNotification::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $notification->delete();

        return back()->with('success', 'Notification deleted.');
    }

    /**
     * Display notification settings.
     */
    public function settings(): Response
    {
        $user = auth()->user();

        // Get all available notification types
        $allTypes = NotificationSetting::getTenantTypes();

        // Get user's current settings
        $userSettings = \DB::table('notification_settings')
            ->where('user_id', $user->id)
            ->get();

        // Merge them
        $settings = collect($allTypes)->map(function ($type) use ($userSettings) {
            $userSetting = $userSettings->firstWhere('type', $type['type']);

            return [
            'type' => $type['type'],
            'label' => $type['label'],
            'description' => $type['description'],
            'enabled' => $userSetting ? $userSetting->enabled : ($type['default_enabled'] ?? true),
            ];
        });

        return Inertia::render('Tenant/Notifications/Settings', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update notification settings.
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.type' => 'required|string',
            'settings.*.enabled' => 'required|boolean',
        ]);

        $user = auth()->user();

        foreach ($validated['settings'] as $setting) {
            \DB::table('notification_settings')->updateOrInsert(
            [
                'user_id' => $user->id,
                'type' => $setting['type'],
            ],
            [
                'enabled' => $setting['enabled'],
                'created_at' => now(),
                'updated_at' => now(),
            ]
            );
        }

        return back()->with('success', 'Notification settings updated successfully.');
    }

    /**
     * Send a message to the owner (staff feature).
     */
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $sender = auth()->user();

        // Send to all owners
        $this->notificationService->notifyOwners(
            'staff_message',
            $validated['title'],
            $validated['message'],
        [],
            $sender
        );

        return back()->with('success', 'Message sent to owner.');
    }
}
