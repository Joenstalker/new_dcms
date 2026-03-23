<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use App\Models\Service;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    /**
     * Show the public booking page.
     */
    public function create()
    {
        $dentists = User::role('Dentist')->get(['id', 'name', 'email', 'profile_picture']);
        $services = Service::approved()->get(['id', 'name', 'description', 'price']);
        
        return Inertia::render('Tenant/Booking/PublicBooking', [
            'dentists' => $dentists,
            'services' => $services
        ]);
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
            'guest_address' => 'nullable|string',
            'guest_medical_history' => 'nullable|array',
            'appointment_date' => 'required|date|after:now',
            'service' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'dentist_id' => 'nullable|exists:users,id',
            'photo' => 'required|image|max:2048', // 2MB max
        ]);

        // Generate a unique booking reference
        $validated['booking_reference'] = 'BK-' . strtoupper(Str::random(8));
        
        // Find existing patient by email or phone (returning patient logic)
        $existingPatient = null;
        if (!empty($validated['guest_email'])) {
            $existingPatient = Patient::where('email', $validated['guest_email'])->first();
        }
        
        if (!$existingPatient && !empty($validated['guest_phone'])) {
            $existingPatient = Patient::where('phone', $validated['guest_phone'])->first();
        }
        
        if ($existingPatient) {
            $validated['patient_id'] = $existingPatient->id;
        }

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('bookings/photos', 'public');
            $validated['photo_path'] = $path;
        }

        $validated['status'] = 'pending';
        $validated['type'] = 'online_booking';

        $appointment = Appointment::create($validated);

        return redirect()->back()->with([
            'success' => 'Your appointment has been successfully booked!',
            'booking_reference' => $appointment->booking_reference
        ]);
    }
}
