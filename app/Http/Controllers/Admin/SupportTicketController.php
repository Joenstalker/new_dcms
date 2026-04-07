<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportMessage;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Events\SupportTicketUpdated;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $category = $request->get('category', 'all');

        $query = SupportTicket::with(['latestMessage', 'tenant'])->latest('updated_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }
        if ($category !== 'all') {
            $query->where('category', $category);
        }

        $tickets = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => SupportTicket::count(),
            'open' => SupportTicket::where('status', 'open')->count(),
            'in_progress' => SupportTicket::where('status', 'in_progress')->count(),
            'resolved' => SupportTicket::where('status', 'resolved')->count(),
        ];

        return Inertia::render('Admin/Support/Index', [
            'tickets' => $tickets,
            'currentStatus' => $status,
            'currentCategory' => $category,
            'stats' => $stats,
        ]);
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['messages.sender', 'messages.attachments', 'tenant']);

        return response()->json([
            'success' => true,
            'ticket' => $ticket
        ]);
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,pending,resolved,closed',
        ]);

        $ticket->update($validated);

        if ($validated['status'] === 'resolved') {
            $ticket->update(['resolved_at' => now()]);
        }

        AuditLog::record(
            'support_ticket_status_updated',
            "Updated status of ticket #{$ticket->id} to {$validated['status']}.",
            'SupportTicket',
            $ticket->id,
        ['new_status' => $validated['status']]
        );

        broadcast(new SupportTicketUpdated($ticket, 'status_updated'));

        return back()->with('success', 'Ticket status updated.');
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'content' => 'required|string|min:2|max:5000',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,zip',
        ]);

        $message = $ticket->messages()->create([
            'sender_id' => Auth::id(),
            'sender_type' => 'admin',
            'content' => $validated['content'],
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('support/attachments', 'support');
                $message->attachments()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        $ticket->update(['status' => 'in_progress']);
        $ticket->touch();

        AuditLog::record(
            'support_ticket_replied',
            "Admin replied to ticket #{$ticket->id}.",
            'SupportTicket',
            $ticket->id
        );

        broadcast(new SupportTicketUpdated($ticket, 'reply_added'));

        return back()->with('success', 'Reply sent to tenant.');
    }

    public function destroy(SupportTicket $ticket)
    {
        $ticketId = $ticket->id;
        $ticket->delete();

        AuditLog::record(
            'support_ticket_deleted',
            "Deleted support ticket #{$ticketId}.",
            'SupportTicket',
            $ticketId
        );

        return back()->with('success', 'Ticket deleted.');
    }
}
