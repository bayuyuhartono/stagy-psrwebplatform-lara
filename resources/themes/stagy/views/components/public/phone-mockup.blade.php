{{-- A phone on a mint panel, used for the e-ticket illustrations. Decorative, so hidden from assistive technology. --}}
<div {{ $attributes->class('flex min-w-0 flex-[0_1_25rem] justify-center') }} aria-hidden="true">
    <div class="flex w-full justify-center rounded-[1.75rem] bg-public-mint px-12 py-10">
        <div class="w-64 rounded-[2.25rem] bg-public-ink p-2.5 shadow-[0_24px_48px_rgba(30,41,86,0.2)]">
            <div class="overflow-hidden rounded-[1.75rem] bg-public-paper">{{ $slot }}</div>
        </div>
    </div>
</div>
