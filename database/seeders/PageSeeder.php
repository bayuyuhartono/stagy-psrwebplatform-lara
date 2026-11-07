<?php

namespace Database\Seeders;

use App\Features\Pages\Enums\PageStatus;
use App\Features\Seo\Services\SitemapService;
use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Seed the standing pages every company profile site is expected to serve.
     *
     * Content is deliberately generic placeholder copy — it exists so the routes
     * resolve and the navigation has somewhere to point, not to ship final wording.
     */
    public function run(): void
    {
        foreach ($this->pages() as $page) {
            Page::query()->firstOrCreate(
                ['slug' => $page['slug']],
                [
                    'title' => $page['title'],
                    'status' => PageStatus::Published,
                    'blocks' => $page['blocks'],
                    'published_at' => now(),
                ],
            );
        }

        // DatabaseSeeder applies WithoutModelEvents, so SitemapCacheObserver never
        // fires here — invalidate explicitly or the sitemap serves stale XML.
        app(SitemapService::class)->invalidate();
    }

    /**
     * @return list<array{slug: string, title: string, blocks: list<array{type: string, data: array<string, mixed>}>}>
     */
    private function pages(): array
    {
        return [
            [
                'slug' => 'about',
                'title' => 'About',
                'blocks' => [
                    ['type' => 'rich_text', 'data' => [
                        'content' => '<p>We are a team of strategists, designers, and engineers building digital products for ambitious organisations. Replace this copy in the CMS under Pages → About.</p>',
                    ]],
                    ['type' => 'feature_grid', 'data' => ['items' => [
                        ['title' => 'Strategy', 'description' => 'Research and positioning that gives the work a clear direction.', 'icon' => null],
                        ['title' => 'Design', 'description' => 'Interfaces and brand systems built to last beyond a launch.', 'icon' => null],
                        ['title' => 'Engineering', 'description' => 'Accessible, performant builds on foundations you can maintain.', 'icon' => null],
                    ]]],
                    ['type' => 'call_to_action', 'data' => [
                        'heading' => 'Work with us',
                        'body' => 'Tell us what you are building and we will get back to you.',
                        'label' => 'Get in touch',
                        'url' => url('/contact'),
                    ]],
                ],
            ],
            [
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy',
                'blocks' => [
                    ['type' => 'rich_text', 'data' => [
                        'content' => '<p><strong>This is placeholder text and is not legal advice.</strong> Replace it with a policy reviewed by your legal counsel before launch.</p>'
                            .'<h2>Information we collect</h2><p>We collect the details you submit through our contact form, along with standard server and analytics data.</p>'
                            .'<h2>How we use it</h2><p>We use your information to respond to enquiries and to understand how the website is used.</p>'
                            .'<h2>Your rights</h2><p>You may request access to, correction of, or deletion of the personal data we hold about you.</p>'
                            .'<h2>Contact</h2><p>Questions about this policy can be sent through our contact page.</p>',
                    ]],
                ],
            ],
            [
                'slug' => 'terms-and-conditions',
                'title' => 'Terms & Conditions',
                'blocks' => [
                    ['type' => 'rich_text', 'data' => [
                        'content' => '<p><strong>This is placeholder text and is not legal advice.</strong> Replace it with terms reviewed by your legal counsel before launch.</p>'
                            .'<h2>Use of this website</h2><p>By accessing this website you agree to use it lawfully and not to disrupt its operation.</p>'
                            .'<h2>Intellectual property</h2><p>All content on this website remains the property of its respective owners.</p>'
                            .'<h2>Limitation of liability</h2><p>The website is provided as is, without warranties of any kind.</p>'
                            .'<h2>Changes</h2><p>We may update these terms from time to time. Continued use constitutes acceptance.</p>',
                    ]],
                ],
            ],
        ];
    }
}
