<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import {
  ArrowRight,
  Briefcase,
  Users,
  TrendingUp,
  ShieldCheck,
  Clock,
  HeartHandshake,
  Star,
  ChevronRight,
  Moon,
  Sun,
} from 'lucide-vue-next'
import { useTheme } from '../composables/useTheme'
import { useAuthStore } from '../stores/auth'
import api from '../composables/useApi'

gsap.registerPlugin(ScrollTrigger)

const { theme, toggleTheme } = useTheme()
const auth = useAuthStore()

const stats = ref({ active_jobs: 0, registered_applicants: 0 })
const statsLoaded = ref(false)

const benefits = [
  { icon: TrendingUp, title: 'Karir yang Berkembang', desc: 'Program pengembangan karir terstruktur dan peluang promosi yang jelas.' },
  { icon: HeartHandshake, title: 'Budaya Kolaboratif', desc: 'Lingkungan kerja yang inklusif, saling mendukung, dan berinovasi bersama.' },
  { icon: ShieldCheck, title: 'Benefit Kompetitif', desc: 'Paket kompensasi kompetitif, BPJS, asuransi kesehatan, dan tunjangan tambahan.' },
  { icon: Clock, title: 'Work-Life Balance', desc: 'Fleksibilitas jam kerja dan kebijakan cuti yang menghargai kehidupan pribadi.' },
]

async function loadStats() {
  try {
    const { data } = await api.get('/public/stats')
    stats.value = data
    statsLoaded.value = true
    animateCounters()
  } catch {
    statsLoaded.value = true
  }
}

function animateCounters() {
  const targets = { jobs: 0, applicants: 0 }
  gsap.to(targets, {
    jobs: stats.value.active_jobs,
    applicants: stats.value.registered_applicants,
    duration: 1.8,
    ease: 'power2.out',
    onUpdate() {
      const jobsEl = document.getElementById('stat-jobs')
      const appEl = document.getElementById('stat-applicants')
      if (jobsEl) jobsEl.textContent = String(Math.round(targets.jobs))
      if (appEl) appEl.textContent = String(Math.round(targets.applicants))
    },
  })
}

onMounted(async () => {
  // Hero entrance animation
  const tl = gsap.timeline({ defaults: { ease: 'power3.out' } })
  tl.from('.hero-tag', { opacity: 0, y: 16, duration: 0.5 })
    .from('.hero-heading', { opacity: 0, y: 28, duration: 0.7 }, '-=0.2')
    .from('.hero-sub', { opacity: 0, y: 20, duration: 0.6 }, '-=0.4')
    .from('.hero-cta', { opacity: 0, y: 16, scale: 0.97, duration: 0.5 }, '-=0.3')

  // Scroll-triggered reveals
  gsap.from('.stats-section', {
    scrollTrigger: { trigger: '.stats-section', start: 'top 80%' },
    opacity: 0, y: 32, duration: 0.7, ease: 'power2.out',
  })

  gsap.from('.about-section', {
    scrollTrigger: { trigger: '.about-section', start: 'top 80%' },
    opacity: 0, x: -32, duration: 0.7, ease: 'power2.out',
  })

  gsap.utils.toArray<Element>('.benefit-card').forEach((card, i) => {
    gsap.from(card, {
      scrollTrigger: { trigger: card, start: 'top 88%' },
      opacity: 0, y: 24, duration: 0.55, delay: i * 0.08, ease: 'power2.out',
    })
  })

  gsap.from('.cta-section', {
    scrollTrigger: { trigger: '.cta-section', start: 'top 82%' },
    opacity: 0, scale: 0.97, duration: 0.6, ease: 'power2.out',
  })

  await loadStats()
})
</script>

