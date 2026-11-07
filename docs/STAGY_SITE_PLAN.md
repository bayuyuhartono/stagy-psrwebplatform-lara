# Stagy Site — Workflow & Status

Implementasi landing site **Stagy** (sistem registrasi & check-in event untuk EO) di atas platform CMS ini.

- **Sumber desain:** canvas "Stagy Landing Mockup" — https://claude.ai/artifact/TXB3RUCLRp8oZhCqXUcZ7V
- **Dimulai:** 2026-11-07
- **Legend:** `[x]` selesai · `[~]` sebagian · `[ ]` belum · ⏸ menunggu keputusan

**Fase aktif:** Fase 1 selesai — menunggu review sebelum Fase 2 (branch `feat/stagy-site`)

| Fase | Judul | Status | Commit |
| --- | --- | --- | --- |
| 1 | Fondasi i18n | `[x]` | `feat(stagy): fase 1 — fondasi i18n` |
| 2 | Theme `stagy` | `[ ]` | — |
| 3 | Block type baru | `[ ]` | — |
| 4 | Modul Pricing | `[ ]` | — |
| 5 | Form Minta Demo | `[ ]` | — |
| 6 | Blog | `[ ]` | — |
| 7 | Website Settings | `[ ]` | — |
| 8 | Seeder konten mockup | `[ ]` | — |

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
8. Commit terpisah per fase: `feat(stagy): fase N — <judul>`, lalu **review bersama user** sebelum lanjut ke fase berikutnya.

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

- [ ] `resources/themes/stagy/theme.json` + settings theme
- [ ] String UI theme lewat `lang/id` & `lang/en` (dipindah dari Fase 1)
- [ ] Token warna `public-*` versi Stagy (navy `#1E2956`, hijau `#486C65`, mint `#F5F8F6`/`#EDF2F0`, border `#D6E0DC`, muted `#525C7A`)
- [ ] Font Horizon (self-hosted) + skala tipografi yang dinaikkan
- [ ] Aset brand: logo, favicon 16/32/180/512 (diambil dari canvas)
- [ ] Layout + navbar desktop (link aktif) + navbar HP (toggle menu, aksesibel)
- [ ] Footer: brand, menu Produk & Bantuan, kontak, sosial, "Powered by Pesoros"
- [ ] Tombol WhatsApp melayang (dari settings)
- [ ] Switcher bahasa "ID · EN"
- [ ] Halaman 404 "Tiket tidak ditemukan"
- [ ] Theme aktif = `stagy` untuk instalasi ini; feature flag Services/Portfolio/News/Contact dimatikan
- [ ] Test: theme resolve, view theme dipakai, 404 ter-render

## Fase 3 — Block type baru

Didaftarkan di `config/content_blocks.php` (field + rules), di-render oleh theme `stagy`, bisa diedit di block editor CMS.

- [ ] `hero` diperluas: badge, CTA kedua, trust points, visual e-ticket
- [ ] `feature_cards` — kartu bernomor dengan link
- [ ] `steps` — langkah bernomor, opsional kolom "Anda / Stagy"
- [ ] `feature_group` — label tahap, judul, deskripsi, checklist, screenshot (layout zig-zag) + navigasi chip anchor
- [ ] `support_tiers` — pendampingan hari-H per paket (data dari Pricing)
- [ ] `faq` — accordion (item terbuka pertama)
- [ ] `legal_sections` — bagian bernomor + daftar isi otomatis, tanggal pembaruan
- [ ] `call_to_action` diperluas: CTA kedua (WhatsApp)
- [ ] `pricing_cards`, `pricing_table`, `pricing_addons` — referensi ke modul Pricing (Fase 4)
- [ ] `demo_form` — form Minta Demo (Fase 5)
- [ ] Test: validasi tiap block, render tiap block

## Fase 4 — Modul Pricing

- [ ] Model + migrasi: paket (nama, tagline, harga mulai, harga per tamu tambahan, tamu termasuk, cara check-in, daftar fitur, highlight, label CTA, posisi), grup & baris perbandingan, add-on (nama, harga, satuan) — per locale
- [ ] Layering lengkap + Policy + permission + feature flag `pricing` + audit log + soft delete
- [ ] Admin API + UI CMS
- [ ] Public API
- [ ] Derivasi "termasuk di semua paket" dari baris perbandingan yang nilainya sama
- [ ] Test: CRUD, otorisasi, public API, derivasi

## Fase 5 — Form Minta Demo

- [ ] Migrasi `contact_messages`: `whatsapp`, `guest_estimate`, `event_date`, `interested_plan`, `consented_at`, `form_type`, `locale`
- [ ] Form Request + Action menerima form demo; `subject` otomatis untuk `form_type = demo`
- [ ] CMS: tampilkan & filter field baru
- [ ] Email notifikasi menyertakan field baru
- [ ] Test: submit valid/invalid, persetujuan wajib, rate limit

## Fase 6 — Blog

- [ ] Flag artikel unggulan (`is_featured`), fallback ke artikel terbaru
- [ ] Waktu baca dihitung dari isi blocks
- [ ] Filter kategori + pagination di halaman daftar
- [ ] Detail: breadcrumb, author, tanggal, waktu baca, cover, share (WA, LinkedIn, salin link), artikel terkait
- [ ] Test: featured, waktu baca, filter, related

## Fase 7 — Website Settings

- [ ] Kontak: WhatsApp, email, alamat, jam kerja (teks per locale)
- [ ] Sosial: Instagram, LinkedIn, TikTok, YouTube
- [ ] Branding: logo, favicon, OG image default
- [ ] Footer: tagline, tautan "Powered by" (per locale)
- [ ] Teks settings per locale (dipindah dari Fase 1)
- [ ] Test: settings tampil di theme

## Fase 8 — Seeder konten mockup

- [ ] Halaman ID: Beranda, Fitur, Cara Kerja, Harga, FAQ, Minta Demo, Syarat & Ketentuan, Kebijakan Privasi (placeholder)
- [ ] Menu header & footer (Produk, Bantuan)
- [ ] Data Pricing: 3 paket, 6 grup perbandingan, 11 add-on
- [ ] FAQ 8 item, kategori blog (Panduan, Tips EO, Teknologi, Laporan) + 7 artikel contoh
- [ ] Settings kontak/sosial dengan placeholder dari mockup
- [ ] Seeder idempotent (aman dijalankan ulang)

---

## Catatan & pertanyaan terbuka

- Konten bertanda `[Konfirmasi: …]` / `[Jawaban dari tim: …]` di mockup ikut di-seed apa adanya agar terlihat di CMS sebagai yang perlu diisi.
- Konten versi EN tidak di-seed; dibuat dari CMS lewat fitur "buat terjemahan".
