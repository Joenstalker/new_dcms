<?php

namespace App\Http\Controllers\Tenant\Support;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportMessage;
use App\Models\SupportAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Stancl\Tenancy\Facades\Tenancy;

class SupportController extends Controller
{
    public function index()
    {
        $tenantId = tenancy()->tenant->id;
        $tickets = SupportTicket::where('tenant_id', $tenantId)
            ->with(['latestMessage'])
            ->latest('updated_at')
            ->get();

        return response()->json([
            'tickets' => $tickets
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|in:billing,technical,feature_request,account,other',
            'priority' => 'required|in:low,medium,high,urgent',
            'content' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,zip',
        ]);

        $tenantId = tenancy()->tenant->id;
        $userId = Auth::id();

        $ticket = SupportTicket::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'subject' => $validated['subject'],
            'description' => $validated['content'], // Initial description
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'status' => 'open',
        ]);

        $message = $ticket->messages()->create([
            'sender_id' => $userId,
            'sender_type' => 'tenant',
            'content' => $validated['content'],
        ]);

        $this->handleAttachments($request, $message);

        return response()->json([
            'success' => true,
            'ticket' => $ticket->load('messages.attachments')
        ]);
    }

    public function show(SupportTicket $ticket)
    {
        // Ensure tenant owns the ticket
        if ($ticket->tenant_id !== tenancy()->tenant->id) {
            abort(403);
        }

        return response()->json([
            'ticket' => $ticket->load(['messages.attachments', 'messages.sender'])
        ]);
    }

    public function sendMessage(Request $request, SupportTicket $ticket)
    {
        if ($ticket->tenant_id !== tenancy()->tenant->id) {
            abort(403);
        }

        $validated = $request->validate([
            'content' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,zip',
        ]);

        $message = $ticket->messages()->create([
            'sender_id' => Auth::id(),
            'sender_type' => 'tenant',
            'content' => $validated['content'],
        ]);

        $this->handleAttachments($request, $message);

        // Update ticket's last activity
        $ticket->touch();

        return response()->json([
            'success' => true,
            'message' => $message->load('attachments')
        ]);
    }

    protected function handleAttachments(Request $request, SupportMessage $message)
    {
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('support/attachments', 'public');

                $message->attachments()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }
    }
}
