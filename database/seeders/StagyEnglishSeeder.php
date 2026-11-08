<?php

namespace Database\Seeders;

use App\Features\Menus\Enums\MenuItemTarget;
use App\Features\Menus\Enums\MenuItemType;
use App\Features\Pages\Enums\PageStatus;
use App\Features\Seo\Services\SeoService;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PricingAddon;
use App\Models\PricingComparisonRow;
use App\Models\PricingPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * English translations of the Stagy pages, menus and pricing. Each row joins
 * the translation group of its Indonesian original, so the language switcher
 * and hreflang pair them up. Run after the Indonesian seeders.
 *
 * Links inside blocks are stored as-is, so they carry the "/en" prefix here.
 * The blog is left out of the English menus until English posts exist.
 * Anything that already has an English version is left alone.
 */
class StagyEnglishSeeder extends Seeder
{
    private const LOCALE = 'en';

    public function run(SeoService $seo): void
    {
        $this->seedPages($seo);
        $this->seedMenus();
        $this->seedPricing();
    }

    private function seedPages(SeoService $seo): void
    {
        $metadata = $this->seo();

        foreach ($this->pages() as $sourceSlug => [$slug, $title, $blocks]) {
            $source = Page::query()->where('slug', $sourceSlug)->where('locale', 'id')->first();

            if ($source === null || $this->hasEnglish($source)) {
                continue;
            }

            $page = Page::query()->create([
                'locale' => self::LOCALE,
                'translation_group_id' => $source->translation_group_id,
                'slug' => $slug,
                'title' => $title,
                'status' => PageStatus::Published,
                'blocks' => $blocks,
                'published_at' => now(),
            ]);

            if (isset($metadata[$slug])) {
                $seo->updateForContent($page, [...$metadata[$slug], 'robots_index' => true, 'robots_follow' => true, 'twitter_card' => 'summary_large_image']);
            }
        }
    }

    private function seedMenus(): void
    {
        $menus = [
            'header' => ['Main menu', [['Features', 'features'], ['How It Works', 'how-it-works'], ['Pricing', 'pricing'], ['FAQ', 'faq']]],
            'footer-product' => ['Footer · Product', [['Features', 'features'], ['How It Works', 'how-it-works'], ['Pricing', 'pricing']]],
            'footer-help' => ['Footer · Help', [['FAQ', 'faq'], ['Request a Demo', 'demo'], ['Terms & Conditions', 'terms'], ['Privacy Policy', 'privacy-policy']]],
        ];

        foreach ($menus as $location => [$name, $items]) {
            $source = Menu::query()->where('location', $location)->where('locale', 'id')->first();

            if (Menu::query()->where('location', $location)->where('locale', self::LOCALE)->exists()) {
                continue;
            }

            $menu = Menu::query()->create([
                'locale' => self::LOCALE,
                'translation_group_id' => $source?->translation_group_id ?? Menu::translationDefaults(self::LOCALE)['translation_group_id'],
                'location' => $location,
                'name' => $name,
                'is_active' => true,
            ]);

            foreach ($items as $position => [$label, $slug]) {
                $page = Page::query()->where('slug', $slug)->where('locale', self::LOCALE)->first();

                MenuItem::query()->create([
                    'menu_id' => $menu->getKey(),
                    'label' => $label,
                    'type' => $page ? MenuItemType::Page : MenuItemType::Custom,
                    'page_id' => $page?->getKey(),
                    'url' => $page ? null : '/en/'.$slug,
                    'target' => MenuItemTarget::Self,
                    'position' => $position,
                ]);
            }
        }
    }

