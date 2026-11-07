<?php

namespace Database\Seeders;

use App\Features\WebsiteSettings\Enums\WebsiteSettingGroup;
use App\Models\WebsiteSetting;
use Illuminate\Database\Seeder;

/**
 * Stagy's website settings. Only blank values are filled, so anything an
 * editor already set survives a re-run.
 *
 * WhatsApp number and email are deliberately left empty: the mockup only has
 * placeholders for them, and a placeholder would produce a broken wa.me link.
 * The site hides WhatsApp buttons until a number is entered in the CMS.
 */
class StagySettingsSeeder extends Seeder
{
    public function run(): void
    {
        $this->fill(WebsiteSettingGroup::General, [
            'site_name' => 'Stagy',
            'tagline' => [
                'id' => 'Sistem registrasi & check-in untuk Event Organizer. Dari undangan sampai laporan, beres dalam satu sistem.',
                'en' => 'Registration & check-in system for event organisers. From invitations to reports, handled in one system.',
            ],
            'locale' => 'id_ID',
            'timezone' => 'Asia/Jakarta',
        ]);

        $this->fill(WebsiteSettingGroup::Contact, [
            'address' => "[Nama gedung / jalan]\n[Kecamatan], [Kota] [Kode pos]",
            'business_hours' => ['id' => 'Senin–Jumat, [09.00–17.00] WIB', 'en' => 'Monday–Friday, [9am–5pm] WIB'],
        ]);

        $this->fill(WebsiteSettingGroup::Seo, [
            'default_title' => ['id' => 'Stagy · Registrasi & check-in event', 'en' => 'Stagy · Event registration & check-in'],
            'default_description' => [
                'id' => 'Import tamu, kirim undangan QR lewat WhatsApp & email, check-in cepat di venue, dan laporan kehadiran untuk klien EO.',
                'en' => 'Import guests, send QR invitations over WhatsApp & email, check in fast at the venue, and report attendance to your clients.',
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function fill(WebsiteSettingGroup $group, array $values): void
    {
        $setting = WebsiteSetting::query()->firstOrCreate(['group' => $group], ['settings' => $group->defaults(), 'is_public' => true]);
        $settings = $setting->settings ?? [];

        foreach ($values as $key => $value) {
            if (blank($settings[$key] ?? null) || $settings[$key] === ($group->defaults()[$key] ?? null)) {
                $settings[$key] = $value;
            }
        }

        $setting->update(['settings' => $settings]);
    }
}
