# Stagy Site — Workflow & Status

Implementasi landing site **Stagy** (sistem registrasi & check-in event untuk EO) di atas platform CMS ini.

- **Sumber desain:** canvas "Stagy Landing Mockup" — https://claude.ai/artifact/TXB3RUCLRp8oZhCqXUcZ7V
- **Dimulai:** 2026-11-07
- **Legend:** `[x]` selesai · `[~]` sebagian · `[ ]` belum · ⏸ menunggu keputusan

**Fase aktif:** Semua fase selesai — tinggal review visual di browser (branch `feat/stagy-site`)

| Fase | Judul | Status | Commit |
| --- | --- | --- | --- |
| 1 | Fondasi i18n | `[x]` | `925485b` |
| 2 | Theme `stagy` | `[x]` | _user_ |
| 3 | Block type baru | `[x]` | _user_ |
| 4 | Modul Pricing | `[x]` | _user_ |
| 5 | Form Minta Demo | `[x]` | _user_ |
| 6 | Blog | `[x]` | _user_ |
| 7 | Website Settings | `[x]` | _user_ |
| 8 | Seeder konten mockup | `[x]` | _user_ |

---

## Keputusan (disepakati 2026-11-07)

| # | Topik | Keputusan |
| --- | --- | --- |
| D1 | Theme | Theme baru `resources/themes/stagy`; theme `default` tidak diubah |
| D2 | Kendali konten | Semua dari CMS: block type baru + modul Pricing |
| D3 | Fitur & Cara Kerja | Page + block (bukan modul Services) |
| D4 | Form demo | Kolom terstruktur baru di `contact_messages` |
| D5 | Bahasa | ID + EN, default `id` |
| D6 | Model i18n | Satu baris per bahasa: kolom `locale` + `translation_group_id`; tanpa package baru |
| D7 | URL | `id` di root (`/harga`), `en` di `/en/...` dengan slug Inggris (`/en/pricing`); `hreflang` di head |
| D8 | Font | Horizon (lisensi web aman); ukuran teks dinaikkan dari mockup (body ≥ 15px, label ≥ 12px) |
| D9 | Konten placeholder | Diisi dari CMS (WA, email, alamat, FAQ, legal, screenshot) |
| D10 | Data awal | Seeder berisi konten mockup versi ID |
| D11 | Modul tak terpakai | Services, Portfolio, News, `/contact` dimatikan via feature flag, tidak dihapus |

---

## Workflow per fase

1. Update tabel status di atas: fase → `[~]`, isi "Fase aktif".
2. Baca `.ai/rules/index.md` + rule yang cocok dengan path yang disentuh; `search-docs` untuk API Laravel yang dipakai.
3. Implementasi mengikuti layering `Controller → Action → Service → Repository → Model`, Form Request, Policy, API Resource, dan feature flag.
4. Tulis/ubah test Pest untuk perilaku baru; jalankan `php artisan test --compact` (suite penuh harus hijau).
5. `vendor/bin/pint --dirty --format agent`; `npm run build` bila ada perubahan frontend.
6. Catat keputusan baru yang berlaku jangka panjang via Boost `record-rule`.
7. Centang item di bagian fase, tulis catatan singkat bila ada penyimpangan dari rencana.
8. **Tidak commit/push** — perubahan ditinggal di working tree; user yang commit & push. Sertakan usulan pesan commit `feat(stagy): fase N — <judul>`, lalu **review bersama user** sebelum lanjut ke fase berikutnya.

---

## Fase 1 — Fondasi i18n

- [x] Config locale: `app.locale = id`, daftar locale yang didukung (`id`, `en`) di `config/localization.php`
- [x] Migrasi: `locale` + `translation_group_id` di `pages`, `posts`, `categories`, `menus` (data lama → `id`, grup = dirinya sendiri; diuji di SQLite & MySQL, termasuk rollback)
- [x] Unique constraint: `(locale, slug)` / `(locale, location)` dan `(translation_group_id, locale)`
- [x] Trait `HasTranslations` (scope `forLocale`/`forCurrentLocale`, relasi `translations`, isi otomatis locale & grup)
- [x] Route publik: `id` di root, `en` di prefix `/en` (nama route `en.*`); middleware `locale`
- [x] Repository & controller publik memfilter berdasarkan locale aktif (halaman, post, menu, kategori)
- [x] Public API menerima parameter `locale` (default `id`, locale tak dikenal → 422)
- [x] Admin API: `locale` + `translation_of` saat membuat, locale tidak bisa diubah, filter `locale` di index, `translations` di detail
- [x] CMS: filter & badge bahasa di daftar halaman/post; panel bahasa + "Add translation" di editor (isi disalin dari sumber); field bahasa di menu & kategori
- [x] `hreflang` + `x-default` + `<html lang>` di head; sitemap memakai prefix locale
- [x] Helper `localized_route()` + `AlternateUrlResolver` untuk switcher "ID · EN" (fallback ke beranda locale tujuan)
- [x] Test: 21 test di `tests/Feature/LocalizationTest.php`

