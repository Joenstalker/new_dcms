@component('mail::message')
# Appointment Reminder

Hello {{ $appointment->patient ? $appointment->patient->first_name : $appointment->guest_first_name }},

This is a friendly reminder that you have an appointment **tomorrow** at **{{ $tenant->name ?? 'our clinic' }}**.

---

**📅 Date:** {{ $appointment->appointment_date->format('l, F j, Y') }}

**🕐 Time:** {{ $appointment->appointment_date->format('g:i A') }}

@if($appointment->service)
**🦷 Service:** {{ $appointment->service }}
@endif

@if($appointment->dentist)
**👨‍⚕️ Dentist:** Dr. {{ $appointment->dentist->name }}
@endif

---

### Reminders
- Please arrive **10 minutes early**.
- Bring any previous dental records if this is your first visit.
- If you need to cancel or reschedule, please contact us as soon as possible.

We look forward to seeing you!

Thanks,<br>
{{ $tenant->name ?? config('app.name') }}
@endcomponent
