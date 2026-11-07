<?php

namespace Database\Seeders;

use App\Features\Posts\Enums\PostStatus;
use App\Features\Posts\Enums\PostType;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * The four blog categories and seven sample articles from the mockup, in
 * Indonesian. Only "Cara membuat buku tamu digital…" has a full body in the
 * design; the others carry their excerpt and a placeholder for the team.
 */
class StagyBlogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect(['panduan' => 'Panduan', 'tips-eo' => 'Tips EO', 'teknologi' => 'Teknologi', 'laporan' => 'Laporan'])
            ->map(fn (string $name, string $slug): Category => Category::query()->firstOrCreate(
                ['slug' => $slug, 'locale' => 'id'],
                [...Category::translationDefaults('id'), 'name' => $name],
            ));

        foreach ($this->articles() as [$date, $category, $title, $slug, $excerpt, $body, $featured]) {
            $post = Post::query()->firstOrCreate(
                ['slug' => $slug, 'locale' => 'id'],
                [
                    ...Post::translationDefaults('id'),
                    'title' => $title,
                    'type' => PostType::Blog,
                    'status' => PostStatus::Published,
                    'excerpt' => $excerpt,
                    'is_featured' => $featured,
                    'blocks' => [['type' => 'rich_text', 'data' => ['content' => $body]]],
                    'published_at' => Carbon::parse($date.' 09:00:00'),
                ],
            );

            $post->categories()->syncWithoutDetaching([$categories[$category]->getKey()]);
        }
    }

    /**
     * @return list<array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string, 6: bool}>
     */
    private function articles(): array
    {
        $placeholder = fn (string $excerpt): string => "{$excerpt}\n\n[Isi artikel lengkap dari tim Stagy.]";

        return [
            ['2026-11-03', 'panduan', 'Cara membuat buku tamu digital untuk event korporat', 'cara-membuat-buku-tamu-digital-untuk-event-korporat',
                'Dari daftar Excel klien sampai tamu check-in pakai QR, langkah demi langkah.', <<<'MARKDOWN'
Buku tamu kertas masih sering dipakai di event korporat. Masalahnya, antrean jadi panjang dan data tamu susah direkap untuk klien.

Buku tamu digital mengganti meja tulis dengan scan QR. Tamu datang, tunjukkan undangan di HP, dan data kehadirannya langsung tercatat.

## 1. Rapikan daftar tamu dari klien

Minta daftar tamu dalam satu file Excel. Pastikan ada kolom nama, nomor WhatsApp, dan level tamu seperti VIP atau Regular.

## 2. Kirim undangan dengan QR unik

Setiap tamu dapat QR sendiri lewat WhatsApp atau email. QR inilah yang nanti di-scan di pintu masuk.

## 3. Siapkan titik check-in di venue

Cukup HP atau tablet dengan kamera, atau scanner untuk antrean yang lebih cepat. Pisahkan jalur VIP kalau tamunya banyak.

- Satu titik scan untuk setiap [X] tamu
- Layar welcome untuk menyapa tamu VIP
- Meja bantuan untuk tamu yang lupa undangan

## 4. Kirim laporan ke klien

Setelah acara, rekap kehadiran bisa langsung diekspor. Klien dapat data siapa yang datang, jam berapa, dan dari level tamu apa.
MARKDOWN, true],
            ['2026-10-27', 'tips-eo', '7 penyebab antrean registrasi dan cara mengatasinya', '7-penyebab-antrean-registrasi-dan-cara-mengatasinya',
                'Antrean panjang di meja tamu biasanya berawal dari hal yang bisa dicegah.', $placeholder('Antrean panjang di meja tamu biasanya berawal dari hal yang bisa dicegah.'), false],
            ['2026-10-20', 'teknologi', 'QR code vs RFID: mana yang cocok untuk event Anda?', 'qr-code-vs-rfid-mana-yang-cocok-untuk-event-anda',
                'Perbandingan dua cara check-in berdasarkan jumlah tamu, venue, dan anggaran.', $placeholder('Perbandingan dua cara check-in berdasarkan jumlah tamu, venue, dan anggaran.'), false],
            ['2026-10-13', 'tips-eo', 'Checklist H-7 untuk tim registrasi sebelum hari-H', 'checklist-h-7-untuk-tim-registrasi-sebelum-hari-h',
                'Hal-hal yang wajib dicek seminggu sebelum acara agar antrean di pintu masuk tidak macet.', $placeholder('Hal-hal yang wajib dicek seminggu sebelum acara agar antrean di pintu masuk tidak macet.'), false],
            ['2026-10-06', 'panduan', 'Kirim undangan WhatsApp massal yang rapi dan personal', 'kirim-undangan-whatsapp-massal-yang-rapi-dan-personal',
                'Tips menyusun pesan undangan, waktu kirim, dan cara memantau RSVP.', $placeholder('Tips menyusun pesan undangan, waktu kirim, dan cara memantau RSVP.'), false],
            ['2026-09-29', 'laporan', 'Laporan kehadiran yang membuat klien puas', 'laporan-kehadiran-yang-membuat-klien-puas',
                'Data apa saja yang perlu ada di laporan pasca-event untuk klien korporat.', $placeholder('Data apa saja yang perlu ada di laporan pasca-event untuk klien korporat.'), false],
            ['2026-09-22', 'teknologi', 'Door prize digital: membuat momen undian lebih seru', 'door-prize-digital-membuat-momen-undian-lebih-seru',
                'Undian di layar besar yang adil, cepat, dan langsung memberi kabar ke pemenang.', $placeholder('Undian di layar besar yang adil, cepat, dan langsung memberi kabar ke pemenang.'), false],
        ];
    }
}