    /**
     * Plans, comparison rows and add-ons are matched to the Indonesian ones by
     * position. Prices stay in rupiah; the formatter writes them as "IDR … million".
     */
    private function seedPricing(): void
    {
        if (PricingPlan::query()->where('locale', self::LOCALE)->exists()) {
            return;
        }

        $sourcePlans = PricingPlan::query()->where('locale', 'id')->orderBy('position')->get();

        if ($sourcePlans->isEmpty()) {
            return;
        }

        $plans = collect([
            ['200 guests included · QR scan', 'On-site team · 2 QR scanners', null, 'Choose Basic', [
                'Registration system, QR & dashboard', 'WhatsApp invitations for 200 guests', '2 QR scanners', 'On-site team', 'Automatic PDF certificates', 'Reports & Excel export',
            ]],
            ['200 guests included · ID card tap', 'On-site team · 3 ID card tap readers', 'Most Popular', 'Choose Professional', [
                'Everything in Basic, plus:', 'ID card + lanyard for every guest', '3 card readers, guests just tap their ID card', 'Door prize on the big screen', 'Backup QR scanner',
            ]],
            ['200 guests included · automatic gate', 'On-site team · automatic gate', null, 'Talk to us about Enterprise', [
                'Everything in Professional, plus:', 'Automatic gate, guests walk through without stopping', 'Automatic-gate ID card + lanyard + hardcase for every guest', 'Card reader at the help desk',
            ]],
        ])->take($sourcePlans->count())->map(function (array $plan, int $position) use ($sourcePlans): PricingPlan {
            $source = $sourcePlans[$position];

            return PricingPlan::query()->create([
                ...$this->translationOf($source),
                'name' => $source->name, 'tagline' => $plan[0], 'price' => $source->price, 'extra_guest_price' => $source->extra_guest_price,
                'support_summary' => $plan[1], 'is_highlighted' => $source->is_highlighted, 'highlight_label' => $plan[2],
                'cta_label' => $plan[3], 'cta_url' => null, 'features' => $plan[4], 'position' => $source->position,
            ]);
        });

        $planIdsBySource = $sourcePlans->take($plans->count())->pluck('id')->combine($plans->pluck('id'));

        $comparison = [
            ['Guests & pricing', 'Guests included'],
            ['Guests & pricing', 'Per extra guest', ['Rp2.500' => 'IDR 2,500', 'Rp17.500' => 'IDR 17,500', 'Rp27.500' => 'IDR 27,500']],
            ['Guests & pricing', 'Check-in method', ['Scan QR' => 'QR scan', 'Tap ID card' => 'ID card tap', 'Gate otomatis' => 'Automatic gate']],
            ['Registration & invitations', 'Import guest data from Excel'],
            ['Registration & invitations', 'Online registration link + approval'],
            ['Registration & invitations', 'VVIP / VIP / Regular guest levels'],
            ['Registration & invitations', 'WhatsApp invitations', ['200 tamu' => '200 guests']],
            ['Registration & invitations', 'RSVP + QR e-ticket'],
            ['Check-in at the venue', 'QR scanner', ['1 (cadangan)' => '1 (backup)']],
            ['Check-in at the venue', 'Card tap reader'],
            ['Check-in at the venue', 'Automatic gate (guests walk through without stopping)'],
            ['Check-in at the venue', 'ID card + lanyard', ['ID card tap, tiap tamu' => 'Tap ID card, every guest', 'ID card gate + hardcase, tiap tamu' => 'Gate ID card + hardcase, every guest']],
            ['Check-in at the venue', 'Welcome screen'],
            ['Check-in at the venue', 'Check-in per room & session'],
            ['Event moments', 'Door prize on the big screen'],
            ['Event moments', 'WhatsApp notification to winners'],
            ['Event moments', 'Automatic PDF certificates'],
            ['Reports', 'Real-time dashboard'],
            ['Reports', 'Daily & per-session reports'],
            ['Reports', 'Excel export'],
            ['Team & support', 'On-site team on event day', ['1 tim' => '1 team']],
        ];

        $sourceRows = PricingComparisonRow::query()->where('locale', 'id')->orderBy('position')->get();

        foreach ($sourceRows->take(count($comparison)) as $index => $source) {
            [$group, $label, $values] = $comparison[$index] + [2 => []];

            PricingComparisonRow::query()->create([
                ...$this->translationOf($source),
                'group_name' => $group,
                'label' => $label,
                'values' => collect($source->values)
                    ->only($planIdsBySource->keys()->all())
                    ->mapWithKeys(fn (mixed $value, string $planId): array => [$planIdsBySource[$planId] => is_string($value) ? ($values[$value] ?? $value) : $value])
                    ->all(),
                'position' => $source->position,
            ]);
        }

        $addons = [
            ['Extra guest (WhatsApp invitation)', '/ guest'],
            ['Tap ID card + lanyard', '/ guest'],
            ['Automatic-gate ID card + lanyard + hardcase', '/ guest'],
            ['Card tap reader', '/ unit'],
            ['Printed certificate (Art Carton 260 gsm)', '/ sheet'],
            ['Door prize (for Basic)', '/ event'],
            ['Extra QR scanner', '/ unit'],
            ['Check-in point laptop', '/ unit'],
            ['Card tap reader (automatic gate)', '/ unit'],
            ['Extra automatic gate', '/ unit'],
            ['Extra on-site team', '/ team'],
        ];

        $sourceAddons = PricingAddon::query()->where('locale', 'id')->orderBy('position')->get();

        foreach ($sourceAddons->take(count($addons)) as $index => $source) {
            PricingAddon::query()->create([
                ...$this->translationOf($source),
                'name' => $addons[$index][0], 'price' => $source->price, 'unit' => $addons[$index][1], 'position' => $source->position,
            ]);
        }
    }

