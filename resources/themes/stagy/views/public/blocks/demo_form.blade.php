@inject('catalog', 'App\Features\Pricing\Services\PricingCatalog')
@inject('links', 'App\Features\ContentBlocks\Services\ContentLinkResolver')
@inject('recaptcha', 'App\Features\Integrations\Services\RecaptchaVerifier')

@php
    $locale = app()->getLocale();
    $privacyUrl = $links->resolve($data['privacy_url'] ?? null);
    $recaptchaSiteKey = $recaptcha->siteKey();
    $fieldClass = 'h-12 w-full rounded-xl border-[1.5px] border-public-line bg-public-paper px-4 text-sm text-public-ink outline-none transition focus:border-public-accent aria-invalid:border-red-600';
    $labelClass = 'flex flex-col gap-2 text-xs tracking-[0.04em] text-public-muted';
@endphp

<x-public.container as="section" class="flex flex-wrap items-start gap-14 pt-18 pb-22">
    <div class="flex min-w-0 flex-[1_1_21.25rem] flex-col gap-6">
        @if (filled($data['eyebrow'] ?? null))
            <x-public.eyebrow>{{ $data['eyebrow'] }}</x-public.eyebrow>
        @endif
        <x-public.heading :level="1" size="title" :text="$data['heading'] ?? ''" />
        @if (filled($data['intro'] ?? null))
            <p class="leading-[1.8] text-public-muted">{{ $data['intro'] }}</p>
        @endif
        @if (filled($data['next_steps'] ?? []))
            <ol class="flex flex-col gap-3.5">
                @foreach ($data['next_steps'] as $step)
                    <li class="flex items-start gap-3.5 text-sm leading-[1.7]">
                        <x-public.number-badge :number="$loop->iteration" size="sm" />
                        <span class="pt-1">{{ $step }}</span>
                    </li>
                @endforeach
            </ol>
        @endif
        @if (filled($links->whatsappUrl()))
            <div class="mt-2 flex flex-wrap items-center gap-4 rounded-2xl bg-public-mist p-5">
                @if (filled($data['chat_prompt'] ?? null))
                    <span class="text-sm">{{ $data['chat_prompt'] }}</span>
                @endif
                <x-public.button href="whatsapp" variant="accent" size="sm"><x-public.icon name="whatsapp" class="size-4.5" />{{ __('theme::site.chat_whatsapp') }}</x-public.button>
            </div>
        @endif
    </div>

    <form
        data-demo-form
        data-endpoint="{{ url('/api/v1/public/contact-messages').'?locale='.$locale }}"
        data-recaptcha-site-key="{{ $recaptchaSiteKey ?? '' }}"
        data-messages="{{ json_encode(['sending' => __('theme::site.demo.sending'), 'error' => __('theme::site.demo.error'), 'captcha' => __('theme::site.demo.captcha'), 'fields' => __('theme::site.demo.errors')], JSON_UNESCAPED_UNICODE) }}"
        novalidate
        class="flex min-w-0 flex-[1.4_1_28.75rem] flex-col gap-4.5 rounded-[1.75rem] border border-public-line bg-public-paper p-[clamp(1.25rem,4vw,2.25rem)] shadow-[0_24px_48px_rgba(30,41,86,0.06)]"
    >
        <div class="grid gap-4.5 sm:grid-cols-2">
            <label class="{{ $labelClass }}">{{ __('theme::site.demo.name') }} *
                <input name="name" type="text" required maxlength="255" autocomplete="name" placeholder="{{ __('theme::site.demo.name_placeholder') }}" class="{{ $fieldClass }}">
            </label>
            <label class="{{ $labelClass }}">{{ __('theme::site.demo.company') }}
                <input name="company" type="text" maxlength="255" autocomplete="organization" class="{{ $fieldClass }}">
            </label>
            <label class="{{ $labelClass }}">{{ __('theme::site.demo.whatsapp') }} *
                <input name="whatsapp" type="tel" required maxlength="50" autocomplete="tel" inputmode="tel" placeholder="{{ __('theme::site.demo.whatsapp_placeholder') }}" class="{{ $fieldClass }}">
            </label>
            <label class="{{ $labelClass }}">{{ __('theme::site.demo.email') }}
                <input name="email" type="email" maxlength="255" autocomplete="email" class="{{ $fieldClass }}">
            </label>
            <label class="{{ $labelClass }}">{{ __('theme::site.demo.guest_estimate') }} *
                <select name="guest_estimate" required class="{{ $fieldClass }}">
                    @foreach (\App\Features\ContactMessages\Enums\GuestEstimate::cases() as $estimate)
                        <option value="{{ $estimate->value }}">{{ $estimate->label($locale) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="{{ $labelClass }}">{{ __('theme::site.demo.event_date') }}
                <input name="event_date" type="date" min="{{ now()->toDateString() }}" class="{{ $fieldClass }}">
            </label>
        </div>
        <label class="{{ $labelClass }}">{{ __('theme::site.demo.plan') }}
            <select name="interested_plan" class="{{ $fieldClass }}">
                <option value="">{{ __('theme::site.demo.plan_undecided') }}</option>
                @foreach ($catalog->for()['plans'] as $plan)
                    <option value="{{ $plan->name }}">{{ $plan->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="{{ $labelClass }}">{{ __('theme::site.demo.message') }}
            <textarea name="message" rows="4" maxlength="10000" placeholder="{{ __('theme::site.demo.message_placeholder') }}" class="{{ $fieldClass }} h-auto py-3 leading-[1.6]"></textarea>
        </label>
        <label class="flex items-start gap-3 text-[0.8125rem] leading-[1.6]">
            <input name="consent" type="checkbox" required value="1" class="mt-0.5 size-5 shrink-0 accent-public-accent">
            <span>
                @if ($privacyUrl)
                    {!! __('theme::site.demo.consent', ['policy' => '<a href="'.e($privacyUrl).'" class="underline underline-offset-3 hover:text-public-accent">'.e(__('theme::site.demo.privacy_policy')).'</a>']) !!}
                @else
                    {{ __('theme::site.demo.consent_plain') }}
                @endif
            </span>
        </label>

        @if (filled($recaptchaSiteKey))
            <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}"></div>
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        @endif

        <div data-demo-success role="status" aria-live="polite" class="hidden rounded-xl bg-public-mint p-4 text-sm leading-[1.7] text-public-deep">{{ $data['success_message'] ?? __('theme::site.demo.success') }}</div>
        <div data-demo-error role="alert" class="hidden rounded-xl bg-red-50 p-4 text-sm leading-[1.7] text-red-800"></div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <button data-demo-submit type="submit" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-public-ink px-6 text-sm tracking-[0.04em] text-white transition hover:bg-public-deep disabled:opacity-60">{{ __('theme::site.demo.submit') }}</button>
            <span class="text-xs text-public-muted">{{ __('theme::site.demo.required_note') }}</span>
        </div>
    </form>
</x-public.container>
