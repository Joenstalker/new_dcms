<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Concern;
use Illuminate\Http\Request;

class ConcernController extends Controller
{
    /**
     * Update the status of a concern.
     */
    public function update(Request $request, Concern $concern)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,resolved,closed',
        ]);

        $concern->update($validated);

        return redirect()->back()->with('success', 'Concern status updated successfully.');
    }
}
