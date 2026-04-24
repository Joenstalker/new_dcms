@component('mail::message')
# Appointment Booking Update

Hello {{ $appointment->patient ? $appointment->patient->first_name : $appointment->guest_first_name }},

We regret to inform you that we are unable to accommodate your requested appointment booking at **{{ $tenant->name ?? 'our clinic' }}**.

**📅 Requested Date:** {{ $appointment->appointment_date->format('l, F j, Y') }}
**🕐 Requested Time:** {{ $appointment->appointment_date->format('g:i A') }}

@if($appointment->service)
**🦷 Service:** {{ $appointment->service }}
@endif

If you would like to choose a different time slot, please feel free to visit our booking page again or contact us directly.

@component('mail::button', ['url' => route('tenant.book.create')])
Rebook Appointment
@endcomponent

Thanks,<br>
{{ $tenant->name ?? config('app.name') }}
@endcomponent
