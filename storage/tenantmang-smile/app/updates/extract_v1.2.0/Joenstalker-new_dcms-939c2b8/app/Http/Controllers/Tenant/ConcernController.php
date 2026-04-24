<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Concern;
use App\Mail\Tenant\ConcernReplyMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ConcernController extends Controller
{
    private function loadConcernOrFail(string $id): Concern
    {
        // Tenant context must already be initialized before hitting concerns.
        if (! tenant()) {
            abort(403, 'Tenant context is required.');
        }

        $concern = Concern::query()->find($id);
        if (! $concern) {
            abort(404);
        }

        return $concern;
    }

    /**
     * Update the status of a concern.
     */
    public function update(Request $request, string $concern)
    {
        $concern = $this->loadConcernOrFail($concern);

        $user = $request->user();
        if (! $user || ! ($user->hasRole('Owner') || $user->can('manage concerns'))) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,resolved,closed',
        ]);

        $concern->update($validated);

        return redirect()->back()->with('success', 'Concern status updated successfully.');
    }

    /**
     * Reply to a concern and email the patient.
     */
    public function reply(Request $request, string $concern)
    {
        $concern = $this->loadConcernOrFail($concern);

        $user = $request->user();
        if (! $user || ! ($user->hasRole('Owner') || $user->can('reply concerns'))) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => 'required|string|min:2|max:5000',
        ]);

        $tenantModel = tenant();
        $clinicName = (string) (($tenantModel->name ?? $tenantModel->id) ?: config('app.name', 'Clinic'));

        try {
            Mail::to($concern->email)->send(new ConcernReplyMail(
                concern: $concern,
                replyMessage: $validated['message'],
                clinicName: $clinicName,
            ));
        } catch (\Throwable $e) {
            Log::warning('Failed to send concern reply email', [
                'concern_id' => $concern->id,
                'tenant_id' => (string) $tenantModel->getTenantKey(),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Reply saved, but email failed to send.'], 500);
        }

        $concern->update([
            'reply_message' => $validated['message'],
            'replied_by' => $user->id,
            'replied_at' => now(),
            'status' => $concern->status === 'pending' ? 'in_progress' : $concern->status,
        ]);

        return response()->json(['success' => true]);
    }
}
