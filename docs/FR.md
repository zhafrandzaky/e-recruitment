# FR.md — Functional Requirements

**Project:** e-recruitment
**Version:** 1.0

This document details every functional requirement, grouped by module. Each requirement follows the format: description, actor, input, process, output, and failure conditions. Module-to-phase mapping is in [`docs/ROADMAP.md`](ROADMAP.md).

---

## Modul 1: Autentikasi & Manajemen Akun

### FR-001 — Login Sistem
- **Deskripsi:** Pengguna (Pelamar atau HR) login menggunakan email dan password. Sistem mendeteksi peran secara otomatis berdasarkan akun.
- **Aktor:** Pelamar, HR Admin
- **Input:** Email, Password
- **Proses:**
  1. Pengguna memasukkan kredensial
  2. Sistem memvalidasi format email
  3. Sistem mencocokkan kredensial dengan data di database (password di-hash, bcrypt/argon2)
  4. Sistem menentukan peran (Pelamar/HR) dan mengarahkan ke dashboard sesuai peran
- **Output:** Sesi aktif, redirect ke dashboard sesuai peran
- **Kondisi Gagal:**
  - Email/password salah → pesan error generik "Email atau password salah" (tidak membocorkan mana yang salah)
  - 3x gagal berturut-turut → akun terkunci sementara (lihat FR-001a)

### FR-001a — Account Lockout
- **Deskripsi:** Setelah 3 kali percobaan login gagal berturut-turut, akun dikunci sementara.
- **Proses:** Sistem mencatat percobaan gagal per akun; pada percobaan ke-3, akun dikunci selama periode cooldown yang dikonfigurasi (lihat `docs/NFR.md` untuk durasi default)
- **Output:** Pesan informasi bahwa akun terkunci sementara, dengan estimasi waktu kapan bisa mencoba lagi

### FR-002 — Lupa Password (Reset)
- **Deskripsi:** Pengguna yang lupa password dapat meminta link reset via email.
- **Aktor:** Pelamar, HR Admin
- **Input:** Email
- **Proses:**
  1. Pengguna memilih "Lupa Password" dan memasukkan email
  2. Sistem memvalidasi email terdaftar (tanpa membocorkan apakah email ada di sistem, demi privasi — pesan sukses ditampilkan terlepas hasilnya)
  3. Jika email valid, sistem mengirim link reset bertanda waktu/expiring via email (Resend)
  4. Pengguna mengklik link, memasukkan password baru
- **Output:** Password berhasil diperbarui, pengguna diarahkan ke halaman login
- **Kondisi Gagal:** Link expired → pesan error, opsi kirim ulang

---

## Modul 2: Lowongan Pekerjaan

### FR-003 — Lihat Lowongan (Pelamar)
- **Deskripsi:** Menampilkan daftar lowongan yang berstatus aktif kepada publik.
- **Aktor:** Pelamar (termasuk pengunjung yang belum login)
- **Proses:** Ambil data lowongan dengan status "Aktif" dari database
- **Output:** Daftar lowongan (judul, ringkasan, lokasi, tanggal posting)
- **Validasi:** Hanya lowongan aktif yang ditampilkan; lowongan ditutup/expired tidak muncul di listing publik

### FR-004 — Cari Lowongan
- **Deskripsi:** Pencarian lowongan berdasarkan kata kunci/posisi.
- **Aktor:** Pelamar
- **Input:** Kata kunci pencarian
- **Proses:** Menyaring data lowongan aktif berdasarkan kecocokan judul/deskripsi
- **Output:** Hasil pencarian yang relevan
- **Validasi:** Tampilkan pesan "Tidak ada lowongan ditemukan" jika hasil kosong, bukan halaman kosong tanpa konteks

### FR-005 — Lihat Detail Lowongan
- **Deskripsi:** Menampilkan detail lengkap satu lowongan.
- **Aktor:** Pelamar
- **Output:** Deskripsi tugas, kualifikasi, lokasi, deadline, tombol "Lamar Sekarang"

### FR-006 — Kelola Lowongan (HR — CRUD)
- **Deskripsi:** HR memiliki akses penuh untuk membuat, mengedit, dan menutup/mengubah status lowongan.
- **Aktor:** HR Admin
- **Input:** Judul, deskripsi, kualifikasi, lokasi, deadline
- **Proses:** Validasi kelengkapan data → simpan/update di database
- **Output:** Lowongan tersimpan/diperbarui, tersedia di listing publik jika status "Aktif"
- **Validasi:** Semua field wajib diisi sebelum lowongan bisa dipublikasikan
- **Kondisi tambahan:** Lowongan otomatis berubah status menjadi "Ditutup" saat deadline terlewati (lihat `docs/ARCHITECTURE.md` untuk mekanisme scheduled job), atau ditutup manual oleh HR

---

## Modul 3: Lamaran (Application)

