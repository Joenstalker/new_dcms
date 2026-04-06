@component('mail::message')
# {{ $isApproval ? 'Your Appointment is Acknowledged!' : 'Booking Confirmation' }}

Hello {{ $appointment->patient ? $appointment->patient->first_name : $appointment->guest_first_name }},

@if($isApproval)
Great news! Your upcoming appointment at **{{ $tenant->name ?? 'our clinic' }}** has been acknowledged by our team.
@else
Thank you for booking an appointment at **{{ $tenant->name ?? 'our clinic' }}**. Here are your appointment details:
@endif

---

**📅 Date:** {{ $appointment->appointment_date->format('l, F j, Y') }}

**🕐 Time:** {{ $appointment->appointment_date->format('g:i A') }}

@if($appointment->service)
**🦷 Service:** {{ $appointment->service }}
@endif

@if($appointment->dentist)
**👨‍⚕️ Dentist:** Dr. {{ $appointment->dentist->name }}
@endif

@if($appointment->booking_reference)
**🔖 Reference:** {{ $appointment->booking_reference }}
@endif

---

### Reminders
- Please arrive **10 minutes early** for registration.
- Bring any previous dental records if available.
- Contact us if you need to reschedule.

Thanks,<br>
{{ $tenant->name ?? config('app.name') }}
@endcomponent
