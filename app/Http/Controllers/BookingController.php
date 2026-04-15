<?php

namespace App\Http\Controllers;

use App\Events\OnlineBookingCreated;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\User;
use App\Models\Service;
use App\Models\Patient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\TenantStorageUsageService;

class BookingController extends Controller
{
    /**
     * Show the public booking page.
     */
    public function create()
    {
        $tenant = tenant();

        // Gate: If online booking is disabled, show unavailable page
        if (!$tenant->isOnlineBookingEnabled()) {
            return Inertia::render('Tenant/Booking/BookingUnavailable', [
                'tenant' => $tenant,
            ]);
        }

        $dentists = User::role('Dentist')->get(['id', 'name', 'email', 'profile_picture']);
        $services = Service::approved()->get(['id', 'name', 'description', 'price']);
        $medicalRecords = MedicalRecord::active()
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return Inertia::render('Tenant/Booking/PublicBooking', [
            'dentists' => $dentists,
            'services' => $services,
            'medicalRecords' => $medicalRecords,
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
            'guest_phone' => ['required', 'regex:/^\d{11}$/'],
            'guest_email' => 'nullable|email|max:255',
            'guest_address' => 'nullable|string',
            'guest_medical_history' => 'nullable|array',
            'appointment_date' => 'required|date|after:now',
            'service' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'dentist_id' => 'nullable|exists:users,id',
            'photo' => 'nullable|image|max:5120', // optional, 5MB max
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
            $file = $request->file('photo');
            $path = $file->store('bookings/photos', 'public');
            $validated['photo_path'] = $path;
            app(TenantStorageUsageService::class)->recordPut('public', $path, (int) $file->getSize());

            if ($existingPatient) {
                $existingPatient->update(['photo_path' => $path]);
            }
        }

        $validated['status'] = 'pending';
        $validated['type'] = 'online_booking';

        $appointment = Appointment::create($validated);

        broadcast(new OnlineBookingCreated((string)tenant()->getTenantKey(), [
            'id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'dentist_id' => $appointment->dentist_id,
            'guest_first_name' => $appointment->guest_first_name,
            'guest_last_name' => $appointment->guest_last_name,
            'guest_phone' => $appointment->guest_phone,
            'appointment_date' => optional($appointment->appointment_date)?->toISOString(),
            'status' => $appointment->status,
            'type' => $appointment->type,
            'service' => $appointment->service,
            'notes' => $appointment->notes,
            'photo_path' => $appointment->photo_path,
            'patient' => null,
        ]));

        return redirect()->back()->with([
            'success' => 'Your appointment has been successfully booked!',
            'booking_reference' => $appointment->booking_reference
        ]);
    }
}
