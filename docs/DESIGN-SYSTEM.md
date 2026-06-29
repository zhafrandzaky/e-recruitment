# DESIGN-SYSTEM.md — Visual Design Standard

**Project:** e-recruitment

> **Untuk AI agent:** Dokumen ini adalah single source of truth untuk setiap keputusan warna, font, icon, dan animasi di UI e-recruitment. "Modern, futuristik, clean, warna profesional terkurasi" (sesuai `AGENTS.md`) bukan aspirasi samar — artinya mengikuti token dan aturan konkret di bawah ini. Jangan mengarang warna baru, memilih icon set lain, atau menambah animasi yang tidak tercakup di Bagian 6. Kalau sebuah layar butuh token yang belum didefinisikan di sini, STOP dan tanyakan dulu, jangan menebak nilai hex sendiri.

---

## 1. Filosofi Desain

Identitas visual e-recruitment dibangun di atas konvensi kategori **professional network/enterprise tool** (LinkedIn, dashboard SaaS B2B) — biru-sentris, terpercaya, neutral-dominant — tanpa meniru literal nilai warna brand manapun. Palette ini secara sengaja menghindari:

- Gradasi indigo/violet (klise visual "generic AI-generated SaaS" paling mudah dikenali saat ini).
- Glassmorphism atau background gradient-mesh tebal.
- Lebih dari satu hue dominan yang bersaing dalam satu tampilan yang sama.

Aturan dasar: **kurang lebih 80% dari setiap layar harus netral** (putih/abu/near-black). Biru brand hanya muncul pada elemen interaktif — tombol primer, status aktif, link, tab terpilih. Warna adalah sinyal, bukan dekorasi.

Project ini **bukan** platform social-network (tidak ada feed, like, atau social graph) — jadi tone visual condong ke arah **dashboard kerja yang efisien dan tepercaya** (gaya ATS/enterprise tool), bukan gaya aplikasi sosial yang playful.

---

## 2. Color Tokens

Semua token didefinisikan sebagai CSS custom properties, dengan nilai light dan dark terpisah. Kedua tema wajib didukung di setiap layar (lihat NFR.md — Usability) — jangan pernah ship layar yang hanya mendukung satu tema.

### 2.1 Primary (Brand Blue)

| Token | Light | Dark | Penggunaan |
|---|---|---|---|
| `--color-primary` | `#0B5FAE` | `#4C9AE8` | Tombol primer, nav item aktif, link |
| `--color-primary-hover` | `#084A87` | `#6BAEF0` | Hover/active state pada elemen primer |
| `--color-primary-subtle` | `#E6F0FA` | `#16293B` | Background subtle untuk item terpilih/highlight (mis. sidebar item aktif) |

### 2.2 Accent (Status/Success Green)

| Token | Light | Dark | Penggunaan |
|---|---|---|---|
| `--color-accent` | `#057642` | `#3FC373` | Indikator status "Lolos Seleksi Berkas", badge sukses, status lowongan "Aktif" |
| `--color-accent-subtle` | `#E5F5EC` | `#0F2B1C` | Background subtle untuk state positif |

### 2.3 Semantic (Warning/Error)

| Token | Light | Dark | Penggunaan |
|---|---|---|---|
| `--color-warning` | `#B45309` | `#F2A65A` | Peringatan (mis. format CV ditolak, ukuran file >2MB) |
| `--color-error` | `#C03434` | `#F08080` | Error, konfirmasi aksi destruktif (mis. tolak lamaran, hapus lowongan) |

### 2.4 Neutral / Surface

| Token | Light | Dark | Penggunaan |
|---|---|---|---|
| `--color-background` | `#FFFFFF` | `#0F1419` | Background halaman |
| `--color-surface` | `#F4F6F8` | `#1B2127` | Card, sidebar, modal, dropdown |
| `--color-border` | `#DCE3E8` | `#2A3138` | Divider, border input, border card |
| `--color-text-primary` | `#1A1D21` | `#E8EAED` | Teks primer |
| `--color-text-secondary` | `#5C6670` | `#9AA4AD` | Teks sekunder, caption, placeholder |

