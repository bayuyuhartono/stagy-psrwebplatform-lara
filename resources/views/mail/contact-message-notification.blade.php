<x-mail::message>
# New contact message

**From:** {{ $contactMessage->name }} ({{ $contactMessage->email }})

**Subject:** {{ $contactMessage->subject }}

{{ $contactMessage->message }}
</x-mail::message>
