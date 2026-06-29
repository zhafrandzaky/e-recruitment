<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { gsap } from 'gsap'
import { Home, ArrowLeft } from 'lucide-vue-next'

onMounted(() => {
  gsap.from('.not-found-content', {
    opacity: 0,
    y: 24,
    duration: 0.6,
    ease: 'power2.out',
  })
})
</script>

<template>
  <div
    class="min-h-screen flex items-center justify-center px-4"
    style="background: var(--color-background)"
  >
    <div class="not-found-content text-center max-w-md">
      <p
        class="text-8xl font-bold mb-4 select-none"
        style="color: var(--color-border)"
      >
        404
      </p>
      <h1 class="text-2xl font-bold mb-3" style="color: var(--color-text-primary)">
        Halaman tidak ditemukan
      </h1>
      <p class="text-sm mb-8" style="color: var(--color-text-secondary)">
        Halaman yang Anda cari tidak ada atau mungkin sudah dipindahkan.
      </p>
      <div class="flex flex-wrap items-center justify-center gap-3">
        <RouterLink
          to="/"
          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors"
          style="background: var(--color-primary); color: #ffffff"
        >
          <Home :size="16" />
          Ke Beranda
        </RouterLink>
        <button
          @click="$router.back()"
          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium transition-colors"
          style="border: 1px solid var(--color-border); color: var(--color-text-secondary)"
        >
          <ArrowLeft :size="16" />
          Kembali
        </button>
      </div>
    </div>
  </div>
</template>