### 2.5 Larangan Eksplisit

- Tidak boleh ada gradient yang menggunakan lebih dari dua hue yang bersebelahan di color wheel. Jika gradient dipakai (mis. hero landing page), harus dua warna **bersebelahan** (mis. dua nuansa biru), jangan lompatan hue kontras tinggi (mis. ungu-ke-pink, biru-ke-oranye).
- Tidak boleh menggunakan ulang nilai literal `#0A66C2` (LinkedIn) atau warna brand kompetitor lain — biru primer e-recruitment harus tetap berbeda secara sengaja (catat alasan di `docs/DECISIONS.md` saat finalisasi).

---

## 3. Logo Usage

- Logo e-recruitment (`logo.svg`, diletakkan di root monorepo saat project start) memiliki siluet tetap yang **tidak boleh diubah**.
- Hanya warna fill yang boleh berubah sesuai tema aktif:
  - **Tema light, di background terang:** render dengan `--color-primary` (`#0B5FAE`).
  - **Tema dark, di background gelap:** render dengan warna terang yang kontrasnya cukup terhadap `--color-background` dark (`#0F1419`) — verifikasi kontras memenuhi WCAG AA sebelum difinalisasi.
- AI agent yang bertanggung jawab atas setup asset frontend awal (Phase 0/1) wajib menghasilkan kedua varian warna sebagai file SVG teroptimasi terpisah (mis. `logo-primary.svg`, `logo-light.svg`) dan memindahkannya ke direktori asset frontend yang tepat — jangan biarkan satu-satunya copy tertinggal di root repo setelah setup awal selesai.

---

## 4. Typography

| Token | Nilai | Penggunaan |
|---|---|---|
| Font family | Sans-serif modern dan clean, cocok untuk UI maupun body text (mis. Inter atau setara) — dipilih final saat implementasi Phase 0/1, diverifikasi lisensinya dan dimuat dengan metode performant (self-hosted), bukan diasumsikan dari memori | Semua teks UI |
| Heading weight | 600–700 (semi-bold s/d bold) | Judul halaman, header section |
| Body weight | 400 (regular) | Body text, deskripsi |
| Base size | 16px (1rem) | Baseline body text — skala naik/turun lewat Tailwind type scale, jangan pakai nilai pixel arbitrer |

**Catatan untuk AI agent yang mengimplementasikan:** konfirmasi pilihan font final dan setup lisensi/self-hosting-nya sebagai bagian dari Phase 0/1, lalu catat keputusan final sebagai ADR baru di `docs/DECISIONS.md` — dokumen ini sengaja membiarkan nama font spesifik terbuka sampai pengecekan saat implementasi, bukan hardcode pilihan yang mungkin sudah basi saat Phase 0 berjalan.

---

## 5. Icons — Lucide Saja

- **Lucide** (`lucide-vue-next`) adalah satu-satunya icon library yang dipakai di seluruh UI e-recruitment. Jangan pernah mencampur Heroicons, Phosphor, Font Awesome, atau set lain, bahkan untuk satu icon saja — inkonsistensi visual antar icon set langsung terlihat dan melanggar aturan "clean, terkurasi".
- Tidak boleh ada karakter emoji/emoticon di manapun dalam UI (sesuai governance). Jika sebuah konsep terasa butuh visual mirip emoji (mis. icon "perayaan" untuk lamaran diterima), gunakan icon Lucide paling sesuai sebagai gantinya.

---

## 6. Prinsip Animasi

UI e-recruitment harus terasa hidup dengan animasi (sesuai requirement awal project), tapi **animasi harus purposeful, bukan dekorasi berlebihan** — ini garis pembatas antara "polished" dan "AI slop".

### 6.1 Di mana animasi pantas dipakai

