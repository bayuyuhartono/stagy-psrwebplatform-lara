<?php

namespace Database\Seeders;

use App\Features\Pages\Enums\PageStatus;
use App\Features\Seo\Services\SeoService;
use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * The Stagy landing pages in Indonesian, as drawn in the "Stagy Landing Mockup"
 * design. Wording marked [Konfirmasi: …] or [Jawaban dari tim: …] is a placeholder
 * the team still has to supply, left visible so it is easy to find in the CMS.
 *
 * Existing pages are never overwritten, so the seeder is safe to run again.
 */
class StagyPageSeeder extends Seeder
{
    public function run(SeoService $seo): void
    {
        $metadata = $this->seo();

        foreach ($this->pages() as $slug => [$title, $blocks]) {
            $page = Page::query()->firstOrCreate(
                ['slug' => $slug, 'locale' => 'id'],
                [
                    ...Page::translationDefaults('id'),
                    'title' => $title,
                    'status' => PageStatus::Published,
                    'blocks' => $blocks,
                    'published_at' => now(),
                ],
            );

            // Like the page itself, SEO the team already edited in the CMS is left alone.
            if (isset($metadata[$slug]) && ! $page->seo()->exists()) {
                $seo->updateForContent($page, [...$metadata[$slug], 'robots_index' => true, 'robots_follow' => true, 'twitter_card' => 'summary_large_image']);
            }
        }
    }