**Dipindah dari Fase 1:**
- Teks website settings per locale → Fase 7 (field-nya baru ditentukan di sana)
- String UI theme lewat `lang/id` & `lang/en` → Fase 2 (string-nya milik theme `stagy`)

**Catatan:**
- Kategori per bahasa; tag dipakai bersama semua bahasa.
- Theme `default` ikut disesuaikan seperlunya (`route()` → `localized_route()`, `hreflang`) supaya link & canonical benar di `/en`.
- UI CMS belum dicoba langsung di browser; perilaku API-nya tercakup test.

## Fase 2 — Theme `stagy`

- [x] `resources/themes/stagy/theme.json`: settings (`demo_page_slug`, tombol WA, "Powered by") + `menu_locations` (`header`, `footer-product`, `footer-help`)
- [x] String UI theme per bahasa di `resources/themes/stagy/lang/{id,en}/site.php`, dipanggil lewat `__('theme::site.*')` (dipindah dari Fase 1)
- [x] Token warna: `public-*` di-override di `stagy.css`; token pendukung baru (`public-muted`, `-line`, `-mist`, `-mint`, `-sage`, `-deep`, `-on-ink`, `font-public`) di `resources/css/theme.css`
- [x] Font Horizon (self-hosted via Vite) + skala tipografi dinaikkan (body 15px, label ≥ 12px)
- [x] Aset brand: logo + favicon 16/32/512 + apple-touch-icon 180 di `resources/themes/stagy/assets/`
- [x] Layout + navbar desktop (link aktif `aria-current`) + navbar HP (toggle, ikon buka/tutup) + skip link
- [x] Footer: brand, tagline, sosial, kolom menu Produk & Bantuan, kontak (WA, email, alamat, jam operasional), "Powered by Pesoros"
- [x] Tombol WhatsApp melayang (muncul hanya jika nomor diisi)
- [x] Switcher bahasa "ID · EN" (desktop & HP)
- [x] Halaman 404 "Tiket tidak ditemukan" (ID & EN)
- [x] Theme aktif = `stagy` di `.env.example`; test tetap memakai `default` (`phpunit.xml`)
- [x] Test: 11 test di `tests/Feature/StagyThemeTest.php`

**Dipindah dari Fase 2:**
- Mematikan feature flag Services/Portfolio/Contact → Fase 8 (flag adalah data, di-seed). News tidak punya flag sendiri; theme Stagy tidak menautkannya.

**Catatan:**
- **Bug lama diperbaiki:** halaman error theme (`errors/404`) tidak pernah dipakai karena Laravel mencari view error dari `config('view.paths')`. `ThemeManager::boot()` sekarang menambahkan path theme ke sana.
- Head SEO dipindah ke partial bersama `public/partials/seo-head.blade.php` (di theme `default`, dipakai kedua theme).
- `MenuItemResource.url` dan link menu di theme sekarang memakai `PublicUrlGenerator::forMenuItem()`, jadi halaman EN otomatis ber-prefix `/en`.
- Kunci settings baru yang sudah dibaca theme: `contact.whatsapp`, `contact.business_hours`, `social.tiktok_url`. Field CMS-nya dibuat di Fase 7.
- Konten halaman masih dirender oleh block bawaan theme `default` (warna sudah Stagy) sampai block Stagy dibuat di Fase 3.
- Belum dicek visual di browser.

## Fase 3 — Block type baru

Didaftarkan di `config/content_blocks.php` (field + rules), di-render oleh theme `stagy` (`views/public/blocks/*`), bisa diedit di block editor CMS.

