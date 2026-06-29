# NFR.md — Non-Functional Requirements

**Project:** e-recruitment
**Version:** 1.0

## 4.1 Performance

**NFR-001 — Response Time**
- Halaman umum (listing lowongan, dashboard) merespons dalam ≤3 detik pada koneksi standar
- Pencarian lowongan merespons dalam ≤2 detik
- Submit lamaran (termasuk upload CV) selesai dalam ≤5 detik untuk file di bawah batas maksimum (2MB)
- Pesan chat real-time terkirim/diterima dalam ≤1 detik dalam kondisi jaringan normal

**NFR-002 — Capacity**
- Sistem harus dapat menangani minimal 1.000 pengguna aktif (gabungan Pelamar+HR) tanpa degradasi performa signifikan, untuk skala satu perusahaan menengah
- Sistem harus dapat menangani volume data pelamar yang besar (ribuan record lamaran) tanpa penurunan performa query — lihat `docs/SCHEMA.md` untuk strategi indexing

## 4.2 Reliability & Availability

**NFR-003 — Ketersediaan Sistem (Uptime)**
- Target uptime ≥99% selama infrastruktur deployment (VPS/server perusahaan) berjalan normal
- Catatan: karena model distribusi single-tenant (lihat `docs/PRD.md` Bagian 5), tidak ada SLA uptime terpusat yang dikelola pengembang produk — tanggung jawab uptime berada pada pihak yang men-deploy

**NFR-004 — Pemulihan Sistem (Recovery)**
- Backup database harian (otomatis, terjadwal)
- Backup object storage (CV dan file terkait) sesuai kebijakan retensi yang dikonfigurasi per-deployment
- Recovery time objective (RTO): sistem dapat dipulihkan dari backup dalam waktu yang wajar untuk skala single-tenant (target: dalam hitungan jam, bukan hari)

## 4.3 Security Requirements

**NFR-005 — Keamanan Data**
- Password di-hash menggunakan algoritma modern (bcrypt atau argon2 — keputusan final dicatat di `docs/DECISIONS.md`)
- HTTPS wajib untuk seluruh komunikasi klien-server di environment production
- Proteksi terhadap XSS dan SQL Injection (parameterized query/ORM, output encoding)
- Validasi file upload ketat (format PDF saja, ukuran maksimum 2MB) untuk mencegah upload file berbahaya
- Rate limiting pada endpoint autentikasi dan endpoint yang menerima input publik (mis. submit lamaran)
- Detail lengkap threat model dan kontrol keamanan ada di [`docs/SECURITY.md`](SECURITY.md)

**NFR-005a — Account Lockout**
- Lockout otomatis setelah 3 kali gagal login berturut-turut
- Durasi cooldown default: 15 menit (dapat dikonfigurasi via environment variable — lihat `docs/ENVIRONMENT.md`)

## 4.4 Usability Requirements

**NFR-006 — Kemudahan Penggunaan**
- UI sederhana dan navigasi mudah dipahami tanpa training — target pengguna memiliki kemampuan teknis dasar-menengah
- Pesan error harus jelas dan actionable (bukan pesan teknis/stack trace)
- Desain responsif: mendukung viewport minimum 360px (mobile) hingga desktop

**NFR-USE-001 — Minimum Viewport**
- Mendukung lebar viewport minimum 360px

**NFR-USE-002 — Dual Theme**
- Setiap halaman wajib mendukung tema light dan dark — lihat `docs/DESIGN-SYSTEM.md` untuk token warna kedua tema

**NFR-USE-005 — No Emoticon**
- Tidak ada karakter emoji/emoticon di manapun dalam UI — gunakan icon Lucide sebagai pengganti jika diperlukan

**NFR-USE-006 — Kontras Warna**
- Semua kombinasi teks/background harus memenuhi WCAG AA minimum untuk kontras warna, di kedua tema (light dan dark)

## 4.5 Scalability Requirements

**NFR-007 — Skalabilitas Data**
- Sistem harus dapat menangani pertumbuhan jumlah lowongan dan lamaran tanpa perubahan arsitektur fundamental, dalam skala satu perusahaan (bukan skala multi-tenant)
- Query reporting/analytics (Modul 8) harus tetap performant seiring volume data lamaran bertambah — pertimbangkan strategi agregasi/caching jika volume data besar (lihat `docs/ARCHITECTURE.md`)

## 4.6 Validation Requirements

**NFR-008 — Validasi Input & File**
- Semua input form divalidasi baik di sisi klien (UX cepat) maupun sisi server (otoritatif, tidak bisa dilewati)
- Validasi file upload mencakup: ekstensi, MIME type aktual (bukan hanya ekstensi nama file), dan ukuran — untuk mencegah file berbahaya menyamar sebagai PDF

## 4.7 Portability Requirements (khusus untuk model distribusi single-tenant)

**NFR-009 — Portabilitas Deployment**
- Sistem harus dapat di-deploy baik di Railway (fase development) maupun sebagai Docker Compose self-contained (fase production/delivery ke klien) tanpa perubahan kode — hanya perubahan konfigurasi environment variable
- Tidak boleh ada dependency yang mengikat sistem ke satu provider cloud tertentu (vendor lock-in) — storage harus S3-compatible (bukan AWS-specific API yang tidak portable), email harus dapat diganti providernya via konfigurasi

**NFR-010 — Branding Configurability**
- Nama aplikasi dan logo harus dapat dikonfigurasi per-deployment via environment variable (`APP_NAME`, `APP_LOGO_URL`), tanpa perlu mengubah kode sumber — lihat `docs/ENVIRONMENT.md`
