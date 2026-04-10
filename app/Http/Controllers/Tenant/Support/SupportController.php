<?php

namespace App\Http\Controllers\Tenant\Support;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportMessage;
use App\Models\SupportAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Facades\Tenancy;
use App\Events\SupportTicketUpdated;

class SupportController extends Controller
{
    private function authorizeSupportChat(): void
    {
        $user = auth()->user();

        if ($user && ($user->hasRole('Owner') || $user->can('access support chat'))) {
            return;
        }

        abort(403, 'You do not have permission to access support chat.');
    }

    public function index()
    {
        $this->authorizeSupportChat();

        $tenantId = tenancy()->tenant->id;
        $tickets = SupportTicket::where('tenant_id', $tenantId)
            ->with(['latestMessage'])
            ->latest('updated_at')
            ->get();

        return response()->json([
            'tickets' => $tickets->map(function (SupportTicket $ticket) {
                $data = $ticket->toArray();

                if ($ticket->relationLoaded('latestMessage') && $ticket->latestMessage) {
                    $latest = $ticket->latestMessage;
                    $data['latest_message_sender_name'] = $this->resolveSenderName($latest);
                    $data['latest_message_sender_avatar_url'] = $this->resolveSenderAvatarUrl($latest);
                }

                return $data;
            })->values(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeSupportChat();

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

        broadcast(new SupportTicketUpdated($ticket, 'created'));

        return response()->json([
            'success' => true,
            'ticket' => $this->transformTicket($ticket->load('messages.attachments')),
        ]);
    }

    public function show(SupportTicket $ticket)
    {
        $this->authorizeSupportChat();

        // Ensure tenant owns the ticket
        if ($ticket->tenant_id !== tenancy()->tenant->id) {
            abort(403);
        }

        return response()->json([
            'ticket' => $this->transformTicket($ticket->load(['messages.attachments', 'messages.sender'])),
        ]);
    }

    public function sendMessage(Request $request, SupportTicket $ticket)
    {
        $this->authorizeSupportChat();

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

        broadcast(new SupportTicketUpdated($ticket, 'reply_added'));

        $message->load('attachments');

        return response()->json([
            'success' => true,
            'message' => $this->transformMessage($message),
        ]);
    }

    protected function transformTicket(SupportTicket $ticket): array
    {
        $data = $ticket->toArray();
        $messages = $ticket->messages ?? collect();

        $data['messages'] = collect($messages)->map(function ($message) {
            return $this->transformMessage($message);
        })->values()->all();

        return $data;
    }

    protected function transformMessage(SupportMessage $message): array
    {
        $data = $message->toArray();
        $data['sender_name'] = $this->resolveSenderName($message);
        $data['sender_avatar_url'] = $this->resolveSenderAvatarUrl($message);

        return $data;
    }

    protected function resolveSenderName(SupportMessage $message): string
    {
        if ($message->sender_type === 'tenant') {
            $tenantUser = \App\Models\User::query()->find($message->sender_id);
            return $tenantUser?->name ?: 'Clinic Staff';
        }

        $admin = DB::connection('central')
            ->table('users')
            ->where('id', $message->sender_id)
            ->first(['name']);

        return $admin?->name ?: 'Support Admin';
    }

    protected function resolveSenderAvatarUrl(SupportMessage $message): string
    {
        if ($message->sender_type === 'tenant') {
            $tenantUser = \App\Models\User::query()->find($message->sender_id);

            if ($tenantUser?->profile_picture_url) {
                return $tenantUser->profile_picture_url;
            }

            return 'https://ui-avatars.com/api/?name=' . urlencode($tenantUser?->name ?: 'Clinic Staff') . '&color=FFFFFF&background=1F2937';
        }

        $admin = DB::connection('central')
            ->table('users')
            ->where('id', $message->sender_id)
            ->first(['name', 'profile_picture']);

        if ($admin && !empty($admin->profile_picture)) {
            $path = (string) $admin->profile_picture;

            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:image/')) {
                return $path;
            }

            return asset('storage/' . ltrim($path, '/'));
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($admin?->name ?: 'Support Admin') . '&color=FFFFFF&background=334155';
    }

    protected function handleAttachments(Request $request, SupportMessage $message)
    {
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
    }
}
