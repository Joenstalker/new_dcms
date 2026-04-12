<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Models\Tenant;
use App\Models\Service;
use App\Models\User;
use App\Services\TenantBrandingService;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Concern;

class LandingController extends Controller
{
    public function index()
    {
        $tenant = tenant();
        $rawLanding = TenantBrandingService::get('landing_page_config', $tenant->landing_page_config ?? []);
        $landingConfig = Tenant::mergeLandingPageConfig(is_array($rawLanding) ? $rawLanding : null);
        $teamConfig = is_array($landingConfig['team'] ?? null) ? $landingConfig['team'] : [];
        $rawTeamSourceMode = $teamConfig['source_mode'] ?? 'auto_staff';
        $teamSourceMode = in_array($rawTeamSourceMode, ['auto_staff', 'manual', 'hybrid'], true)
            ? $rawTeamSourceMode
            : 'auto_staff';
        $includeOwner = (bool) ($teamConfig['include_owner'] ?? true);
        
        // Fetch approved services
        $services = Service::approved()->latest()->take(6)->get();
        
        // Fetch dentists
        $dentists = User::role('Dentist')->get(['id', 'name', 'email']);

        $autoRoleNames = $includeOwner
            ? ['Owner', 'Dentist', 'Assistant']
            : ['Dentist', 'Assistant'];

        $autoTeamMembers = User::query()
            ->with('roles:id,name')
            ->whereHas('roles', function ($query) use ($autoRoleNames) {
                $query->whereIn('name', $autoRoleNames);
            })
            ->get(['id', 'name', 'email', 'profile_picture'])
            ->map(function (User $member) {
                $primaryRole = optional($member->roles->first())->name ?? 'Staff';

                return [
                    'id' => 'staff-' . $member->id,
                    'source' => 'staff',
                    'name' => $member->name,
                    'role' => $primaryRole,
                    'bio' => '',
                    'image_url' => $member->profile_picture_url,
                ];
            })
            ->values()
            ->all();

        $manualTeamMembers = collect($teamConfig['manual_cards'] ?? [])
            ->filter(fn ($card) => is_array($card))
            ->map(function (array $card, int $index) {
                return [
                    'id' => $card['id'] ?? ('manual-' . $index),
                    'source' => 'manual',
                    'name' => (string) ($card['name'] ?? ''),
                    'role' => (string) ($card['role'] ?? ''),
                    'bio' => (string) ($card['bio'] ?? ''),
                    'image_url' => (string) ($card['image_url'] ?? ''),
                ];
            })
            ->filter(fn (array $card) => $card['name'] !== '')
            ->values()
            ->all();

        $teamMembers = match ($teamSourceMode) {
            'manual' => $manualTeamMembers,
            'hybrid' => array_values(array_merge($autoTeamMembers, $manualTeamMembers)),
            default => $autoTeamMembers,
        };

        $medicalRecords = MedicalRecord::active()
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        return Inertia::render('Tenant/Landing', [
            'services' => $services,
            'dentists' => $dentists,
            'teamMembers' => $teamMembers,
            'medicalRecords' => $medicalRecords,
            'recaptchaSiteKey' => config('services.recaptcha.site_key'),
            'online_booking_enabled' => $tenant->isOnlineBookingEnabled(),
            'operating_hours' => $tenant->getOperatingHoursWithDefaults(),
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
