# SRS.md — Software Requirement Specification

**Project:** e-recruitment
**Version:** 1.0
**Reference standard:** IEEE Std 830 (structure adapted for this project's scope)

## Bagian 1: Pendahuluan

### 1.1 Tujuan Dokumen

Dokumen ini mendefinisikan kebutuhan fungsional dan non-fungsional dari sistem e-recruitment. Dokumen ini menjadi acuan bagi pengembang (manusia maupun AI agent) dan pemangku kepentingan dalam proses pengembangan, pengujian, dan implementasi sistem.

### 1.2 Ruang Lingkup Sistem

e-recruitment adalah platform rekrutmen internal **single-tenant** berbasis web yang digunakan oleh **satu perusahaan** untuk:

- Mempublikasikan dan mengelola lowongan pekerjaan
- Memfasilitasi pelamar eksternal dalam melamar pekerjaan
- Membantu HR dalam proses seleksi kandidat, penjadwalan interview, dan komunikasi dengan pelamar
- Memberikan visibilitas data (reporting) kepada HR atas pipeline rekrutmen mereka

Sistem ini **bukan** platform agregator lintas-perusahaan. Satu deployment sistem ini melayani tepat satu perusahaan — lihat [`docs/PRD.md`](PRD.md) Bagian 4.2 untuk batasan scope eksplisit.

### 1.3 Definisi, Akronim, dan Singkatan

| Istilah | Keterangan |
|---|---|
| HR | Human Resources — staf internal perusahaan yang mengelola rekrutmen |
| Pelamar / Applicant | Pengguna eksternal yang melamar pekerjaan |
| Sistem | Aplikasi e-recruitment |
| ATS | Applicant Tracking System |
| CV | Curriculum Vitae, diunggah dalam format PDF |
| Single-tenant | Satu instance/deployment sistem melayani satu perusahaan saja |
| ADR | Architecture Decision Record, dicatat di `docs/DECISIONS.md` |

### 1.4 Referensi

- IEEE Std 830 (struktur SRS)
- `docs/PRD.md` — visi dan scope produk
- `docs/FR.md`, `docs/NFR.md` — requirement detail
- `docs/USECASE.md` — use case diagram dan narrative lengkap

### 1.5 Gambaran Umum Dokumen

- Bagian 1: Pendahuluan
- Bagian 2: Deskripsi Umum
- Bagian 3: Functional Requirements (ringkasan — detail penuh di `docs/FR.md`)
- Bagian 4: Non-Functional Requirements (ringkasan — detail penuh di `docs/NFR.md`)
- Bagian 5: Use Case Diagram (ringkasan — detail penuh di `docs/USECASE.md`)
- Bagian 6: Persyaratan Antarmuka Eksternal
- Bagian 7: Batasan & Asumsi

## Bagian 2: Overall Description

### 2.1 Perspektif Produk

Sistem ini adalah aplikasi web yang menghubungkan Pelamar (eksternal) dan HR (internal) dalam satu platform digital terintegrasi, didedikasikan untuk satu perusahaan. Sistem terdiri dari frontend Vue.js dan backend Laravel, dengan komunikasi real-time (chat per-lamaran) melalui Laravel Reverb, dan integrasi ke layanan eksternal (Google Calendar/Meet API untuk interview, Resend untuk email).

### 2.2 Fungsi Utama Produk

| No | Fungsi | Deskripsi Singkat |
|---|---|---|
| F-01 | Manajemen Akun Pengguna | Login, autentikasi keamanan, lockout setelah gagal berulang, reset password |
| F-02 | Manajemen Lowongan | Pembuatan, publikasi, dan pengelolaan informasi posisi pekerjaan (CRUD oleh HR) |
| F-03 | Pengelolaan Lamaran | Penanganan berkas masuk (CV), verifikasi format, pelacakan status lamaran oleh pelamar |
| F-04 | Seleksi Kandidat | Penyaringan berkas, perubahan status lamaran oleh HR |
| F-05 | Notifikasi Otomatis | Pengiriman email real-time terkait perubahan status atau konfirmasi lamaran |
| F-06 | Penjadwalan Interview | HR menjadwalkan interview; sistem auto-generate link Google Meet/Zoom dan mengirimkannya ke pelamar |
| F-07 | Chat Real-time | Komunikasi dua arah antara HR dan Pelamar, terikat ke satu lamaran spesifik |
| F-08 | Reporting & Analytics | Dashboard agregat untuk HR: jumlah pelamar per lowongan, funnel seleksi, time-to-hire |

### 2.3 Karakteristik Pengguna

**Pelamar**
- Mengakses lowongan dan mengirim lamaran dari luar perusahaan
- Tingkat kemampuan teknis: dasar–menengah (harus bisa dipakai tanpa training)

**HR Admin**
- Mengelola lowongan, pelamar, interview, dan melihat reporting
- Tingkat kemampuan teknis: menengah
- Saat ini satu jenis peran tunggal — tidak ada sub-role (lihat `docs/DECISIONS.md`)

### 2.4 Lingkungan Operasional

**Server:**
- Sistem Operasi: Linux (container-based)
- Basis data: PostgreSQL
- Cache/Queue: Redis
- Object storage: S3-compatible (MinIO default)

**Klien:**
- Browser modern (Chrome, Firefox, Safari, Edge — versi 2 tahun terakhir)
- Perangkat: Laptop, ponsel pintar (responsive, minimum viewport 360px)

### 2.5 Batasan Desain dan Implementasi

- Berbasis web, membutuhkan koneksi internet aktif
- Format CV: PDF saja, maksimal 2MB
- Frontend wajib menggunakan Bun sebagai package manager (tidak ada npm/yarn/pnpm)
- Single-tenant: tidak ada arsitektur multi-tenant/`tenant_id` di manapun dalam sistem
- Branding (nama aplikasi, logo) dikonfigurasi via environment variable per-deployment, tidak di-hardcode

### 2.6 Asumsi dan Ketergantungan

- Perusahaan yang men-deploy sistem memiliki akses ke layanan Google Calendar/Meet API (atau Zoom API) untuk fitur penjadwalan interview
- Perusahaan memiliki (atau menyediakan) kredensial Resend untuk pengiriman email production
- Sistem tersedia selama infrastruktur deployment (VPS/server) berjalan — tidak ada SLA uptime terpusat yang dikelola oleh pengembang produk, karena setiap deployment dikelola independen oleh perusahaan masing-masing

## Bagian 3: Functional Requirements (Ringkasan)

Lihat [`docs/FR.md`](FR.md) untuk detail lengkap FR-001 sampai FR-0XX per modul.

## Bagian 4: Non-Functional Requirements (Ringkasan)

Lihat [`docs/NFR.md`](NFR.md) untuk detail lengkap performa, reliability, security baseline, usability, dan scalability.

## Bagian 5: Use Case Diagram (Ringkasan)

Sistem memiliki dua aktor manusia (Pelamar, HR Admin) dan dua aktor sistem pendukung (penyedia email untuk notifikasi, dan penyedia Calendar/Meet API untuk interview). Diagram use case lengkap, narrative per use case, dan relasi `<<include>>`/`<<extend>>` ada di [`docs/USECASE.md`](USECASE.md).

## Bagian 6: External Interface Requirements

### 6.1 User Interface (UI) Requirements

**UIR-001 — Struktur Halaman Umum**

Setiap halaman dalam sistem harus memiliki struktur layout yang konsisten:
- Header & Navbar di bagian atas (logo, menu navigasi, profil pengguna)
- Sidebar Navigasi di sisi kiri (untuk modul yang relevan)
- Konten Utama di area tengah
- Footer di bagian bawah

**UIR-002 — Halaman Login**

- Menampilkan logo dan nama sistem (dari env var `APP_NAME`)
- Form input: Email dan Password
- Tautan "Lupa Password" yang mengarah ke proses reset password
- Pesan error user-friendly tanpa informasi teknis (mis. tidak membocorkan "email tidak ditemukan" vs "password salah" secara terpisah)
- Desain responsif (mobile & desktop)

**UIR-003 — Dashboard per Peran**

| Peran | Konten Dashboard |
|---|---|
| Pelamar | Status lamaran, riwayat lamaran, notifikasi, profil, thread chat aktif |
| HR | Daftar pelamar per lowongan, status seleksi, jadwal interview, dashboard reporting, thread chat aktif |

**UIR-004 — Standar Komponen UI**

- Design system konsisten di seluruh halaman — lihat [`docs/DESIGN-SYSTEM.md`](DESIGN-SYSTEM.md) untuk token warna, tipografi, icon (Lucide saja), dan prinsip animasi
- Tidak ada emoticon di manapun dalam UI

**UIR-005 — Halaman Landing Page (`/`)**

- Entry point utama aplikasi sebelum pengguna masuk ke job listing
- Struktur berbeda dari UIR-001 (tidak ada sidebar, tidak ada navbar terautentikasi):
  - **Navbar minimal** — logo dan tombol Masuk/Daftar saja
  - **Hero section** — full-width, headline besar, subtext, CTA tombol "Lihat Lowongan"
  - **Statistik live** — angka aktif dari database (lowongan aktif, pelamar terdaftar)
  - **Tentang & Benefit** — konten statis dua kolom atau card grid
  - **CTA sekunder** — ajakan bergabung dengan link ke `/jobs` dan `/register`
  - **Footer** — sama seperti halaman lain
- Animasi: termasuk kategori "landing/marketing surface" per `docs/DESIGN-SYSTEM.md` Section 6.1 — boleh lebih ekspresif (GSAP hero reveal, scroll-triggered section animation)
- Harus dapat diakses tanpa login; statistik live bersifat opsional (graceful degradation jika gagal)
- Responsif (mobile & desktop) sesuai breakpoint Tailwind standar

### 6.2 Hardware Interfaces

Tidak ada antarmuka hardware khusus — sistem berjalan sepenuhnya berbasis web standar.

### 6.3 Software Interfaces

| Interface | Tujuan |
|---|---|
| Google Calendar/Meet API (atau Zoom API) | Auto-generate link meeting interview |
| Resend API | Pengiriman email production |
| S3-compatible API (MinIO/R2/AWS S3) | Penyimpanan file CV |
| Laravel Reverb (WebSocket) | Chat real-time per-lamaran |

### 6.4 Communication Interfaces

- HTTPS untuk semua komunikasi klien-server
- WebSocket (via Laravel Reverb) untuk chat real-time

## Bagian 7: Batasan & Asumsi

- Sistem ini diasumsikan dijalankan satu instance per perusahaan — bukan infrastruktur shared
- Tidak ada asumsi bahwa perusahaan memiliki SMTP server sendiri (berbeda dari draft awal referensi akademik) — sistem menyediakan Resend sebagai default production
- Detail constraint teknis lengkap (format file, ukuran maksimum, rate limiting) ada di [`docs/NFR.md`](NFR.md) dan [`docs/SECURITY.md`](SECURITY.md)
