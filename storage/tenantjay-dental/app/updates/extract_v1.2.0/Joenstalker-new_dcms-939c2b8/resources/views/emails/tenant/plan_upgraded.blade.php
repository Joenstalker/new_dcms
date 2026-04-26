<x-mail::message>
# Great News!

Hi {{ $tenant->owner_name }},

Your clinic **{{ $tenant->name }}** has been upgraded to the **{{ $plan->name }}** plan by an administrator.

You now have access to all the features included in this plan.
@if($expiryDate)
This upgrade is valid until **{{ \Carbon\Carbon::parse($expiryDate)->format('M d, Y') }}**.
@endif

<x-mail::button :url="config('app.url')">
Login to your Clinic
</x-mail::button>

If you have any questions, please feel free to contact our support team.

Best regards,
The {{ config('app.name') }} Team
</x-mail::message>
