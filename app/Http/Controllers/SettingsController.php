<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $tenant = tenant();
        $bookingUrl = route('tenant.book.create'); // Full booking URL
        
        // Generate QR Code as Base64 SVG
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)
            ->format('svg')
            ->generate($bookingUrl);

        return Inertia::render('Settings/Index', [
            'tenant' => $tenant,
            'booking_url' => $bookingUrl,
            'qr_code' => (string) $qrCode
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'clinic_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'branding_color' => 'nullable|string|max:7',
        ]);

        $tenant = tenant();
        if ($tenant) {
            $tenant->update($validated);
        }

        return redirect()->back()->with('success', 'Clinic settings updated successfully.');
    }
}