### FR-007 — Unggah CV
- **Deskripsi:** Pelamar mengunggah CV sebagai bagian dari proses melamar.
- **Aktor:** Pelamar (wajib sudah login — lihat relasi include ke FR-001 di `docs/USECASE.md`)
- **Input:** File CV
- **Proses:** Validasi format dan ukuran → simpan ke object storage (S3-compatible)
- **Output:** File tersimpan, path/reference disimpan di database
- **Validasi:** Hanya format PDF diterima; ukuran maksimum 2MB. File yang tidak sesuai ditolak dengan pesan error spesifik (format salah, atau ukuran melebihi batas)

### FR-008 — Isi Form Lamaran
- **Deskripsi:** Pelamar mengisi data diri tambahan sebagai bagian dari lamaran.
- **Aktor:** Pelamar
- **Input:** Nama lengkap, nomor HP, alamat, (field tambahan lain sesuai `docs/SCHEMA.md`)
- **Output:** Data tersimpan, terhubung ke record lamaran
- **Validasi:** Semua field wajib diisi sebelum lamaran dapat disubmit

### FR-009 — Kirim Lamaran
- **Deskripsi:** Finalisasi dan submit lamaran untuk satu lowongan spesifik.
- **Aktor:** Pelamar
- **Proses:** Validasi kelengkapan CV + form → simpan record lamaran dengan status awal
- **Output:** Status lamaran otomatis menjadi "Menunggu" (Pending); trigger notifikasi konfirmasi (lihat FR-014)
- **Kondisi Gagal:** Data belum lengkap (CV atau form belum lengkap) → lamaran tidak dapat disubmit, pesan error menunjukkan field yang kurang

### FR-010 — Lihat Status Lamaran (Pelamar)
- **Deskripsi:** Pelamar dapat melihat status lamaran mereka sendiri (riwayat semua lamaran yang pernah diajukan).
- **Aktor:** Pelamar
- **Output:** Daftar lamaran beserta status terkini masing-masing (Menunggu / Lolos Seleksi Berkas / Ditolak / dijadwalkan interview)

---

## Modul 4: Seleksi Pelamar

### FR-011 — Lihat Daftar Pelamar (HR)
- **Deskripsi:** HR melihat daftar pelamar untuk satu lowongan spesifik.
- **Aktor:** HR Admin
- **Output:** Tabel pelamar (nama, tanggal melamar, status saat ini)

### FR-012 — Lihat/Unduh CV (HR)
- **Deskripsi:** HR dapat melihat atau mengunduh CV (PDF) pelamar.
- **Aktor:** HR Admin
- **Output:** File CV ditampilkan/diunduh
- **Kondisi Gagal:** Jika file CV gagal dimuat/korup → sistem menampilkan pesan peringatan "Dokumen tidak dapat dimuat" dan menyarankan HR menghubungi pelamar untuk re-upload

### FR-013 — Ubah Status Pelamar (HR)
- **Deskripsi:** HR mengubah status lamaran melalui pilihan dropdown.
- **Aktor:** HR Admin
- **Input:** Status baru (Menunggu / Lolos Seleksi Berkas / Ditolak)
- **Proses:** Update status di database → trigger notifikasi otomatis ke pelamar (FR-014)
- **Output:** Status pelamar berubah, tercatat di riwayat (untuk keperluan reporting di Modul 8)
- **Validasi:** Status yang dipilih harus salah satu dari nilai enum yang valid

---

## Modul 5: Notifikasi

### FR-014 — Notifikasi Email Otomatis
- **Deskripsi:** Sistem mengirim email otomatis ke pelamar pada setiap perubahan status atau saat lamaran berhasil terkirim.
- **Aktor sistem:** Resend (production) / Mailpit (development)
- **Pemicu:**
  - Lamaran berhasil dikirim (FR-009) → email konfirmasi/tanda terima
  - Status lamaran berubah (FR-013) → email notifikasi status baru
  - Interview dijadwalkan (FR-015) → email berisi detail dan link meeting
- **Output:** Email terkirim ke alamat pelamar yang terdaftar
- **Kondisi Gagal:** Jika pengiriman email gagal, sistem mencatat kegagalan (log) untuk retry — lihat `docs/ARCHITECTURE.md` untuk mekanisme queue/retry

---

## Modul 6: Penjadwalan Interview

### FR-015 — Jadwalkan Interview (HR)
- **Deskripsi:** HR menjadwalkan interview untuk pelamar yang lolos seleksi berkas. Sistem secara otomatis membuat link Google Meet/Zoom melalui API dan mengirimkannya ke pelamar.
- **Aktor:** HR Admin
- **Input:** Tanggal, jam interview, pelamar yang dipilih
- **Proses:**
  1. HR memilih tanggal/jam dan menekan "Buat Jadwal"
  2. Sistem memanggil Google Calendar/Meet API (atau Zoom API) untuk membuat event dan menghasilkan link meeting
  3. Sistem menyimpan detail jadwal dan link, terhubung ke record lamaran
  4. Sistem mengirim email otomatis ke pelamar berisi tanggal, jam, dan link (FR-014)
