{{-- A checklist line: a sage square with a tick, then the text. --}}
<li {{ $attributes->class('flex items-start gap-3 text-sm') }}>
    <span class="mt-0.5 grid size-5.5 shrink-0 place-items-center rounded-[0.4375rem] bg-public-sage text-public-deep"><x-public.icon name="check" class="size-3" stroke-width="3" /></span>
    <span>{{ $slot }}</span>
</li>