- [x] Form CMS dibangkitkan dari skema untuk block bertanda `'editor' => 'schema'` (string, text, link, media, select, boolean, action, list, repeater)
- [x] Aturan validasi `content_link`: URL http(s), path `/…`, `#anchor`, `mailto:`/`tel:`, dan kata kunci `whatsapp` → nomor WA dari settings (tombol disembunyikan bila kosong)
- [x] Penanda highlight di judul: `*kata*` → hijau (di-escape lebih dulu)
- [x] `hero` diperluas: badge, tombol kedua, trust points, visual e-ticket (teks tiket bisa diedit) / gambar / tanpa visual
- [x] `page_header` — eyebrow, judul, intro, chip lompat ke `#anchor`
- [x] `feature_cards` — kartu bernomor otomatis dengan link + link "lihat semua"
- [x] `steps` — layout ringkas (baris) atau detail (kolom "Anda / Stagy", kartu terakhir navy)
- [x] `feature_group` — label tahap, checklist, screenshot/placeholder; selang-seling kiri/kanan & latar otomatis
- [x] `faq` — accordion `<details>` (item pertama terbuka), tombol WhatsApp opsional
- [x] `legal_sections` — bagian bernomor + daftar isi otomatis, tanggal pembaruan, catatan penutup
- [x] `call_to_action` diperluas: tombol kedua
- [x] Versi Stagy untuk block lama: `rich_text`, `image`, `feature_grid`
- [x] Test: 23 test di `tests/Feature/StagyBlocksTest.php`

**Dipindah dari Fase 3:**
- `support_tiers`, `pricing_cards`, `pricing_table`, `pricing_addons` → Fase 4 (datanya dari modul Pricing)
- `demo_form` → Fase 5

**Catatan:**
- Block baru hanya punya tampilan di theme `stagy`; di theme `default` block tersebut tidak dirender.
- Form CMS yang dibangkitkan dari skema belum dicoba di browser (build JS lolos).

## Fase 4 — Modul Pricing

- [x] Model + migrasi per locale (`HasTranslations`, soft delete):
  - `pricing_plans`: nama, tagline, harga mulai, harga per tamu tambahan, daftar fitur, ringkasan pendampingan hari-H, highlight + label, tombol (label + link), urutan
  - `pricing_comparison_rows`: grup, label, nilai per paket (`true` ✓ / `false` – / teks pendek), urutan
  - `pricing_addons`: nama, harga, satuan, urutan
- [x] Layering: `PricingRepository`, `PricingService` (satu service untuk ketiganya), 4 Action, Policy (`pricing.*`), audit log (`pricing-plan.created`, dst.)
- [x] Feature flag `pricing`; permission `pricing.view/create/update/delete/restore` (Editor: penuh, Sales/Support: lihat)
- [x] Admin API `api/v1/admin/pricing/{plans,comparison-rows,addons}` (+ restore), daftar per bahasa
- [x] Public API `api/v1/public/pricing?locale=` → paket, tabel perbandingan, "termasuk di semua paket", add-on
- [x] `PricingCatalog`: baris yang nilainya sama untuk semua paket dipindah ke "termasuk di semua paket"; baris yang tidak dimiliki paket mana pun dibuang
- [x] `PriceFormatter`: `Rp4 juta`, `Rp8,1 juta`, `Rp2.500` / `IDR 8.1 million`
- [x] Block `pricing_cards` (lengkap / ringkas), `pricing_table`, `pricing_addons`, `support_tiers` (dipindah dari Fase 3)
- [x] CMS: menu **Pricing** — pilih bahasa, kelola Paket, Tabel perbandingan (✓ / – / teks per paket), Add-on
- [x] Test: 17 test di `tests/Feature/PricingTest.php`

**Catatan:**
- Nilai perbandingan disimpan per id paket. Menghapus paket membuat nilainya diabaikan (bukan error).
- Baris yang sama di semua paket dengan nilai teks tampil sebagai "Label: nilai" (mis. "Undangan WhatsApp: 200 tamu"). Baris "Tim on-site tambahan: Add-on" dari mockup sebaiknya tidak di-seed karena sudah ada di tabel add-on.
- Halaman CMS Pricing belum dicoba di browser (build JS lolos).

## Fase 5 — Form Minta Demo

