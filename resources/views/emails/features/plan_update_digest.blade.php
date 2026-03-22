<x-mail::message>
{{ $isAdvertisement ? '# Exciting News for ' . $plan->name . '!' : '# New Features Available for Your ' . $plan->name }}

Hi {{ $user->name }},

We've just released some fantastic new features specifically designed to improve your clinic's workflow. 

@if($isAdvertisement)
While your clinic isn't currently using the **{{ $plan->name }}**, we wanted to let you know about the powerful new capabilities we've added to it. If you ever feel like upgrading, these features are waiting for you!
@else
Because you are subscribed to the **{{ $plan->name }}**, these new capabilities are available to you immediately! Just head to your Clinic Portal > Settings to enable them.
@endif

### What's New:
@foreach($features as $feature)
- **{{ $feature->name }}**
  {{ $feature->description }}
@endforeach

<x-mail::button :url="config('app.url') . '/admin/settings?tab=updates'">
{{ $isAdvertisement ? 'View Plans & Upgrades' : 'Apply Updates Now' }}
</x-mail::button>

Thanks for growing with us,<br>
{{ config('app.name') }} Team
</x-mail::message>
