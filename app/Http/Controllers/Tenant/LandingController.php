<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Concern;

class LandingController extends Controller
{
    public function index()
    {
        $tenant = tenant();
        
        // Fetch approved services
        $services = Service::approved()->latest()->take(6)->get();
        
        // Fetch dentists
        $dentists = User::role('Dentist')->get(['id', 'name', 'email']);

        return Inertia::render('Tenant/Landing', [
            'tenant' => $tenant,
            'services' => $services,
            'dentists' => $dentists,
        ]);
    }

    public function submitConcern(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        Concern::create($validated);

        return redirect()->back()->with('success', 'Your message has been sent successfully! Our team will get back to you soon.');
    }
}
