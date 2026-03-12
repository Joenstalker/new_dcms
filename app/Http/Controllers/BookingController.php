<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BookingController extends Controller
{
    /**
     * Show the public booking page.
     */
    public function create()
    {
        // Tenant scope is active because of the tenant route group
        return Inertia::render('Booking/PublicBooking');
    }

    /**
     * Store the guest appointment booking.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'guest_first_name' => 'required|string|max:255',
            'guest_last_name' => 'required|string|max:255',
            'guest_phone' => 'required|string|max:20',
            'guest_email' => 'nullable|email|max:255',
            'appointment_date' => 'required|date|after:today',
            'service' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['status'] = 'scheduled';

        Appointment::create($validated);

        return redirect()->back()->with('success', 'Your appointment has been successfully booked!');
    }
}
