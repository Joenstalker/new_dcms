<?php

namespace App\Http\Controllers\Admin;

use App\Events\SupportTicketUpdated;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    use ApiResponse;

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

        return $this->respondSuccess([
            'ticket' => $this->transformTicket($ticket),
        ], 'Support ticket details retrieved successfully.');
    }

    protected function transformTicket(SupportTicket $ticket): array
    {
        $data = $ticket->toArray();
        $messages = $ticket->messages ?? collect();

        $data['messages'] = collect($messages)->map(function ($message) use ($ticket) {
            return $this->transformMessage($message, $ticket);
        })->values()->all();

        return $data;
    }

    protected function transformMessage(SupportMessage $message, SupportTicket $ticket): array
    {
        $data = $message->toArray();
        $data['sender_name'] = $this->resolveSenderName($message, $ticket);
        $data['sender_avatar_url'] = $this->resolveSenderAvatarUrl($message, $ticket);

        return $data;
    }

    protected function resolveSenderName(SupportMessage $message, SupportTicket $ticket): string
    {
        if ($message->sender_type === 'tenant') {
            $tenantName = $ticket->tenant?->owner_name ?: $ticket->tenant?->name;

            return $tenantName ?: 'Tenant Clinic';
        }

        $admin = User::query()->find($message->sender_id);

        return $admin?->name ?: 'Support Admin';
    }

    protected function resolveSenderAvatarUrl(SupportMessage $message, SupportTicket $ticket): string
    {
        try {
            if ($message->sender_type === 'tenant') {
                $tenantUserPicture = $this->resolveTenantUserProfilePicture($ticket, (int) $message->sender_id);
                if ($tenantUserPicture !== null) {
                    return $tenantUserPicture;
                }

                $logo = (string) ($ticket->tenant?->logo_path ?? '');

                if ($logo !== '') {
                    if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://') || str_starts_with($logo, 'data:image/')) {
                        return $logo;
                    }

                    $tenantLogoUrl = $this->buildTenantStorageUrl($ticket, $logo);
                    if ($tenantLogoUrl !== null) {
                        return $tenantLogoUrl;
                    }

                    return asset('storage/'.ltrim($logo, '/'));
                }

                $tenantName = $ticket->tenant?->owner_name ?: $ticket->tenant?->name ?: 'Tenant Clinic';

                return 'https://ui-avatars.com/api/?name='.urlencode($tenantName).'&color=FFFFFF&background=1F2937';
            }

            $admin = User::query()->find($message->sender_id);

            if ($admin?->profile_picture_url) {
                return $admin->profile_picture_url;
            }

            return 'https://ui-avatars.com/api/?name='.urlencode($admin?->name ?: 'Support Admin').'&color=FFFFFF&background=334155';
        } catch (\Throwable $e) {
            $name = $this->resolveSenderName($message, $ticket);
            $bg = $message->sender_type === 'admin' ? '334155' : '1F2937';

            return 'https://ui-avatars.com/api/?name='.urlencode($name).'&color=FFFFFF&background='.$bg;
        }
    }

    protected function resolveTenantUserProfilePicture(SupportTicket $ticket, int $senderId): ?string
    {
        if (! $ticket->tenant || $senderId <= 0) {
            return null;
        }

        $tenantDatabase = (string) ($ticket->tenant?->database_name ?? '');
        if ($tenantDatabase === '') {
            return null;
        }

        try {
            $record = DB::connection('mysql')
                ->table($tenantDatabase.'.users')
                ->where('id', $senderId)
                ->first(['profile_picture']);

            $profilePicture = (string) ($record->profile_picture ?? '');
            if ($profilePicture === '') {
                return null;
            }

            if (str_starts_with($profilePicture, 'http://') || str_starts_with($profilePicture, 'https://') || str_starts_with($profilePicture, 'data:image/')) {
                return $profilePicture;
            }

            return $this->buildTenantStorageUrl($ticket, $profilePicture);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function buildTenantStorageUrl(SupportTicket $ticket, string $path): ?string
    {
        $tenantDomain = DB::connection('central')
            ->table('domains')
            ->where('tenant_id', (string) $ticket->tenant_id)
            ->value('domain');

        if (! $tenantDomain) {
            return null;
        }

        $appUrl = config('app.url', 'http://localhost');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'http';
        $centralHost = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';
        $port = parse_url($appUrl, PHP_URL_PORT);

        $host = str_contains($tenantDomain, '.')
            ? $tenantDomain
            : ($tenantDomain.'.'.$centralHost);

        $portSegment = $port ? ':'.$port : '';

        return $scheme.'://'.$host.$portSegment.'/tenant-storage/'.ltrim($path, '/');
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
