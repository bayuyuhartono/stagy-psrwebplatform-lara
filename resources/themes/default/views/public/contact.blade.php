<x-public.layout :settings="$settings" :menu="$menu" metaTitle="Contact Us" metaDescription="Get in touch with us to start a project.">
    <section class="relative overflow-hidden bg-public-ink pb-24 pt-40 text-white lg:pb-32 lg:pt-52">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,var(--color-public-surface),transparent_50%)] opacity-40"></div>
        <div class="relative mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
            <p class="public-reveal text-xs font-bold uppercase tracking-[0.22em] text-public-accent">Get in touch</p>
            <div class="mt-6 grid gap-10 lg:grid-cols-[1fr_26rem] lg:items-end">
                <h1 class="public-reveal text-6xl font-semibold leading-[0.88] tracking-[-0.06em] sm:text-8xl lg:text-9xl">Let’s start<br>a conversation.</h1>
                <p class="public-reveal text-lg leading-8 text-white/55">Tell us about your next project, or just drop by to say hello. We are always looking for exciting opportunities.</p>
            </div>
        </div>
    </section>

    <section class="bg-public-paper py-20 lg:py-32">
        <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
            <div class="grid gap-16 lg:grid-cols-[1fr_1.5fr]">
                <div class="text-sm">
                    <h2 class="text-3xl font-semibold tracking-[-0.04em]">Contact Info</h2>
                    <div class="mt-8 space-y-6">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-public-ink/40">Email</p>
                            <p class="mt-1 font-medium text-lg">{{ $settings->get('contact')['email'] ?? 'hello@pesoros.com' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-public-ink/40">Phone</p>
                            <p class="mt-1 font-medium text-lg">{{ $settings->get('contact')['phone'] ?? '+62 123 4567 890' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-public-ink/40">Office</p>
                            <p class="mt-1 font-medium text-lg">{{ $settings->get('contact')['address'] ?? 'Jakarta, Indonesia' }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-[2rem] bg-white p-8 shadow-sm sm:p-12">
                    <form data-contact-form data-recaptcha-site-key="{{ $recaptchaSiteKey ?? '' }}" class="space-y-6">
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label for="name" class="block text-xs font-bold uppercase tracking-wide text-public-ink/60">Your Name *</label>
                                <input type="text" id="name" name="name" required maxlength="255" class="mt-2 block w-full rounded-xl border border-public-ink/10 bg-public-paper/50 px-4 py-3 text-sm focus:border-public-accent focus:outline-none focus:ring-1 focus:ring-public-accent">
                            </div>
                            <div>
                                <label for="email" class="block text-xs font-bold uppercase tracking-wide text-public-ink/60">Email Address *</label>
                                <input type="email" id="email" name="email" required maxlength="255" class="mt-2 block w-full rounded-xl border border-public-ink/10 bg-public-paper/50 px-4 py-3 text-sm focus:border-public-accent focus:outline-none focus:ring-1 focus:ring-public-accent">
                            </div>
                        </div>

                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label for="phone" class="block text-xs font-bold uppercase tracking-wide text-public-ink/60">Phone Number</label>
                                <input type="tel" id="phone" name="phone" maxlength="50" class="mt-2 block w-full rounded-xl border border-public-ink/10 bg-public-paper/50 px-4 py-3 text-sm focus:border-public-accent focus:outline-none focus:ring-1 focus:ring-public-accent">
                            </div>
                            <div>
                                <label for="subject" class="block text-xs font-bold uppercase tracking-wide text-public-ink/60">Subject *</label>
                                <select id="subject" name="subject" required class="mt-2 block w-full rounded-xl border border-public-ink/10 bg-public-paper/50 px-4 py-3 text-sm focus:border-public-accent focus:outline-none focus:ring-1 focus:ring-public-accent">
                                    <option value="" disabled selected>Select a subject</option>
                                    <option value="New Project">New Project Inquiry</option>
                                    <option value="Partnership">Partnership</option>
                                    <option value="General">General Question</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="message" class="block text-xs font-bold uppercase tracking-wide text-public-ink/60">Your Message *</label>
                            <textarea id="message" name="message" rows="5" required minlength="10" maxlength="10000" class="mt-2 block w-full rounded-xl border border-public-ink/10 bg-public-paper/50 px-4 py-3 text-sm focus:border-public-accent focus:outline-none focus:ring-1 focus:ring-public-accent"></textarea>
                        </div>

                        @if (filled($recaptchaSiteKey ?? null))
                            <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}"></div>
                            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                        @endif

                        <div data-contact-success class="hidden rounded-xl bg-green-50 p-4 text-sm text-green-800">
                            Thank you! Your message has been sent successfully. We will get back to you shortly.
                        </div>

                        <div data-contact-error class="hidden rounded-xl bg-red-50 p-4 text-sm text-red-800"></div>

                        <button data-contact-submit type="submit" class="inline-flex w-full items-center justify-center gap-3 rounded-full bg-public-ink px-6 py-4 text-sm font-bold text-white transition hover:bg-public-ink/90 disabled:opacity-70 sm:w-auto">
                            <span data-contact-submit-label>Send Message</span>
                            <span>↗</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</x-public.layout>