- [x] Block `demo_form` (dipindah dari Fase 3): judul, intro, langkah "setelah form dikirim", ajakan chat WhatsApp, pesan sukses, link kebijakan privasi; pilihan paket diambil dari modul Pricing sesuai bahasa
- [x] Migrasi `contact_messages`: `form_type`, `locale`, `whatsapp`, `guest_estimate`, `event_date`, `interested_plan`, `consented_at`; `email` & `message` jadi opsional (diuji di MySQL, termasuk rollback)
- [x] Endpoint kontak yang sama dengan `form_type=demo`: WA + jumlah tamu + persetujuan wajib, email opsional, tanggal tidak boleh lewat; subjek otomatis "Permintaan demo — {EO/nama}"; bahasa pengirim tersimpan
- [x] Form kontak biasa tetap dengan aturan lamanya
- [x] Script form di theme (`stagy.js`): validasi di browser, pesan error per field dalam bahasa aktif, reCAPTCHA bila diaktifkan
- [x] CMS inbox: filter "Demo requests" & jumlah tamu, pencarian nama EO/WA, detail demo (WA bisa diklik, jumlah tamu, tanggal, paket, bahasa, waktu persetujuan)
- [x] Email notifikasi menampilkan detail demo; auto-reply dilewati bila tidak ada email
- [x] Test: 12 test di `tests/Feature/DemoRequestTest.php`

**Catatan:**
- **Bug yang ditemukan & diperbaiki:** email notifikasi selalu memasang reply-to ke email pengunjung, sehingga crash bila email kosong.
- Form demo & inbox CMS belum dicoba di browser (build JS lolos).

## Fase 6 — Blog

- [x] Migrasi `posts`: `cover_image_id` (media) + `is_featured` (diuji di MySQL, termasuk rollback)
- [x] Artikel unggulan: yang terbaru bertanda unggulan, fallback ke artikel terbaru; hanya di halaman 1 tanpa filter
- [x] Waktu baca dihitung dari excerpt + teks block (200 kata/menit, minimal 1 menit); juga di API (`reading_time_minutes`)
- [x] Halaman daftar: tab kategori (hanya kategori bahasa aktif yang punya artikel; link ke arsip `/blog/category/{slug}`), grid kartu, pagination bernomor
- [x] Judul & penutup halaman blog diedit dari CMS: halaman ber-slug `blog` (block pertama = judul, sisanya di bawah daftar, SEO-nya dipakai); fallback teks bawaan theme
- [x] Detail: breadcrumb, penulis, tanggal (format bahasa aktif), waktu baca, cover 16:9 (atau placeholder berwarna per kategori), share WA / LinkedIn / salin link, artikel terkait (sekategori dulu, lalu terbaru)
- [x] Block `rich_text` di theme Stagy dirender sebagai Markdown aman (`##`, daftar, tebal, link; HTML mentah dibuang)
- [x] CMS editor post: centang "Featured article" + pemilih cover
- [x] Test: 9 test di `tests/Feature/StagyBlogTest.php`

**Catatan:**
- Halaman News memakai tampilan yang sama (route `/news` tetap ada, tidak ditautkan di theme).
- Tampilan blog & editor CMS belum dicoba di browser.

## Fase 7 — Website Settings

- [x] Teks per bahasa (dipindah dari Fase 1): `general.tagline`, `contact.business_hours`, `seo.default_title`, `seo.default_description`, `experience.announcement` disimpan sebagai `{"id": …, "en": …}`; string lama tetap diterima dan berlaku untuk semua bahasa
- [x] `PublicWebsiteSettings`: semua pembaca settings publik (controller, 404, public API) memakai nilai bahasa aktif → fallback bahasa default → bahasa mana pun yang terisi
- [x] Kontak: nomor WhatsApp (dipakai semua tombol/link WA, fallback ke nomor telepon), jam operasional per bahasa
- [x] Sosial: TikTok ditambahkan (Instagram, LinkedIn, YouTube sudah ada)
- [x] Branding: logo & favicon dari CMS dipakai theme Stagy, fallback ke aset bawaan; OG image default memakai `seo.social_image_id` yang sudah ada
- [x] CMS Settings: satu input per bahasa untuk teks per bahasa, field WhatsApp & TikTok
- [x] Test: 9 test di `tests/Feature/LocalizedWebsiteSettingsTest.php`

