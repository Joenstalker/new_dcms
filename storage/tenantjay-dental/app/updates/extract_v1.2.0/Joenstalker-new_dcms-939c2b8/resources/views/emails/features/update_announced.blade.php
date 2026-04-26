<x-mail::message>
# Hello {{ $user->name }},

We are excited to announce a new feature for **{{ $tenant->name }}**!

## {{ $feature->name }}
{{ $feature->description }}

**Status:** {{ ucfirst(str_replace('_', ' ', $feature->implementation_status)) }}

To start using this feature or read more about it, please visit your clinic dashboard and apply the update.

<x-mail::button :url="config('app.url') . '/settings/updates'">
View Update in Dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>
