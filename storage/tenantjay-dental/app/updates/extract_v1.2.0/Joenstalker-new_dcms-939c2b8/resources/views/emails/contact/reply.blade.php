<x-mail::message>
# Hello {{ $contactMessage->name }},

Thank you for reaching out to us. 

{{ $replyText }}

<br>
<br>

---
**Your original message:**
> {{ $contactMessage->message }}

Thanks,<br>
The {{ config('app.name') }} Team
</x-mail::message>
