<?php

namespace Database\Seeders;

use App\Models\PricingAddon;
use App\Models\PricingComparisonRow;
use App\Models\PricingPlan;
use Illuminate\Database\Seeder;

/**
 * Stagy's three plans, comparison table and add-ons in Indonesian, from the
 * mockup's pricing page. Skipped entirely once any Indonesian plan exists.
 *
 * The mockup's "Tim on-site tambahan: Add-on" comparison row is left out: it is
 * the same for every plan, so it would be listed under "included in every plan",
 * and the add-on table already covers it.
 */
class StagyPricingSeeder extends Seeder
{
    private const YES = true;

    private const NO = false;

    public function run(): void
    {
        if (PricingPlan::query()->where('locale', 'id')->exists()) {
            return;
        }

        $plans = collect([
            ['Basic', '200 tamu termasuk · scan QR', 4_000_000, 2_500, 'Tim on-site · 2 QR scanner', false, null, 'Pilih Basic', [
                'Sistem registrasi, QR & dashboard', 'Undangan WhatsApp untuk 200 tamu', '2 QR scanner', 'Tim on-site', 'Sertifikat PDF otomatis', 'Laporan & export Excel',
            ]],
            ['Professional', '200 tamu termasuk · tap ID card', 8_100_000, 17_500, 'Tim on-site · 3 alat tap ID card', true, 'Paling Populer', 'Pilih Professional', [
                'Semua fitur Basic, plus:', 'ID card + lanyard untuk tiap tamu', '3 alat tap kartu, tamu cukup tap ID card', 'Door prize di layar besar', 'QR scanner cadangan',
            ]],
            ['Enterprise', '200 tamu termasuk · gate otomatis', 11_600_000, 27_500, 'Tim on-site · gate otomatis', false, null, 'Konsultasi Enterprise', [
                'Semua fitur Professional, plus:', 'Gate otomatis, tamu lewat tanpa berhenti', 'ID card gate otomatis + lanyard + hardcase untuk tiap tamu', 'Alat tap kartu di meja bantuan',
            ]],
        ])->map(fn (array $plan, int $position): PricingPlan => PricingPlan::query()->create([
            ...PricingPlan::translationDefaults('id'),
            'name' => $plan[0], 'tagline' => $plan[1], 'price' => $plan[2], 'extra_guest_price' => $plan[3],
            'support_summary' => $plan[4], 'is_highlighted' => $plan[5], 'highlight_label' => $plan[6],
            'cta_label' => $plan[7], 'cta_url' => '/demo', 'features' => $plan[8], 'position' => $position,
        ]));

        $comparison = [
            'Tamu & harga' => [
                ['Tamu termasuk', '200', '200', '200'],
                ['Tambahan per tamu', 'Rp2.500', 'Rp17.500', 'Rp27.500'],
                ['Cara check-in', 'Scan QR', 'Tap ID card', 'Gate otomatis'],
            ],
            'Registrasi & undangan' => [
                ['Import data tamu dari Excel', self::YES, self::YES, self::YES],
                ['Link registrasi online + persetujuan', self::YES, self::YES, self::YES],
                ['Level tamu VVIP / VIP / Regular', self::YES, self::YES, self::YES],
                ['Undangan WhatsApp', '200 tamu', '200 tamu', '200 tamu'],
                ['RSVP + e-ticket QR', self::YES, self::YES, self::YES],
            ],
            'Check-in di venue' => [
                ['QR scanner', '2', '1 (cadangan)', '1 (cadangan)'],
                ['Alat tap kartu', self::NO, '3', self::NO],
                ['Gate otomatis (tamu lewat tanpa berhenti)', self::NO, self::NO, self::YES],
                ['ID card + lanyard', 'Add-on', 'ID card tap, tiap tamu', 'ID card gate + hardcase, tiap tamu'],
                ['Layar welcome', self::YES, self::YES, self::YES],
                ['Check-in per ruangan & sesi', self::YES, self::YES, self::YES],
            ],
            'Momen acara' => [
                ['Door prize di layar besar', 'Add-on', self::YES, self::YES],
                ['Notifikasi WhatsApp ke pemenang', 'Add-on', self::YES, self::YES],
                ['Sertifikat PDF otomatis', self::YES, self::YES, self::YES],
            ],
            'Laporan' => [
                ['Dashboard real-time', self::YES, self::YES, self::YES],
                ['Laporan harian & per sesi', self::YES, self::YES, self::YES],
                ['Export Excel', self::YES, self::YES, self::YES],
            ],
            'Tim & dukungan' => [
                ['Tim on-site saat hari-H', '1 tim', '1 tim', '1 tim'],
            ],
        ];

        $position = 0;

        foreach ($comparison as $group => $rows) {
            foreach ($rows as $row) {
                $label = array_shift($row);
                PricingComparisonRow::query()->create([
                    ...PricingComparisonRow::translationDefaults('id'),
                    'group_name' => $group,
                    'label' => $label,
                    'values' => $plans->pluck('id')->combine($row)->all(),
                    'position' => $position++,
                ]);
            }
        }

        $addons = [
            ['Tamu tambahan (undangan WhatsApp)', 2_500, '/ tamu'],
            ['ID card tap + lanyard', 15_000, '/ tamu'],
            ['ID card gate otomatis + lanyard + hardcase', 25_000, '/ tamu'],
            ['Alat tap kartu', 100_000, '/ unit'],
            ['Cetak sertifikat (Art Carton 260 gsm)', 8_000, '/ lembar'],
            ['Door prize (untuk Basic)', 700_000, '/ event'],
            ['QR scanner tambahan', 100_000, '/ unit'],
            ['Laptop titik check-in', 150_000, '/ unit'],
            ['Alat tap kartu (gate otomatis)', 200_000, '/ unit'],
            ['Gate otomatis tambahan', 1_000_000, '/ unit'],
            ['Tim on-site tambahan', 500_000, '/ tim'],
        ];

        foreach ($addons as $position => [$name, $price, $unit]) {
            PricingAddon::query()->create([...PricingAddon::translationDefaults('id'), 'name' => $name, 'price' => $price, 'unit' => $unit, 'position' => $position]);
        }
    }
}
