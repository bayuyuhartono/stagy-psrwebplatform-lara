<x-mail::message>
# Hello {{ $recipientName }},

{{ $replyBody }}

Regards,<br>
{{ config('app.name') }}
</x-mail::message>
