<?php

namespace App\Http\Controllers\Tenant\Support;

use App\Events\SupportTicketUpdated;
use App\Http\Controllers\Controller;
use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\SecuritySanitizationService;
use App\Services\TenantStorageUsageService;
use App\Traits\ApiResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SupportController extends Controller
{
    use ApiResponse;

    private const FIRST_MESSAGE_AUTO_REPLY = "Thank you for reaching out with your concern.\nWe value your feedback and will review it promptly.";

    private function authorizeSupportChat(): void
    {
        $user = auth()->user();

        if ($user && ($user->hasRole('Owner') || $user->can('access support chat'))) {
            return;
        }

        throw new HttpResponseException(
            $this->respondError('You do not have permission to access support chat.', 403)
        );
    }

    public function index()
    {
        $this->authorizeSupportChat();

        $tickets = SupportTicket::forCurrentTenant()
            ->with(['latestMessage'])
            ->latest('updated_at')
            ->get();

        return $this->respondSuccess([
            'tickets' => $tickets->map(function (SupportTicket $ticket) {
                $data = $ticket->toArray();

                if ($ticket->relationLoaded('latestMessage') && $ticket->latestMessage) {
                    $latest = $ticket->latestMessage;
                    $data['latest_message_sender_name'] = $this->resolveSenderName($latest);
                    $data['latest_message_sender_avatar_url'] = $this->resolveSenderAvatarUrl($latest);
                }

                return $data;
            })->values(),
        ], 'Support tickets retrieved successfully.');
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

        $tenantModel = tenant();
        if (! $tenantModel) {
            return $this->respondError('Missing tenant context.', 422);
        }
        $tenantId = $tenantModel->getTenantKey();
        $userId = Auth::id();
        $sanitizer = app(SecuritySanitizationService::class);

        $subject = $sanitizer->sanitizePlainText($validated['subject'], 255);
        $content = $sanitizer->sanitizeMultilineText($validated['content']);

        $ticket = SupportTicket::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'subject' => $subject,
            'description' => $content, // Initial description
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'status' => 'open',
        ]);

        $message = $ticket->messages()->create([
            'sender_id' => $userId,
            'sender_type' => 'tenant',
            'content' => $content,
        ]);

        $this->handleAttachments($request, $message);

        $this->createInitialAutoReplyIfNeeded($ticket);

        try {
            broadcast(new SupportTicketUpdated($ticket, 'created'));
        } catch (\Throwable $e) {
            Log::warning('Support ticket created but broadcast failed.', [
                'ticket_id' => $ticket->id,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->respondSuccess([
            'ticket' => $this->transformTicket($ticket->load('messages.attachments')),
        ], 'Support ticket created successfully.', 201);
    }

    protected function createInitialAutoReplyIfNeeded(SupportTicket $ticket): void
    {
        // Only send one automatic acknowledgment for the first tenant message in a ticket.
        if ($ticket->messages()->count() !== 1) {
            return;
        }

        $firstMessage = $ticket->messages()->oldest('id')->first(['sender_type']);

        if (! $firstMessage || $firstMessage->sender_type !== 'tenant') {
            return;
        }

        $ticket->messages()->create([
            'sender_id' => 0,
            'sender_type' => 'admin',
            'content' => self::FIRST_MESSAGE_AUTO_REPLY,
        ]);
    }

    public function show(SupportTicket $ticket)
    {
        $this->authorizeSupportChat();
        $this->authorize('view', $ticket);

        return $this->respondSuccess([
            'ticket' => $this->transformTicket($ticket->load(['messages.attachments', 'messages.sender'])),
        ], 'Support ticket details retrieved successfully.');
    }

    /**
     * Serve a support attachment for tenant chat.
     */
    public function attachment(SupportTicket $ticket, SupportAttachment $attachment)
    {
        $this->authorizeSupportChat();
        $this->authorize('view', $ticket);

        $message = SupportMessage::query()
            ->where('id', (int) $attachment->support_message_id)
            ->where('support_ticket_id', $ticket->id)
            ->first(['id', 'sender_type']);

        if (! $message) {
            abort(404);
        }

        $path = (string) $attachment->file_path;
        if ($path === '') {
            abort(404);
        }

        $disk = $message->sender_type === 'admin'
            ? Storage::build([
                'driver' => 'local',
                'root' => base_path('storage/app/public'),
                'throw' => false,
            ])
            : Storage::disk('support');

        if (! $disk->exists($path)) {
            abort(404);
        }

        return response($disk->get($path), 200, [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'Cache-Control' => 'private, max-age=604800',
        ]);
    }

    public function sendMessage(Request $request, SupportTicket $ticket)
    {
        $this->authorizeSupportChat();
        $this->authorize('update', $ticket);

        if ($ticket->status === 'closed') {
            return $this->respondError('This ticket is closed and read-only.', 422);
        }

        $validated = $request->validate([
            'content' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,zip',
        ]);

        $sanitizer = app(SecuritySanitizationService::class);
        $content = $sanitizer->sanitizeMultilineText($validated['content']);

        $message = $ticket->messages()->create([
            'sender_id' => Auth::id(),
            'sender_type' => 'tenant',
            'content' => $content,
        ]);

        $this->handleAttachments($request, $message);

        // Update ticket's last activity
        $ticket->touch();

        try {
            broadcast(new SupportTicketUpdated($ticket, 'reply_added'));
        } catch (\Throwable $e) {
            Log::warning('Support message saved but broadcast failed.', [
                'ticket_id' => $ticket->id,
                'message' => $e->getMessage(),
            ]);
        }

        $message->load('attachments');

        return $this->respondSuccess([
            'message' => $this->transformMessage($message, $ticket),
        ], 'Support message sent successfully.');
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

    protected function transformMessage(SupportMessage $message, ?SupportTicket $ticket = null): array
    {
        $data = $message->toArray();
        $data['sender_name'] = $this->resolveSenderName($message);
        $data['sender_avatar_url'] = $this->resolveSenderAvatarUrl($message);

        if ($ticket && ! empty($data['attachments']) && is_array($data['attachments'])) {
            $data['attachments'] = collect($data['attachments'])->map(function ($att) use ($ticket) {
                if (! is_array($att)) {
                    return $att;
                }

                $attachmentId = $att['id'] ?? null;
                if ($attachmentId) {
                    $att['url'] = route('tenant.support.attachment', [
                        'ticket' => $ticket->id,
                        'attachment' => $attachmentId,
                    ], absolute: false);
                }

                return $att;
            })->values()->all();
        }

        return $data;
    }

    protected function resolveSenderName(SupportMessage $message): string
    {
        if ($message->sender_type === 'tenant') {
            $tenantUser = User::query()->find($message->sender_id);

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
            $tenantUser = User::query()->find($message->sender_id);

            if ($tenantUser?->profile_picture_url) {
                return $tenantUser->profile_picture_url;
            }

            return 'https://ui-avatars.com/api/?name='.urlencode($tenantUser?->name ?: 'Clinic Staff').'&color=FFFFFF&background=1F2937';
        }

        $admin = DB::connection('central')
            ->table('users')
            ->where('id', $message->sender_id)
            ->first(['name', 'profile_picture']);

        if ($admin && ! empty($admin->profile_picture)) {
            $path = (string) $admin->profile_picture;

            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:image/')) {
                return $path;
            }

            $normalizedPath = ltrim($path, '/');
            if (! Storage::disk('public')->exists($normalizedPath)) {
                return 'https://ui-avatars.com/api/?name='.urlencode($admin->name ?: 'Support Admin').'&color=FFFFFF&background=334155';
            }

            return asset('storage/'.$normalizedPath);
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($admin?->name ?: 'Support Admin').'&color=FFFFFF&background=334155';
    }

    protected function handleAttachments(Request $request, SupportMessage $message)
    {
        if ($request->hasFile('attachments')) {
            $tenantId = (string) (tenant()?->getTenantKey() ?? '');
            $usage = app(TenantStorageUsageService::class);

            foreach ($request->file('attachments') as $file) {
                $path = $file->store('support/attachments', 'support');

                $message->attachments()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ]);

                if ($tenantId !== '') {
                    $usage->recordPut('support', $path, (int) $file->getSize(), $tenantId);
                }
            }
        }
    }
}
