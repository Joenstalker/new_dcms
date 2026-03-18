<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');

        $query = ContactMessage::query()->latest();

        if ($filter === 'unread') {
            $query->where('status', 'unread');
        } elseif ($filter === 'read') {
            $query->where('status', 'read');
        } elseif ($filter === 'replied') {
            $query->where('status', 'replied');
        } elseif ($filter === 'archived') {
            $query->where('status', 'archived');
        }

        $messages = $query->paginate(15)->withQueryString();
        $unreadCount = ContactMessage::unread()->count();

        $stats = [
            'total' => ContactMessage::count(),
            'unread' => $unreadCount,
            'replied' => ContactMessage::where('status', 'replied')->count(),
            'archived' => ContactMessage::where('status', 'archived')->count(),
        ];

        $counts = [
            'all' => $stats['total'],
            'unread' => $stats['unread'],
            'read' => ContactMessage::where('status', 'read')->count(),
            'replied' => $stats['replied'],
            'archived' => $stats['archived'],
        ];

        return Inertia::render('Admin/Support/Index', [
            'messages' => $messages,
            'unreadCount' => $unreadCount,
            'currentFilter' => $filter,
            'stats' => $stats,
            'counts' => $counts,
        ]);
    }

    public function show(ContactMessage $message)
    {
        // Auto-mark as read
        if ($message->status === 'unread') {
            $message->update(['status' => 'read']);
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    public function updateStatus(Request $request, ContactMessage $message)
    {
        $validated = $request->validate([
            'status' => 'required|in:unread,read,replied,archived',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $message->update($validated);

        AuditLog::record(
            'support_message_updated',
            "Updated status of message from {$message->email} to {$validated['status']}.",
            'ContactMessage',
            $message->id,
            ['new_status' => $validated['status']]
        );

        return back()->with('success', 'Message status updated.');
    }

    public function reply(Request $request, ContactMessage $message)
    {
        $validated = $request->validate([
            'reply' => 'required|string|min:5|max:5000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:5120|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif', // Max 5MB per file
        ]);

        try {
            $attachments = $request->file('attachments') ?? [];

            \Illuminate\Support\Facades\Mail::to($message->email)
                ->send(new \App\Mail\ReplyToContactMessage($message, $validated['reply'], $attachments));

            $message->update(['status' => 'replied']);

            AuditLog::record(
                'support_message_replied',
                "Sent reply to support message from {$message->email}.",
                'ContactMessage',
                $message->id
            );

            return back()->with('success', 'Reply sent to ' . $message->email);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send contact reply email: ' . $e->getMessage());
            return back()->with('error', 'Failed to send email. Check SMTP configuration.');
        }
    }

    public function destroy(ContactMessage $message)
    {
        $messageId = $message->id;
        $fromEmail = $message->email;
        $message->delete();

        AuditLog::record(
            'support_message_deleted',
            "Deleted support message from {$fromEmail}.",
            'ContactMessage',
            $messageId
        );

        return back()->with('success', 'Message deleted.');
    }
}