<template>
  <div class="min-h-screen flex flex-col" style="background: var(--color-background)">

    <!-- ── Minimal Navbar ───────────────────────────────────────────────── -->
    <header
      class="sticky top-0 z-20 border-b"
      style="background: var(--color-background); border-color: var(--color-border)"
    >
      <div class="max-w-6xl mx-auto px-5 sm:px-8 h-14 flex items-center justify-between">
        <RouterLink to="/" class="flex items-center gap-2">
          <img
            :src="theme === 'dark' ? '/src/assets/logo/logo-light.svg' : '/src/assets/logo/logo-primary.svg'"
            alt="Logo"
            class="h-7 w-auto"
          />
        </RouterLink>

        <div class="flex items-center gap-2">
          <button
            @click="toggleTheme"
            class="p-2 rounded-md transition-colors"
            style="color: var(--color-text-secondary)"
            :aria-label="theme === 'light' ? 'Dark mode' : 'Light mode'"
          >
            <Moon v-if="theme === 'light'" :size="16" />
            <Sun v-else :size="16" />
          </button>

          <template v-if="auth.isAuthenticated">
            <RouterLink
              :to="auth.isHrAdmin ? '/hr/jobs' : '/jobs'"
              class="px-4 py-1.5 rounded-md text-sm font-semibold transition-colors"
              style="background: var(--color-primary); color: #ffffff"
            >
              Dashboard
            </RouterLink>
          </template>
          <template v-else>
            <RouterLink
              to="/login"
              class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
              style="color: var(--color-text-secondary)"
            >
              Masuk
            </RouterLink>
            <RouterLink
              to="/register"
              class="px-4 py-1.5 rounded-md text-sm font-semibold transition-colors"
              style="background: var(--color-primary); color: #ffffff"
            >
              Daftar
            </RouterLink>
          </template>
        </div>
      </div>
    </header>

    <main class="flex-1">

      <!-- ── Hero ─────────────────────────────────────────────────────── -->
      <section class="max-w-6xl mx-auto px-5 sm:px-8 pt-20 pb-24 text-center">
        <span
          class="hero-tag inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold mb-6"
          style="background: var(--color-primary-subtle); color: var(--color-primary)"
        >
          <Star :size="12" />
          Platform rekrutmen terpercaya
        </span>

        <h1 class="hero-heading text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight mb-6 max-w-3xl mx-auto" style="color: var(--color-text-primary)">
          Temukan Karir<br />
          <span style="color: var(--color-primary)">Impian Anda</span>
        </h1>

        <p class="hero-sub text-lg max-w-xl mx-auto mb-10" style="color: var(--color-text-secondary)">
          Kami menghubungkan talenta terbaik dengan peluang karir yang tepat.
          Bergabunglah dan mulai perjalanan profesional Anda bersama kami.
        </p>

        <div class="hero-cta flex flex-wrap items-center justify-center gap-3">
          <RouterLink
            to="/jobs"
            class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-sm font-semibold transition-all duration-200 hover:opacity-90"
            style="background: var(--color-primary); color: #ffffff"
          >
            Lihat Lowongan
            <ArrowRight :size="16" />
          </RouterLink>
          <RouterLink
            v-if="!auth.isAuthenticated"
            to="/register"
            class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-sm font-semibold transition-colors"
            style="border: 1px solid var(--color-border); color: var(--color-text-primary)"
          >
            Daftar Gratis
          </RouterLink>
        </div>
      </section>

      <!-- ── Stats ─────────────────────────────────────────────────────── -->
      <section
        class="stats-section border-y py-12"
        style="border-color: var(--color-border); background: var(--color-surface)"
      >
        <div class="max-w-6xl mx-auto px-5 sm:px-8 grid grid-cols-2 gap-8 max-w-lg mx-auto text-center">
          <div>
            <p class="text-4xl font-bold mb-1" style="color: var(--color-primary)">
              <span id="stat-jobs">{{ stats.active_jobs }}</span>
            </p>
            <p class="text-sm font-medium" style="color: var(--color-text-secondary)">
              Lowongan Aktif
            </p>
          </div>
          <div>
            <p class="text-4xl font-bold mb-1" style="color: var(--color-primary)">
              <span id="stat-applicants">{{ stats.registered_applicants }}</span>
            </p>
            <p class="text-sm font-medium" style="color: var(--color-text-secondary)">
              Pelamar Terdaftar
            </p>
          </div>
        </div>
      </section>

      <!-- ── Tentang Perusahaan ─────────────────────────────────────────── -->
      <section class="about-section max-w-6xl mx-auto px-5 sm:px-8 py-20">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
          <div>
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold mb-4" style="color: var(--color-primary)">
              <Briefcase :size="14" />
              TENTANG KAMI
            </span>
            <h2 class="text-3xl font-bold mb-5" style="color: var(--color-text-primary)">
              Membangun Tim yang Kuat,<br />Satu Pelamar di Satu Waktu
            </h2>
            <p class="text-base leading-relaxed mb-4" style="color: var(--color-text-secondary)">
              Kami adalah perusahaan yang percaya bahwa orang yang tepat adalah kunci kesuksesan.
              Platform rekrutmen ini dirancang untuk mempermudah proses pencarian kerja — transparan,
              efisien, dan berpusat pada pengalaman pelamar.
            </p>
            <p class="text-base leading-relaxed" style="color: var(--color-text-secondary)">
              Setiap lowongan yang kami buka mencerminkan komitmen kami terhadap pertumbuhan jangka panjang
              dan budaya kerja yang sehat.
            </p>
          </div>

          <!-- Visual accent -->
          <div
            class="rounded-2xl p-8 flex flex-col gap-4"
            style="background: var(--color-surface); border: 1px solid var(--color-border)"
          >
            <div
              v-for="item in [
                { label: 'Proses seleksi transparan', value: '✓' },
                { label: 'Feedback ke pelamar setelah seleksi', value: '✓' },
                { label: 'Onboarding terstruktur untuk karyawan baru', value: '✓' },
                { label: 'Program pengembangan karir internal', value: '✓' },
              ]"
              :key="item.label"
              class="flex items-center gap-3"
            >
              <span
                class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                style="background: var(--color-accent-subtle); color: var(--color-accent)"
              >
                {{ item.value }}
              </span>
              <span class="text-sm" style="color: var(--color-text-primary)">{{ item.label }}</span>
            </div>
          </div>
        </div>
      </section>

      <!-- ── Benefits ─────────────────────────────────────────────────── -->
      <section
        class="py-20 border-t"
        style="border-color: var(--color-border); background: var(--color-surface)"
      >
        <div class="max-w-6xl mx-auto px-5 sm:px-8">
          <div class="text-center mb-12">
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold mb-3" style="color: var(--color-primary)">
              <Users :size="14" />
              MENGAPA BERGABUNG
            </span>
            <h2 class="text-3xl font-bold" style="color: var(--color-text-primary)">
              Lebih dari Sekadar Pekerjaan
            </h2>
          </div>

          <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div
              v-for="benefit in benefits"
              :key="benefit.title"
              class="benefit-card rounded-xl p-6 transition-all duration-200 hover:-translate-y-0.5"
              style="background: var(--color-background); border: 1px solid var(--color-border)"
            >
              <div
                class="w-10 h-10 rounded-lg flex items-center justify-center mb-4"
                style="background: var(--color-primary-subtle)"
              >
                <component :is="benefit.icon" :size="20" style="color: var(--color-primary)" />
              </div>
              <h3 class="font-semibold text-sm mb-2" style="color: var(--color-text-primary)">
                {{ benefit.title }}
              </h3>
              <p class="text-sm leading-relaxed" style="color: var(--color-text-secondary)">
                {{ benefit.desc }}
              </p>
            </div>
          </div>
        </div>
      </section>

      <!-- ── CTA Section ───────────────────────────────────────────────── -->
      <section class="cta-section max-w-6xl mx-auto px-5 sm:px-8 py-20">
        <div
          class="rounded-2xl px-8 py-12 text-center"
          style="background: var(--color-primary-subtle); border: 1px solid color-mix(in srgb, var(--color-primary) 20%, transparent)"
        >
          <h2 class="text-3xl font-bold mb-4" style="color: var(--color-text-primary)">
            Siap Bergabung?
          </h2>
          <p class="text-base mb-8 max-w-md mx-auto" style="color: var(--color-text-secondary)">
            Jelajahi semua posisi yang tersedia dan temukan peluang yang paling sesuai dengan keahlian Anda.
          </p>
          <div class="flex flex-wrap items-center justify-center gap-3">
            <RouterLink
              to="/jobs"
              class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-sm font-semibold transition-all hover:opacity-90"
              style="background: var(--color-primary); color: #ffffff"
            >
              Lihat Semua Lowongan
              <ChevronRight :size="16" />
            </RouterLink>
            <RouterLink
              v-if="!auth.isAuthenticated"
              to="/register"
              class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-sm font-semibold transition-colors"
              style="border: 1px solid var(--color-primary); color: var(--color-primary)"
            >
              Buat Akun Pelamar
            </RouterLink>
          </div>
        </div>
      </section>
    </main>

    <!-- ── Footer ───────────────────────────────────────────────────────── -->
    <footer
      class="border-t py-6 text-center text-sm"
      style="border-color: var(--color-border); color: var(--color-text-secondary)"
    >
      &copy; {{ new Date().getFullYear() }} e-recruitment. Semua hak dilindungi.
    </footer>
  </div>
</template>