    /**
     * @return array{locale: string, translation_group_id: string}
     */
    private function translationOf(Model $source): array
    {
        return ['locale' => self::LOCALE, 'translation_group_id' => $source->getAttribute('translation_group_id')];
    }

    private function hasEnglish(Page $source): bool
    {
        return Page::query()->where('translation_group_id', $source->translation_group_id)->where('locale', self::LOCALE)->exists();
    }

    /**
     * @return array<string, array{meta_title: ?string, meta_description: string, focus_keyword: ?string}>
     */
    private function seo(): array
    {
        return [
            'home' => ['meta_title' => 'Stagy · Event Registration & Check-in for Event Organisers', 'focus_keyword' => 'event registration', 'meta_description' => 'Import guest data, send QR invitations over WhatsApp & email, then check guests in at the venue in one system. Attendance reports ready for your client.'],
            'features' => ['meta_title' => 'Event Registration, QR Invitation & Check-in Features · Stagy', 'focus_keyword' => 'event check-in', 'meta_description' => 'Online registration, WhatsApp & email invitations with QR, QR or RFID check-in at the venue, door prizes, certificates and real-time attendance reports.'],
            'how-it-works' => ['meta_title' => 'How Stagy Works: From Guest List to Report', 'focus_keyword' => 'how it works', 'meta_description' => 'Four steps with Stagy: send your guest list, invitations go out, guests check in at the venue, and the report is ready. Our team is with you on event day.'],
            'pricing' => ['meta_title' => 'Event Registration & Check-in Pricing · Stagy', 'focus_keyword' => 'pricing', 'meta_description' => 'Event registration & check-in packages priced per event, with no subscription. Compare what each plan includes and the add-ons you can bolt on.'],
            'faq' => ['meta_title' => 'Event Registration & Check-in FAQ · Stagy', 'focus_keyword' => 'faq', 'meta_description' => 'Answers to common questions about guest registration, QR invitations, venue check-in, data security and Stagy pricing for event organisers.'],
            'demo' => ['meta_title' => 'Request a Demo of the Event Registration System · Stagy', 'focus_keyword' => 'demo', 'meta_description' => 'Book a Stagy demo for your client’s event. Tell us the expected guest count and event date, and our team will get in touch.'],
            'terms' => ['meta_title' => null, 'focus_keyword' => null, 'meta_description' => 'The terms and conditions for using Stagy’s event registration and check-in service, for event organisers and their clients.'],
            'privacy-policy' => ['meta_title' => null, 'focus_keyword' => null, 'meta_description' => 'How Stagy collects, uses, stores and protects guest data and the client data of the event organisers who use our service.'],
        ];
    }