    /**
     * A distinct search title and description per page, so results do not all
     * repeat the site-wide default. A null title falls back to "<page title> · Stagy".
     *
     * @return array<string, array{meta_title: ?string, meta_description: string, focus_keyword: ?string}>
     */
    private function seo(): array
    {
        return [
            'home' => ['meta_title' => 'Stagy · Registrasi & Check-in Event untuk Event Organizer', 'focus_keyword' => 'registrasi event', 'meta_description' => 'Import data tamu, kirim undangan QR lewat WhatsApp & email, lalu check-in tamu di venue dalam satu sistem. Laporan kehadiran siap dikirim ke klien.'],
            'fitur' => ['meta_title' => 'Fitur Registrasi, Undangan QR & Check-in Event · Stagy', 'focus_keyword' => 'check-in event', 'meta_description' => 'Registrasi online, undangan WhatsApp & email dengan QR, scan QR atau RFID di venue, door prize, sertifikat, dan laporan kehadiran real-time.'],
            'cara-kerja' => ['meta_title' => 'Cara Kerja Stagy: Dari Data Tamu sampai Laporan', 'focus_keyword' => 'cara kerja', 'meta_description' => 'Empat langkah bersama Stagy: kirim data tamu, undangan terkirim, check-in di venue, dan laporan siap. Tim kami mendampingi Anda sampai hari-H.'],
            'harga' => ['meta_title' => 'Harga Paket Registrasi & Check-in Event · Stagy', 'focus_keyword' => 'harga', 'meta_description' => 'Paket registrasi & check-in event dengan harga per event, tanpa langganan. Bandingkan fitur tiap paket dan add-on yang bisa Anda tambahkan.'],
            'faq' => ['meta_title' => 'FAQ Registrasi & Check-in Event · Stagy', 'focus_keyword' => 'faq', 'meta_description' => 'Jawaban untuk pertanyaan umum seputar registrasi tamu, undangan QR, check-in di venue, keamanan data, dan paket harga Stagy untuk Event Organizer.'],
            'demo' => ['meta_title' => 'Minta Demo Sistem Registrasi Event · Stagy', 'focus_keyword' => 'demo', 'meta_description' => 'Jadwalkan demo Stagy untuk event klien Anda. Ceritakan perkiraan jumlah tamu dan tanggal acara, lalu tim kami akan menghubungi Anda.'],
            'syarat-ketentuan' => ['meta_title' => null, 'focus_keyword' => null, 'meta_description' => 'Syarat dan ketentuan penggunaan layanan registrasi dan check-in event Stagy bagi Event Organizer dan klien mereka.'],
            'kebijakan-privasi' => ['meta_title' => null, 'focus_keyword' => null, 'meta_description' => 'Bagaimana Stagy mengumpulkan, memakai, menyimpan, dan melindungi data tamu serta data klien Event Organizer yang memakai layanan kami.'],
            'blog' => ['meta_title' => 'Blog Stagy: Tips Registrasi & Check-in Event', 'focus_keyword' => 'check-in event', 'meta_description' => 'Tips dan panduan seputar registrasi tamu, undangan digital, dan check-in event untuk Event Organizer, dari tim Stagy.'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: list<array{type: string, data: array<string, mixed>}>}>
     */
    private function pages(): array
    {
        return [
            'home' => ['Beranda', [
                ['type' => 'hero', 'data' => [
                    'badge' => 'Registrasi & check-in untuk Event Organizer',
                    'heading' => 'Registrasi & check-in event klien Anda, *beres* dalam satu sistem.',
                    'subheading' => 'Import tamu, kirim undangan QR lewat WhatsApp & email, lalu check-in di venue dengan cepat. Laporan kehadiran siap dikirim ke klien begitu acara selesai.',
                    'primary_action' => ['label' => 'Minta Demo', 'url' => '/demo'],
                    'secondary_action' => ['label' => 'Lihat Paket', 'url' => '/harga'],
                    'trust_points' => ['Data tamu terlindungi', 'Tim on-site saat hari-H', 'Harga per event, tanpa langganan'],
                    'visual' => 'ticket',
                    'ticket_label' => 'E-ticket · VIP',
                    'ticket_event' => '[Nama Event] Annual Gathering 2026',
                    'ticket_guest' => 'Budi Santoso',
                    'ticket_detail' => 'PT Contoh · Meja 12',
                    'ticket_time' => '12 Des · 18.00',
                    'ticket_gate' => 'Gate A',
                ]],
                ['type' => 'feature_cards', 'data' => [
                    'eyebrow' => 'Fitur',
                    'heading' => 'Dari undangan sampai laporan, *semua* di satu tempat.',
                    'link' => ['label' => 'Lihat semua fitur', 'url' => '/fitur'],
                    'items' => [
                        ['title' => 'Sebelum event', 'description' => 'Import Excel, registrasi online, undangan WhatsApp & email dengan QR.', 'url' => '/fitur#sebelum'],
                        ['title' => 'Di venue', 'description' => 'Scan QR, tap ID card, atau gate otomatis. Layar welcome dan check-in per sesi.', 'url' => '/fitur#venue'],
                        ['title' => 'Momen acara', 'description' => 'Door prize di layar besar dan sertifikat PDF otomatis.', 'url' => '/fitur#highlight'],
                        ['title' => 'Sesudah event', 'description' => 'Dashboard real-time dan laporan yang siap dikirim ke klien.', 'url' => '/fitur#sesudah'],
                    ],
                ]],
                ['type' => 'steps', 'data' => [
                    'eyebrow' => 'Cara kerja',
                    'heading' => 'Empat langkah, dari data sampai *laporan*.',
                    'link' => ['label' => 'Lihat detail cara kerja', 'url' => '/cara-kerja'],
                    'variant' => 'compact',
                    'items' => [
                        ['title' => 'Kirim data tamu (Excel) atau buka link registrasi'],
                        ['title' => 'Undangan + QR terkirim ke WA/email tamu'],
                        ['title' => 'Tamu check-in di venue (QR atau RFID)'],
                        ['title' => 'Laporan kehadiran siap dikirim ke klien'],
                    ],
                ]],
                ['type' => 'pricing_cards', 'data' => [
                    'eyebrow' => 'Paket & harga',
                    'heading' => 'Harga jelas per event. *Tanpa* langganan.',
                    'link' => ['label' => 'Bandingkan paket lengkap', 'url' => '/harga'],
                    'variant' => 'summary',
                ]],
                ['type' => 'call_to_action', 'data' => [
                    'heading' => 'Siap membuat event klien Anda lebih rapi?',
                    'body' => 'Ceritakan acaranya, tim kami akan menghubungi Anda melalui WhatsApp pada jam kerja.',
                    'label' => 'Minta Demo',
                    'url' => '/demo',
                    'secondary_action' => ['label' => 'Chat WhatsApp', 'url' => 'whatsapp'],
                ]],
            ]],

            'fitur' => ['Fitur', [
                ['type' => 'page_header', 'data' => [
                    'eyebrow' => 'Fitur',
                    'heading' => 'Semua yang dibutuhkan EO, dari undangan sampai *laporan*.',
                    'intro' => 'Satu sistem untuk seluruh alur tamu di event klien Anda. Pilih tahap untuk langsung lompat ke fiturnya.',
                    'links' => [
                        ['label' => '01 Sebelum event', 'url' => '#sebelum'],
                        ['label' => '02 Di venue', 'url' => '#venue'],
                        ['label' => '03 Momen acara', 'url' => '#highlight'],
                        ['label' => '04 Sesudah event', 'url' => '#sesudah'],
                    ],
                ]],
                ['type' => 'feature_group', 'data' => [
                    'anchor' => 'sebelum', 'label' => 'Sebelum event', 'heading' => 'Tamu terdata, undangan terkirim',
                    'description' => 'Siapkan daftar tamu klien tanpa Excel bolak-balik.',
                    'points' => ['Import data tamu dari Excel', 'Link registrasi online + persetujuan', 'Level tamu VVIP / VIP / Regular', 'Kirim undangan WhatsApp & email massal', 'RSVP + e-ticket QR otomatis'],
                    'media_placeholder' => 'Screenshot: daftar tamu & kirim undangan',
                ]],
                ['type' => 'feature_group', 'data' => [
                    'anchor' => 'venue', 'label' => 'Di venue', 'heading' => 'Check-in cepat, antrean lebih pendek',
                    'description' => 'Tamu cukup menunjukkan QR, tap ID card, atau lewat gate otomatis.',
                    'points' => ['Scan QR pakai kamera HP atau scanner', 'Tap ID card di alat tap kartu', 'Gate otomatis, tamu lewat tanpa berhenti', 'Layar welcome menyapa tamu VIP', 'Check-in per ruangan & sesi, tap masuk/keluar', 'Cetak ID card & serah terima merchandise'],
                    'media_placeholder' => 'Screenshot: scan station & layar welcome',
                ]],
                ['type' => 'feature_group', 'data' => [
                    'anchor' => 'highlight', 'label' => 'Momen acara', 'heading' => 'Momen yang diingat tamu',
                    'description' => 'Fitur hiburan yang membuat acara klien lebih meriah.',
                    'points' => ['Door prize di layar besar', 'Notifikasi WhatsApp ke pemenang', 'Sertifikat PDF otomatis'],
                    'media_placeholder' => 'Screenshot: display door prize di layar besar',
                ]],
                ['type' => 'feature_group', 'data' => [
                    'anchor' => 'sesudah', 'label' => 'Sesudah event', 'heading' => 'Laporan siap dikirim ke klien',
                    'description' => 'Data kehadiran langsung rapi begitu acara selesai.',
                    'points' => ['Dashboard real-time', 'Laporan harian & per sesi', 'Export Excel sekali klik'],
                    'media_placeholder' => 'Screenshot: dashboard kehadiran real-time',
                ]],
                ['type' => 'call_to_action', 'data' => [
                    'heading' => 'Mau lihat fiturnya jalan langsung?',
                    'label' => 'Minta Demo',
                    'url' => '/demo',
                    'secondary_action' => ['label' => 'Lihat Harga', 'url' => '/harga'],
                ]],
            ]],

            'cara-kerja' => ['Cara Kerja', [
                ['type' => 'page_header', 'data' => [
                    'eyebrow' => 'Cara kerja',
                    'heading' => 'Empat langkah, dari data tamu sampai *laporan*.',
                    'intro' => 'Anda fokus ke klien dan acaranya. Urusan daftar tamu, undangan, dan check-in kami yang urus.',
                ]],
                ['type' => 'steps', 'data' => [
                    'variant' => 'detailed',
                    'items' => [
                        ['title' => 'Kirim data tamu', 'you' => 'Kirim Excel daftar tamu klien, atau bagikan link registrasi online.', 'us' => 'Rapikan data dan format nomor WhatsApp, atur level tamu & persetujuan.'],
                        ['title' => 'Undangan terkirim', 'you' => 'Setujui isi undangan untuk klien.', 'us' => 'Kirim undangan + QR lewat WhatsApp & email, pantau RSVP.'],
                        ['title' => 'Check-in di venue', 'you' => 'Fokus ke jalannya acara.', 'us' => 'Scan QR, tap ID card, atau gate otomatis. Layar welcome dan check-in per sesi.'],
                        ['title' => 'Laporan siap', 'you' => 'Teruskan laporan ke klien.', 'us' => 'Dashboard real-time, laporan harian & per sesi, export Excel.'],
                    ],
                ]],
                ['type' => 'support_tiers', 'data' => [
                    'eyebrow' => 'Hari-H',
                    'heading' => 'Pendampingan di venue, sesuai paket.',
                    'link' => ['label' => 'Bandingkan paket', 'url' => '/harga'],
                ]],
                ['type' => 'call_to_action', 'data' => [
                    'heading' => 'Punya event klien dalam waktu dekat?',
                    'label' => 'Minta Demo',
                    'url' => '/demo',
                    'secondary_action' => ['label' => 'Chat WhatsApp', 'url' => 'whatsapp'],
                ]],
            ]],

            'harga' => ['Harga', [
                ['type' => 'page_header', 'data' => [
                    'eyebrow' => 'Paket & harga',
                    'heading' => 'Harga jelas per event. *Tanpa* langganan.',
                    'intro' => 'Semua paket sudah termasuk 200 tamu, sistem, undangan WhatsApp, perangkat check-in, dan tim on-site. Tamu lebih banyak? Tinggal tambah per tamu.',
                ]],
                ['type' => 'pricing_cards', 'data' => [
                    'variant' => 'full',
                    'note' => 'Harga per event. [Konfirmasi: durasi yang termasuk & sudah/belum termasuk PPN]',
                ]],
                ['type' => 'pricing_table', 'data' => [
                    'eyebrow' => 'Perbandingan',
                    'heading' => 'Bandingkan isi tiap paket.',
                    'note' => 'Item bertanda "Add-on" bisa ditambahkan dengan harga di tabel add-on di bawah.',
                    'show_included_in_all' => true,
                ]],
                ['type' => 'pricing_addons', 'data' => [
                    'eyebrow' => 'Add-on',
                    'heading' => 'Tambah sesuai kebutuhan klien.',
                    'intro' => 'Bisa ditambahkan ke paket mana pun, dengan harga satuan yang sama. Ada pertanyaan soal pembayaran? Cek FAQ.',
                ]],
            ]],

            'faq' => ['FAQ', [
                ['type' => 'faq', 'data' => [
                    'eyebrow' => 'FAQ',
                    'heading' => 'Pertanyaan yang sering *ditanyakan* EO.',
                    'intro' => 'Belum menemukan jawabannya? Tim kami siap membantu melalui WhatsApp.',
                    'show_whatsapp' => true,
                    'items' => [
                        ['question' => 'Bisakah check-in memakai HP?', 'answer' => 'Bisa. Scan QR bisa memakai kamera HP atau scanner. Paket Professional memakai ID card yang cukup di-tap ke alat tap kartu, dan Enterprise memakai gate otomatis sehingga tamu lewat tanpa berhenti.'],
                        ['question' => 'Bisa untuk event multi-hari atau banyak ruangan?', 'answer' => 'Bisa. Check-in bisa dipisah per ruangan dan per sesi. [Konfirmasi: biaya untuk hari tambahan]'],
                        ['question' => 'Data tamu klien aman?', 'answer' => 'Data tamu hanya bisa dilihat admin yang berwenang. [Konfirmasi: kepatuhan UU PDP dan detail penyimpanan data]'],
                        ['question' => 'Perlu internet di venue?', 'answer' => '[Jawaban dari tim: kebutuhan koneksi di venue]'],
                        ['question' => 'Berapa lama setup sebelum event?', 'answer' => '[Jawaban dari tim: estimasi waktu setup]'],
                        ['question' => 'Bisa custom desain undangan & e-ticket?', 'answer' => '[Jawaban dari tim: opsi custom desain]'],
                        ['question' => 'Bisa pakai branding EO atau klien?', 'answer' => '[Jawaban dari tim: opsi branding EO/klien di undangan, e-ticket, dan layar welcome]'],
                        ['question' => 'Bagaimana sistem pembayarannya?', 'answer' => '[Jawaban dari tim: termin & metode pembayaran]'],
                    ],
                ]],
            ]],

            'demo' => ['Minta Demo', [
                ['type' => 'demo_form', 'data' => [
                    'eyebrow' => 'Minta demo',
                    'heading' => 'Ceritakan event klien Anda, kami *siapkan* demonya.',
                    'intro' => 'Setelah form dikirim, ini yang terjadi:',
                    'next_steps' => [
                        'Tim kami akan menghubungi Anda melalui WhatsApp pada jam kerja.',
                        'Demo singkat sesuai jenis dan skala event klien.',
                        'Penawaran harga siap Anda teruskan ke klien.',
                    ],
                    'chat_prompt' => 'Lebih cepat lewat chat?',
                    'privacy_url' => '/kebijakan-privasi',
                ]],
            ]],

            'syarat-ketentuan' => ['Syarat & Ketentuan', [
                ['type' => 'legal_sections', 'data' => [
                    'eyebrow' => 'Legal',
                    'heading' => 'Syarat & Ketentuan',
                    'updated' => 'Terakhir diperbarui: [tanggal]',
                    'intro' => '[Paragraf pembuka dari tim legal: siapa pihak dalam perjanjian ini dan sejak kapan berlaku saat EO menggunakan layanan Stagy.]',
                    'sections' => collect([
                        'Definisi' => 'Istilah seperti Layanan, EO, Klien, Tamu, dan Event',
                        'Ruang lingkup layanan' => 'apa saja yang termasuk di tiap paket dan add-on',
                        'Pemesanan & pembayaran' => 'termin, metode, dan batas waktu pembayaran',
                        'Pembatalan & perubahan jadwal' => 'ketentuan refund dan reschedule event',
                        'Kewajiban EO' => 'kelengkapan data tamu, persetujuan klien, akses venue',
                        'Data tamu & privasi' => 'kepemilikan data, retensi, dan rujukan ke Kebijakan Privasi',
                        'Batasan tanggung jawab' => 'gangguan jaringan, perangkat venue, keadaan kahar',
                        'Hukum yang berlaku' => 'yurisdiksi dan penyelesaian sengketa',
                        'Perubahan syarat' => 'cara Stagy memberi tahu perubahan',
                    ])->map(fn (string $topic, string $title): array => ['title' => $title, 'body' => "[Isi dari tim legal: {$topic}.]"])->values()->all(),
                    'closing' => 'Ada pertanyaan soal syarat ini? Hubungi kami lewat WhatsApp atau email. Baca juga Kebijakan Privasi.',
                ]],
            ]],

            'kebijakan-privasi' => ['Kebijakan Privasi', [
                ['type' => 'legal_sections', 'data' => [
                    'eyebrow' => 'Legal',
                    'heading' => 'Kebijakan Privasi',
                    'updated' => 'Terakhir diperbarui: [tanggal]',
                    'intro' => '[Paragraf pembuka dari tim legal tentang komitmen Stagy terhadap data pribadi EO, klien, dan tamu.]',
                    'sections' => collect([
                        'Data yang kami kumpulkan' => 'data EO, data tamu, dan data teknis',
                        'Cara kami menggunakan data' => 'tujuan pemrosesan data',
                        'Penyimpanan & keamanan' => 'lokasi, masa simpan, dan pengamanan data',
                        'Berbagi data dengan pihak lain' => 'penyedia WhatsApp, email, dan hosting',
                        'Hak Anda' => 'akses, koreksi, dan penghapusan data sesuai UU PDP',
                        'Kontak' => 'cara menghubungi pengelola data',
                    ])->map(fn (string $topic, string $title): array => ['title' => $title, 'body' => "[Isi dari tim legal: {$topic}.]"])->values()->all(),
                ]],
            ]],

            'blog' => ['Blog', [
                ['type' => 'page_header', 'data' => [
                    'eyebrow' => 'Blog',
                    'heading' => 'Tips & panduan agar event klien makin *rapi*.',
                    'intro' => 'Seputar registrasi, check-in, dan manajemen tamu untuk Event Organizer.',
                ]],
                ['type' => 'call_to_action', 'data' => [
                    'heading' => 'Siap mencobanya di event berikutnya?',
                    'label' => 'Minta Demo',
                    'url' => '/demo',
                ]],
            ]],
        ];
    }
}
