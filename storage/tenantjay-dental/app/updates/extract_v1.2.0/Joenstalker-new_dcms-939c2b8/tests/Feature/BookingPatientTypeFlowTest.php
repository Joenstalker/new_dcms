<?php

namespace Tests\Feature;

use App\Http\Controllers\AppointmentController;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingPatientTypeFlowTest extends TestCase
{
    use DatabaseMigrations;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteTenantSqliteFiles();

        config(['app.url' => 'http://test-clinic.dcms.test']);
        $this->withServerVariables([
            'HTTP_HOST' => 'test-clinic.dcms.test',
            'SERVER_NAME' => 'test-clinic.dcms.test',
        ]);

        $this->tenant = Tenant::create(['id' => 'test-clinic']);
        $this->tenant->domains()->create(['domain' => 'test-clinic.dcms.test']);

        $plan = SubscriptionPlan::create([
            'name' => 'Booking Test Plan',
            'price_monthly' => 99,
            'max_users' => 100,
            'max_patients' => 1000,
            'max_appointments' => 1000,
        ]);

        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'subscription_plan_id' => $plan->id,
            'stripe_status' => 'active',
            'payment_status' => 'paid',
            'billing_cycle' => 'monthly',
            'stripe_id' => 'sub_booking_test_001',
        ]);

        $this->provisionTenantSqliteAndMigrate($this->tenant);
    }

    protected function tearDown(): void
    {
        if (function_exists('tenancy') && tenancy()->initialized) {
            tenancy()->end();
        }

        $this->deleteTenantSqliteFiles();

        parent::tearDown();
    }

    #[Test]
    public function existing_patient_booking_links_to_existing_patient_record(): void
    {
        tenancy()->initialize($this->tenant);
        $patient = Patient::create([
            'first_name' => 'Existing',
            'last_name' => 'Patient',
            'phone' => '09171234567',
            'email' => 'existing@clinic.test',
        ]);

        $request = Request::create('/book', 'POST', [
            'patient_type' => 'existing',
            'guest_first_name' => 'Existing',
            'guest_last_name' => 'Patient',
            'guest_phone' => '',
            'guest_email' => 'existing@clinic.test',
            'appointment_date' => $this->nextOpenDateTime()->format('Y-m-d H:i'),
            'service' => 'Consultation',
        ]);
        (new \App\Http\Controllers\BookingController())->store($request);

        $appointment = Appointment::latest('id')->first();
        $this->assertNotNull($appointment);
        $this->assertSame($patient->id, $appointment->patient_id);
        $this->assertSame('pending', $appointment->status);
        $this->assertSame('online_booking', $appointment->type);
        tenancy()->end();
    }

    #[Test]
    public function existing_patient_booking_without_match_returns_validation_error(): void
    {
        tenancy()->initialize($this->tenant);

        $response = (new \App\Http\Controllers\BookingController())->store(Request::create('/book', 'POST', [
            'patient_type' => 'existing',
            'guest_first_name' => 'No',
            'guest_last_name' => 'Match',
            'guest_phone' => '09999999999',
            'guest_email' => 'nomatch@clinic.test',
            'appointment_date' => $this->nextOpenDateTime()->format('Y-m-d H:i'),
            'service' => 'Consultation',
        ]));

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertSame(0, Appointment::count());
        tenancy()->end();
    }

    #[Test]
    public function new_patient_booking_stays_unlinked_and_creates_patient_only_on_approval(): void
    {
        tenancy()->initialize($this->tenant);
        Patient::create([
            'first_name' => 'Already',
            'last_name' => 'Registered',
            'phone' => '09170000000',
            'email' => 'already@clinic.test',
        ]);

        (new \App\Http\Controllers\BookingController())->store(Request::create('/book', 'POST', [
            'patient_type' => 'new',
            'guest_first_name' => 'Fresh',
            'guest_last_name' => 'Guest',
            'guest_phone' => '09170000000',
            'guest_email' => 'already@clinic.test',
            'appointment_date' => $this->nextOpenDateTime()->format('Y-m-d H:i'),
            'service' => 'Consultation',
        ]));

        $appointment = Appointment::latest('id')->first();
        $this->assertNotNull($appointment);
        $this->assertNull($appointment->patient_id);

        $patientsBeforeApprove = Patient::count();

        (new AppointmentController())->approve($appointment->id);

        $appointment->refresh();
        $this->assertNotNull($appointment->patient_id);
        $this->assertSame('scheduled', $appointment->status);
        $this->assertSame($patientsBeforeApprove + 1, Patient::count());
        tenancy()->end();
    }

    private function nextOpenDateTime(): Carbon
    {
        $candidate = now()->addDay()->setSecond(0);

        while (in_array($candidate->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY], true)) {
            $candidate->addDay();
        }

        return $candidate->setTime(10, 0);
    }
}