    /**
     * English pages keyed by the slug of their Indonesian original.
     *
     * @return array<string, array{0: string, 1: string, 2: list<array{type: string, data: array<string, mixed>}>}>
     */
    private function pages(): array
    {
        return [
            'home' => ['home', 'Home', [
                ['type' => 'hero', 'data' => [
                    'badge' => 'Registration & check-in for event organisers',
                    'heading' => 'Your clients’ event registration & check-in, *handled* in one system.',
                    'subheading' => 'Import guests, send QR invitations over WhatsApp & email, then check guests in fast at the venue. The attendance report is ready for your client as soon as the event ends.',
                    'primary_action' => ['label' => 'Request a Demo', 'url' => '/en/demo'],
                    'secondary_action' => ['label' => 'See Plans', 'url' => '/en/pricing'],
                    'trust_points' => ['Guest data kept safe', 'On-site team on event day', 'Priced per event, no subscription'],
                    'visual' => 'ticket',
                    'ticket_label' => 'E-ticket · VIP',
                    'ticket_event' => '[Event Name] Annual Gathering 2026',
                    'ticket_guest' => 'Budi Santoso',
                    'ticket_detail' => 'PT Contoh · Table 12',
                    'ticket_time' => '12 Dec · 6.00 PM',
                    'ticket_gate' => 'Gate A',
                ]],
                ['type' => 'feature_cards', 'data' => [
                    'eyebrow' => 'Features',
                    'heading' => 'From invitation to report, *all* in one place.',
                    'link' => ['label' => 'See all features', 'url' => '/en/features'],
                    'items' => [
                        ['title' => 'Before the event', 'description' => 'Excel import, online registration, WhatsApp & email invitations with QR.', 'url' => '/en/features#before'],
                        ['title' => 'At the venue', 'description' => 'Scan a QR, tap an ID card or walk through an automatic gate. Welcome screen and per-session check-in.', 'url' => '/en/features#venue'],
                        ['title' => 'Event moments', 'description' => 'Door prizes on the big screen and automatic PDF certificates.', 'url' => '/en/features#highlight'],
                        ['title' => 'After the event', 'description' => 'A real-time dashboard and reports ready to send to your client.', 'url' => '/en/features#after'],
                    ],
                ]],
                ['type' => 'steps', 'data' => [
                    'eyebrow' => 'How it works',
                    'heading' => 'Four steps, from data to *report*.',
                    'link' => ['label' => 'See how it works', 'url' => '/en/how-it-works'],
                    'variant' => 'compact',
                    'items' => [
                        ['title' => 'Send your guest list (Excel) or open a registration link'],
                        ['title' => 'Invitations + QR go out to guests over WhatsApp/email'],
                        ['title' => 'Guests check in at the venue (QR or RFID)'],
                        ['title' => 'Attendance report ready to send to your client'],
                    ],
                ]],
                ['type' => 'pricing_cards', 'data' => [
                    'eyebrow' => 'Plans & pricing',
                    'heading' => 'Clear pricing per event. *No* subscription.',
                    'link' => ['label' => 'Compare all plans', 'url' => '/en/pricing'],
                    'variant' => 'summary',
                ]],
                ['type' => 'call_to_action', 'data' => [
                    'heading' => 'Ready to run your clients’ events more smoothly?',
                    'body' => 'Tell us about the event and our team will reach you on WhatsApp during business hours.',
                    'label' => 'Request a Demo',
                    'url' => '/en/demo',
                    'secondary_action' => ['label' => 'Chat on WhatsApp', 'url' => 'whatsapp'],
                ]],
            ]],

            'fitur' => ['features', 'Features', [
                ['type' => 'page_header', 'data' => [
                    'eyebrow' => 'Features',
                    'heading' => 'Everything an event organiser needs, from invitation to *report*.',
                    'intro' => 'One system for the whole guest journey at your clients’ events. Pick a stage to jump straight to its features.',
                    'links' => [
                        ['label' => '01 Before the event', 'url' => '#before'],
                        ['label' => '02 At the venue', 'url' => '#venue'],
                        ['label' => '03 Event moments', 'url' => '#highlight'],
                        ['label' => '04 After the event', 'url' => '#after'],
                    ],
                ]],
                ['type' => 'feature_group', 'data' => [
                    'anchor' => 'before', 'label' => 'Before the event', 'heading' => 'Guests registered, invitations sent',
                    'description' => 'Prepare your client’s guest list without passing spreadsheets back and forth.',
                    'points' => ['Import guest data from Excel', 'Online registration link + approval', 'VVIP / VIP / Regular guest levels', 'Bulk WhatsApp & email invitations', 'RSVP + automatic QR e-ticket'],
                    'media_placeholder' => 'Screenshot: guest list & sending invitations',
                ]],
                ['type' => 'feature_group', 'data' => [
                    'anchor' => 'venue', 'label' => 'At the venue', 'heading' => 'Fast check-in, shorter queues',
                    'description' => 'Guests just show their QR, tap an ID card or walk through an automatic gate.',
                    'points' => ['Scan QR with a phone camera or scanner', 'Tap an ID card on a card reader', 'Automatic gate, guests walk through without stopping', 'Welcome screen greets VIP guests', 'Check-in per room & session, tap in/out', 'ID card printing & merchandise hand-over'],
                    'media_placeholder' => 'Screenshot: scan station & welcome screen',
                ]],
                ['type' => 'feature_group', 'data' => [
                    'anchor' => 'highlight', 'label' => 'Event moments', 'heading' => 'Moments guests remember',
                    'description' => 'Entertainment features that make your client’s event livelier.',
                    'points' => ['Door prize on the big screen', 'WhatsApp notification to winners', 'Automatic PDF certificates'],
                    'media_placeholder' => 'Screenshot: door prize display on the big screen',
                ]],
                ['type' => 'feature_group', 'data' => [
                    'anchor' => 'after', 'label' => 'After the event', 'heading' => 'Reports ready to send to your client',
                    'description' => 'Attendance data is tidy the moment the event ends.',
                    'points' => ['Real-time dashboard', 'Daily & per-session reports', 'One-click Excel export'],
                    'media_placeholder' => 'Screenshot: real-time attendance dashboard',
                ]],
                ['type' => 'call_to_action', 'data' => [
                    'heading' => 'Want to see the features in action?',
                    'label' => 'Request a Demo',
                    'url' => '/en/demo',
                    'secondary_action' => ['label' => 'See Pricing', 'url' => '/en/pricing'],
                ]],
            ]],

            'cara-kerja' => ['how-it-works', 'How It Works', [
                ['type' => 'page_header', 'data' => [
                    'eyebrow' => 'How it works',
                    'heading' => 'Four steps, from guest list to *report*.',
                    'intro' => 'You focus on your client and the event. We take care of the guest list, invitations and check-in.',
                ]],
                ['type' => 'steps', 'data' => [
                    'variant' => 'detailed',
                    'items' => [
                        ['title' => 'Send the guest list', 'you' => 'Send your client’s guest list in Excel, or share an online registration link.', 'us' => 'Clean up the data and WhatsApp numbers, set guest levels & approvals.'],
                        ['title' => 'Invitations go out', 'you' => 'Approve the invitation wording for your client.', 'us' => 'Send invitations + QR over WhatsApp & email and track RSVPs.'],
                        ['title' => 'Check-in at the venue', 'you' => 'Focus on running the event.', 'us' => 'QR scan, ID card tap or automatic gate. Welcome screen and per-session check-in.'],
                        ['title' => 'Report ready', 'you' => 'Forward the report to your client.', 'us' => 'Real-time dashboard, daily & per-session reports, Excel export.'],
                    ],
                ]],
                ['type' => 'support_tiers', 'data' => [
                    'eyebrow' => 'Event day',
                    'heading' => 'On-site support at the venue, by plan.',
                    'link' => ['label' => 'Compare plans', 'url' => '/en/pricing'],
                ]],
                ['type' => 'call_to_action', 'data' => [
                    'heading' => 'Got a client event coming up?',
                    'label' => 'Request a Demo',
                    'url' => '/en/demo',
                    'secondary_action' => ['label' => 'Chat on WhatsApp', 'url' => 'whatsapp'],
                ]],
            ]],

            'harga' => ['pricing', 'Pricing', [
                ['type' => 'page_header', 'data' => [
                    'eyebrow' => 'Plans & pricing',
                    'heading' => 'Clear pricing per event. *No* subscription.',
                    'intro' => 'Every plan includes 200 guests, the system, WhatsApp invitations, check-in devices and an on-site team. More guests? Just add them per guest.',
                ]],
                ['type' => 'pricing_cards', 'data' => [
                    'variant' => 'full',
                    'note' => 'Prices per event. [To confirm: duration included & whether VAT is included]',
                ]],
                ['type' => 'pricing_table', 'data' => [
                    'eyebrow' => 'Comparison',
                    'heading' => 'Compare what each plan includes.',
                    'note' => 'Items marked "Add-on" can be added at the prices in the add-on table below.',
                    'show_included_in_all' => true,
                ]],
                ['type' => 'pricing_addons', 'data' => [
                    'eyebrow' => 'Add-ons',
                    'heading' => 'Add what your client needs.',
                    'intro' => 'Available with any plan, at the same unit price. Questions about payment? Check the FAQ.',
                ]],
            ]],

            'faq' => ['faq', 'FAQ', [
                ['type' => 'faq', 'data' => [
                    'eyebrow' => 'FAQ',
                    'heading' => 'Questions event organisers *often* ask.',
                    'intro' => 'Can’t find your answer? Our team is happy to help on WhatsApp.',
                    'show_whatsapp' => true,
                    'items' => [
                        ['question' => 'Can guests check in with a phone?', 'answer' => 'Yes. QR codes can be scanned with a phone camera or a scanner. The Professional plan uses ID cards that guests simply tap on a card reader, and Enterprise uses an automatic gate so guests walk through without stopping.'],
                        ['question' => 'Does it work for multi-day events or several rooms?', 'answer' => 'Yes. Check-in can be split per room and per session. [To confirm: cost of additional days]'],
                        ['question' => 'Is my client’s guest data safe?', 'answer' => 'Guest data is only visible to authorised admins. [To confirm: compliance with Indonesia’s PDP Law and data storage details]'],
                        ['question' => 'Do we need internet at the venue?', 'answer' => '[Answer from the team: connectivity requirements at the venue]'],
                        ['question' => 'How long does setup take before the event?', 'answer' => '[Answer from the team: estimated setup time]'],
                        ['question' => 'Can the invitation & e-ticket design be customised?', 'answer' => '[Answer from the team: custom design options]'],
                        ['question' => 'Can we use the organiser’s or client’s branding?', 'answer' => '[Answer from the team: organiser/client branding options on invitations, e-tickets and the welcome screen]'],
                        ['question' => 'How does payment work?', 'answer' => '[Answer from the team: payment terms & methods]'],
                    ],
                ]],
            ]],

            'demo' => ['demo', 'Request a Demo', [
                ['type' => 'demo_form', 'data' => [
                    'eyebrow' => 'Request a demo',
                    'heading' => 'Tell us about your client’s event, we’ll *prepare* the demo.',
                    'intro' => 'Here’s what happens after you send the form:',
                    'next_steps' => [
                        'Our team will reach you on WhatsApp during business hours.',
                        'A short demo tailored to the type and size of your client’s event.',
                        'A price quote ready to forward to your client.',
                    ],
                    'chat_prompt' => 'Faster over chat?',
                    'privacy_url' => '/en/privacy-policy',
                ]],
            ]],

            'syarat-ketentuan' => ['terms', 'Terms & Conditions', [
                ['type' => 'legal_sections', 'data' => [
                    'eyebrow' => 'Legal',
                    'heading' => 'Terms & Conditions',
                    'updated' => 'Last updated: [date]',
                    'intro' => '[Opening paragraph from the legal team: who the parties to this agreement are and when it takes effect once an organiser uses Stagy.]',
                    'sections' => collect([
                        'Definitions' => 'terms such as Service, Organiser, Client, Guest and Event',
                        'Scope of service' => 'what each plan and add-on includes',
                        'Orders & payment' => 'payment terms, methods and deadlines',
                        'Cancellation & rescheduling' => 'refund and event rescheduling rules',
                        'Organiser obligations' => 'complete guest data, client consent, venue access',
                        'Guest data & privacy' => 'data ownership, retention and a reference to the Privacy Policy',
                        'Limitation of liability' => 'network outages, venue equipment, force majeure',
                        'Governing law' => 'jurisdiction and dispute resolution',
                        'Changes to these terms' => 'how Stagy announces changes',
                    ])->map(fn (string $topic, string $title): array => ['title' => $title, 'body' => "[Content from the legal team: {$topic}.]"])->values()->all(),
                    'closing' => 'Questions about these terms? Reach us on WhatsApp or by email. See also the Privacy Policy.',
                ]],
            ]],

            'kebijakan-privasi' => ['privacy-policy', 'Privacy Policy', [
                ['type' => 'legal_sections', 'data' => [
                    'eyebrow' => 'Legal',
                    'heading' => 'Privacy Policy',
                    'updated' => 'Last updated: [date]',
                    'intro' => '[Opening paragraph from the legal team about Stagy’s commitment to the personal data of organisers, clients and guests.]',
                    'sections' => collect([
                        'Data we collect' => 'organiser data, guest data and technical data',
                        'How we use data' => 'the purposes of processing',
                        'Storage & security' => 'location, retention period and safeguards',
                        'Sharing data with others' => 'WhatsApp, email and hosting providers',
                        'Your rights' => 'access, correction and deletion under Indonesia’s PDP Law',
                        'Contact' => 'how to reach the data controller',
                    ])->map(fn (string $topic, string $title): array => ['title' => $title, 'body' => "[Content from the legal team: {$topic}.]"])->values()->all(),
                ]],
            ]],
        ];
    }
}
