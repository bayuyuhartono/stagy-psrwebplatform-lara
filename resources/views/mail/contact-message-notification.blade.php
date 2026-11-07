<x-mail::message>
@if ($contactMessage->form_type === \App\Features\ContactMessages\Enums\ContactFormType::Demo)
# New demo request

**From:** {{ $contactMessage->name }}@if (filled($contactMessage->company)) — {{ $contactMessage->company }}@endif

**WhatsApp:** {{ $contactMessage->whatsapp }}

@if (filled($contactMessage->email))
**Email:** {{ $contactMessage->email }}

@endif
**Expected guests:** {{ $contactMessage->guest_estimate?->label('en') ?? '—' }}

**Event date:** {{ $contactMessage->event_date?->format('j M Y') ?? '—' }}

**Interested plan:** {{ $contactMessage->interested_plan ?? '—' }}

@if (filled($contactMessage->message))
{{ $contactMessage->message }}
@endif
@else
# New contact message

**From:** {{ $contactMessage->name }} ({{ $contactMessage->email }})

**Subject:** {{ $contactMessage->subject }}

{{ $contactMessage->message }}
@endif
</x-mail::message>