- **State feedback:** tombol ditekan, validasi form muncul/hilang, toggle switch, loading state saat upload CV.
- **Transisi:** transisi halaman/route, modal/dialog buka-tutup, dropdown/accordion expand-collapse.
- **Feedback real-time:** notifikasi lamaran baru masuk, pesan baru muncul di chat (per-lamaran), status lamaran berubah (mis. "Menunggu" → "Lolos Seleksi Berkas" muncul dengan transisi halus), badge counter bertambah.
- **Landing/marketing surface:** halaman publik (landing page perusahaan, halaman daftar lowongan publik) boleh memakai animasi yang lebih ekspresif (hero scroll effect, background animasi halus) karena pengguna jarang mengunjunginya berulang dan menentukan kesan pertama.

### 6.2 Di mana animasi TIDAK pantas dipakai

- Dashboard dan layar penggunaan-tinggi (tabel daftar pelamar, dashboard reporting/analytics HR, halaman kelola lowongan) hanya pakai transisi **subtle** — bukan tempat untuk efek showcase yang berat, karena akan memperlambat penggunaan berulang dan terasa sebagai clutter, bukan polish.
- Jangan animasikan sesuatu hanya karena komponennya mendukung animasi. Setiap elemen yang dianimasikan harus punya alasan jelas yang terkait kategori di 6.1.

### 6.3 Implementasi (Vue murni — tanpa Nuxt)

- **GSAP** adalah library animasi utama untuk seluruh UI — dipilih karena powerful untuk animasi kompleks (timeline, scroll-trigger, orchestrated sequences) dan menjadi standar industri.
- Untuk transisi dasar Vue (route transition, `v-if`/`v-show` enter-leave, list transition), gunakan **Vue Transition/TransitionGroup built-in** terlebih dahulu sebelum menjangkau GSAP — pakai GSAP khusus untuk animasi yang butuh kontrol timeline lebih kompleks (mis. sequence multi-elemen di landing page, scroll-triggered reveal via `ScrollTrigger` plugin GSAP).
- Komponen UI dasar (modal, dropdown, accordion, tabs) bisa memakai **Headless UI (Vue)** atau **Reka UI** (keduanya framework-agnostic, kompatibel Vue murni, tidak terikat Nuxt) sebagai basis struktur, lalu animasi transisinya tetap diatur lewat Vue Transition/GSAP sesuai aturan di atas — jangan mengandalkan animasi bawaan library komponen jika tidak sesuai prinsip 6.1/6.2.
- Landing/marketing surface (6.1, poin terakhir) boleh menggunakan GSAP dengan lebih ekspresif (ScrollTrigger, timeline kompleks) karena alasan yang sama seperti di atas.

---

## 7. Spacing dan Layout

- Gunakan Tailwind default spacing scale (`4px` base unit) secara konsisten — jangan memasukkan nilai pixel arbitrer di custom CSS kalau utility class Tailwind sudah mencakup kebutuhan tersebut.
- Layout halaman standar (sesuai `docs/SRS.md` UIR-001): Header/Navbar (atas), Sidebar kontekstual (kiri, untuk modul yang relevan), Konten Utama (tengah), Footer (bawah). Struktur ini harus konsisten secara visual di setiap halaman terautentikasi.

---

## 8. Responsive Breakpoints

Ikuti Tailwind default breakpoint scale (`sm`, `md`, `lg`, `xl`, `2xl`) kecuali ada layar spesifik dengan alasan terdokumentasi untuk berbeda. Minimum viewport yang didukung: 360px width.

---

## 9. Sumber & Adaptasi

Dokumen ini diadaptasi dari design system project lain milik pemilik repo ("Zinkly") dengan penyesuaian berikut:
- Istilah dan contoh spesifik ke konteks social-network (feed, like count) diganti dengan konteks e-recruitment (lamaran, status seleksi, chat interview).
- Stack implementasi animasi diganti dari Nuxt-specific (Inspira UI, Nuxt UI, Reka UI sebagai bagian Nuxt ecosystem) ke Vue 3 murni + GSAP, karena e-recruitment menggunakan Vue.js murni, bukan Nuxt.
- Prinsip warna, tipografi, icon, dan filosofi animasi dipertahankan karena bersifat stack-agnostic dan tetap relevan untuk kategori produk yang sama (professional/enterprise tool).