- **Output:** Jadwal interview tersimpan, email terkirim ke pelamar
- **Catatan penting:** Interview itu sendiri terjadi **di luar sistem** (di Google Meet/Zoom langsung) — sistem ini hanya menangani penjadwalan dan distribusi link, bukan video call yang embedded. Lihat `docs/DECISIONS.md` untuk alasan keputusan ini.
- **Kondisi Gagal:** Jika panggilan API gagal (kredensial invalid, layanan down), sistem menampilkan error ke HR dan tidak menyimpan jadwal yang gagal, dengan opsi coba lagi

### FR-016 — Reschedule / Batalkan Interview (HR)
- **Deskripsi:** HR dapat mengubah jadwal atau membatalkan interview yang sudah dibuat.
- **Aktor:** HR Admin
- **Proses:** Update/hapus event yang terkait via API, kirim ulang notifikasi ke pelamar dengan info terbaru
- **Output:** Jadwal diperbarui/dibatalkan, pelamar diberi tahu

---

## Modul 7: Chat Real-time

### FR-017 — Chat per Lamaran
- **Deskripsi:** Satu thread chat real-time per lamaran, menghubungkan HR dan Pelamar yang bersangkutan dengan lamaran tersebut.
- **Aktor:** Pelamar, HR Admin
- **Proses:** Pesan dikirim dan diterima secara real-time melalui WebSocket (Laravel Reverb)
- **Output:** Riwayat chat tersimpan dan dapat dilihat kembali, muncul di halaman detail lamaran (HR) dan halaman status lamaran (Pelamar)
- **Batasan scope yang disengaja (lihat `docs/DECISIONS.md`):**
  - Tidak ada chat antar-pelamar atau grup
  - Tidak ada attachment file di dalam chat
  - Tidak ada read-receipt atau typing indicator
  - Satu thread chat terikat ke satu record lamaran spesifik — bukan chat umum lintas-lamaran

---

## Modul 8: Reporting & Analytics

### FR-018 — Dashboard Reporting (HR)
- **Deskripsi:** HR melihat data agregat atas pipeline rekrutmen mereka.
- **Aktor:** HR Admin
- **Output:**
  - Jumlah pelamar per lowongan
  - Funnel seleksi: distribusi status (Menunggu / Lolos Seleksi Berkas / Ditolak) per lowongan
  - Time-to-hire: rata-rata jumlah hari dari lowongan dibuka sampai ada pelamar yang statusnya menjadi final (diterima)
- **Proses:** Query agregasi atas data lamaran dan riwayat perubahan status — lihat `docs/SCHEMA.md` untuk struktur data yang mendukung query ini
- **Validasi:** Data ditampilkan real-time atau near-real-time (tidak perlu generate report manual/batch terpisah)

---

## Modul 9: Landing Page Publik

> Modul ini ditambahkan post-Phase-1 sebagai keputusan desain eksplisit. Lihat `docs/DECISIONS.md` ADR-021 untuk konteks.

### FR-019 — Tampilan Landing Page Publik

- **Deskripsi:** Pengunjung yang membuka root URL (`/`) melihat halaman marketing perusahaan sebelum menjelajah ke daftar lowongan.
- **Aktor:** Pelamar, pengunjung publik (termasuk yang belum login)
- **Input:** —
- **Proses:** Render halaman dengan konten statis (teks di kode) dikombinasikan data live dari FR-020.
- **Output:** Halaman landing page berisi:
  - **Hero section** — headline dan tagline perusahaan, CTA utama menuju `/jobs`
  - **Tentang Perusahaan** — deskripsi singkat perusahaan (teks statis)
  - **Benefit Kerja** — daftar keunggulan bergabung (statis, ikon dari Lucide)
  - **Statistik Live** — jumlah lowongan aktif saat ini dan pelamar terdaftar (dari FR-020)
  - **CTA sekunder** — link menuju `/jobs` dan `/register`
- **Validasi:** Dapat diakses tanpa login. Jika endpoint statistik gagal, halaman tetap tampil tanpa angka (graceful degradation).

### FR-020 — Statistik Publik

- **Deskripsi:** Endpoint publik yang mengembalikan statistik agregat platform untuk ditampilkan di landing page.
- **Aktor:** Sistem (dipanggil oleh landing page)
- **Input:** Tidak ada — tidak memerlukan autentikasi
- **Proses:**
  1. Hitung `job_postings` dengan `status = 'active'` dan `deleted_at IS NULL`
  2. Hitung `users` dengan `role = 'applicant'`
- **Output:** `{ active_jobs: integer, registered_applicants: integer }` — lihat `docs/API.md` Section 8 untuk kontrak lengkap
- **Validasi:** Hanya angka agregat — tidak ada data personal yang terekspos. Field `total_applications` dan `shortlisted_applicants` ditambahkan di Phase 2 saat tabel `applications` tersedia.

---

## Traceability Note

Setiap FR di atas terhubung ke use case yang sesuai di [`docs/USECASE.md`](USECASE.md) dan ke entity/tabel yang relevan di [`docs/SCHEMA.md`](SCHEMA.md). Saat menambah FR baru, pastikan traceability ini tetap konsisten di kedua dokumen tersebut.