**Catatan:**
- Tagline footer = `general.tagline`; "Powered by" diatur di pengaturan theme (nama & link), tidak per bahasa.
- Form Settings belum dicoba di browser.

## Fase 8 — Seeder konten mockup

- [x] `StagySiteSeeder` dipanggil `DatabaseSeeder` saat theme aktif `stagy` (seeder konten company profile lama tetap untuk theme `default`)
- [x] Halaman ID: Beranda (`home`), Fitur, Cara Kerja, Harga, FAQ, Minta Demo (`demo`), Syarat & Ketentuan, Kebijakan Privasi (placeholder), `blog` (judul + CTA penutup)
- [x] Menu `header`, `footer-product`, `footer-help`
- [x] Pricing: 3 paket, 21 baris perbandingan (tanpa "Tim on-site tambahan: Add-on"), 11 add-on
- [x] FAQ 8 item, 4 kategori blog + 7 artikel (artikel "buku tamu digital" lengkap dalam Markdown & ditandai unggulan; lainnya excerpt + placeholder)
- [x] Settings: nama situs, tagline ID/EN, alamat & jam operasional (placeholder), judul/deskripsi SEO default ID/EN
- [x] Feature flag: Services & Portfolio mati untuk Stagy; `contact-messages` tetap hidup (dipakai form demo)
- [x] Idempotent: data yang sudah ada tidak ditimpa, settings hanya mengisi yang kosong (diuji 2× di MySQL)
- [x] Test: 16 test di `tests/Feature/StagySeederTest.php` (termasuk validasi skema semua block yang di-seed & render semua halaman)

**Catatan:**
- **Bug lama diperbaiki:** seeder berjalan tanpa model event, sehingga `locale`/`translation_group_id` tidak terisi. Ditambah `HasTranslations::translationDefaults()`; seeder lama (`PageSeeder`, `MenuSeeder`, `DemoContentSeeder`) ikut diperbaiki.
- Nomor WhatsApp & email sengaja dikosongkan (mockup hanya berisi placeholder). Tombol WA tersembunyi sampai nomor diisi di CMS.
- Halaman `/contact` bawaan tetap ada (tidak ditautkan); flag-nya sama dengan API kontak.

## Komponen theme `stagy`

Dipakai di semua view theme (`resources/themes/stagy/views/components/public/`). Markup baru sebaiknya memakai ini, bukan menulis ulang kelasnya:

| Komponen | Fungsi |
| --- | --- |
| `container` | Kolom halaman; `width="page|article|media|text"` (1160 / 900 / 960 / 720px) |
| `heading` | Skala judul: `size="display|title|section|subsection"`, `:text` untuk highlight `*kata*` |
| `eyebrow` | Label kecil di atas judul; `tone="sage"` untuk latar navy |
| `section-heading` | Eyebrow + judul + link "lihat semua" |
| `button` | Tombol (`primary`, `outline`, `light`, `accent`, `ghost`), resolve link `whatsapp` |
| `badge` | Pil kecil (`accent`, `mint`, `paper`) |
| `number-badge` | Nomor `01`, `02` dalam kotak (`mint`, `ink`, `accent`; `size="sm|md"`) |
| `check-item` | Baris checklist bertanda centang |
| `phone-mockup` | Ilustrasi HP di panel mint (hero, 404) |
| `post-card`, `post-cover` | Kartu & cover artikel |
| `highlight`, `icon`, `ticket-qr` | Teks highlight, ikon garis, pola QR dekoratif |

## Catatan & pertanyaan terbuka

- Konten bertanda `[Konfirmasi: …]` / `[Jawaban dari tim: …]` / `[Isi dari tim legal: …]` di-seed apa adanya agar mudah ditemukan di CMS.
- Konten versi EN tidak di-seed; dibuat dari CMS lewat "Add translation" (halaman, post, menu, kategori) dan pilihan bahasa di Pricing.
- **Belum dilakukan:** review visual di browser (desktop & HP) untuk seluruh halaman dan UI CMS baru.
- **Perlu diisi tim:** nomor WhatsApp, email, alamat, jam operasional, akun sosial, screenshot produk (block Fitur), cover artikel, isi legal, jawaban FAQ, konfirmasi PPN/durasi paket.
