<script setup lang="ts">
import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref,
} from 'vue'

import { RouterLink } from 'vue-router'

import {
  ArrowRight,
  Check,
  ChevronLeft,
  ChevronRight,
  ClipboardCheck,
  FileSpreadsheet,
  FileText,
  HandCoins,
  History,
  Landmark,
  Lock,
  Monitor,
  PenLine,
  Quote,
  Send,
  ShieldCheck,
  Store,
  Users,
} from '@lucide/vue'


import SectionWave from '@/components/landing/SectionWave.vue'

import {
  revealDirective as vReveal,
} from '@/directives/reveal'

interface Testimonial {
  initials: string
  role: string
  category: string
  quote: string
}

const activeTestimonialIndex = ref(0)

let testimonialTimer:
  | ReturnType<typeof setInterval>
  | null = null

const testimonials: Testimonial[] = [
  {
    initials: 'SV',
    role: 'Surveyor BPNT',
    category: 'Contoh konten UAT',
    quote:
      'Antarmuka yang sederhana membantu petugas melakukan pendataan dengan lebih mudah melalui perangkat seluler.',
  },
  {
    initials: 'AD',
    role: 'Admin Dinas Sosial',
    category: 'Contoh konten UAT',
    quote:
      'Pengelolaan data dalam satu sistem dapat mengurangi proses pencatatan dan perekapan yang dilakukan berulang.',
  },
  {
    initials: 'MG',
    role: 'Manager Program',
    category: 'Contoh konten UAT',
    quote:
      'Informasi yang belum lengkap dapat diketahui lebih cepat sehingga tindak lanjut kepada petugas menjadi lebih terarah.',
  },
]

const fallbackTestimonial:
  Testimonial = {
    initials: 'SP',
    role: 'SIPBPNT',
    category: 'Informasi sistem',
    quote:
      'SIPBPNT dirancang untuk mendukung pelayanan publik yang lebih tertib dan mudah digunakan.',
  }

const currentTestimonial =
  computed<Testimonial>(() => {
    return (
      testimonials[
        activeTestimonialIndex.value
      ] ?? fallbackTestimonial
    )
  })

// Strip fitur ringkas, tampil tepat di bawah hero
// (gaya ikon-kotak seperti pada referensi desain).
const quickFeatures = [
  {
    icon: PenLine,
    title: 'Pendataan KPM',
    tone: 'orange',
  },
  {
    icon: HandCoins,
    title: 'Penyaluran Tepat Sasaran',
    tone: 'green',
  },
  {
    icon: Store,
    title: 'Transaksi E-Warung',
    tone: 'orange',
  },
  {
    icon: ShieldCheck,
    title: 'Verifikasi & Validasi',
    tone: 'green',
  },
  {
    icon: Lock,
    title: 'Keamanan Data',
    tone: 'orange',
  },
  {
    icon: History,
    title: 'Riwayat & Laporan',
    tone: 'green',
  },
]

const benefits = [
  {
    icon: ClipboardCheck,
    title: 'Pendataan lebih cepat',
    description:
      'Data KPM dan transaksi dicatat secara terstruktur.',
  },
  {
    icon: Monitor,
    title: 'Monitoring lebih mudah',
    description:
      'Progres pelaksanaan dapat dipantau berdasarkan periode.',
  },
  {
    icon: FileSpreadsheet,
    title: 'Rekap otomatis',
    description:
      'Data tersusun tanpa perekapan manual berulang.',
  },
  {
    icon: FileText,
    title: 'Pelaporan lebih efisien',
    description:
      'Laporan dapat disiapkan sesuai kebutuhan Dinas Sosial.',
  },
]

function nextTestimonial(): void {
  activeTestimonialIndex.value =
    (activeTestimonialIndex.value + 1) %
    testimonials.length
}

function previousTestimonial(): void {
  activeTestimonialIndex.value =
    activeTestimonialIndex.value === 0
      ? testimonials.length - 1
      : activeTestimonialIndex.value - 1
}

function selectTestimonial(
  index: number,
): void {
  activeTestimonialIndex.value = index
  restartTimer()
}

function startTimer(): void {
  stopTimer()

  const reducedMotion =
    window.matchMedia(
      '(prefers-reduced-motion: reduce)',
    ).matches

  if (reducedMotion) {
    return
  }

  testimonialTimer =
    window.setInterval(
      nextTestimonial,
      7000,
    )
}

function stopTimer(): void {
  if (!testimonialTimer) {
    return
  }

  window.clearInterval(
    testimonialTimer,
  )

  testimonialTimer = null
}

function restartTimer(): void {
  stopTimer()
  startTimer()
}

onMounted(startTimer)
onBeforeUnmount(stopTimer)
</script>

<template>
  <div>
    <!-- Hero -->
    <section
      class="relative isolate flex min-h-[680px] items-center overflow-hidden bg-government-900 pb-24 pt-32 sm:min-h-[760px] sm:pb-28 sm:pt-36"
    >
      <!-- Foto latar dengan animasi zoom halus saat halaman dibuka -->
      <div
        class="hero-bg absolute inset-0 -z-30 bg-cover bg-center bg-no-repeat"
        :style="{
          backgroundImage: `url('/images/kantor.jpg')`,
          backgroundPosition: 'center 42%',
        }"
        aria-hidden="true"
      />

      <!-- Overlay gradasi oranye-hijau -->
      <div
        class="absolute inset-0 -z-20 bg-[linear-gradient(100deg,rgba(8,51,42,0.94)_0%,rgba(15,118,110,0.82)_38%,rgba(15,118,110,0.35)_68%,rgba(15,118,110,0.1)_100%)]"
        aria-hidden="true"
      />

      <div
        class="absolute inset-0 -z-10 bg-government-900/10"
        aria-hidden="true"
      />

      <div
        class="mx-auto w-full max-w-7xl px-5 sm:px-6 lg:px-8"
      >
        <div class="max-w-2xl">
          <div
            v-reveal="{
              direction: 'left',
            }"
            class="inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/10 px-4 py-2 text-xs font-medium uppercase tracking-[0.13em] text-white backdrop-blur-sm"
          >
            <Landmark
              :size="16"
              aria-hidden="true"
            />

            Dinas Sosial Kota Mojokerto
          </div>

          <h1
            v-reveal="{
              direction: 'left',
              delay: 100,
            }"
            class="mt-6 text-[38px] font-semibold leading-[1.15] tracking-[-0.03em] text-white sm:text-5xl lg:text-[54px]"
          >
            <span class="text-[#FDBA74]">Transparansi dan</span><br>
            <span class="text-[#FDBA74]">Kemudahan Pendataan</span><br>
            Bantuan Pangan BPNT
          </h1>

          <p
            v-reveal="{
              direction: 'up',
              delay: 190,
            }"
            class="mt-6 max-w-xl text-base leading-8 text-white/85 sm:text-lg"
          >
            Sistem digital terintegrasi yang membantu
            proses pendataan KPM, pencatatan transaksi,
            validasi, monitoring, dan pelaporan
            penyaluran Bantuan Pangan Non Tunai di
            Kota Mojokerto.
          </p>

          <div
            v-reveal="{
              direction: 'up',
              delay: 280,
            }"
            class="mt-8 flex flex-col gap-3 sm:flex-row"
          >
            <RouterLink
              :to="{ name: 'login' }"
              class="inline-flex min-h-[52px] items-center justify-center gap-2 rounded-full bg-[#F97316] px-7 text-sm font-semibold text-white shadow-lg shadow-orange-900/30 transition-all duration-200 hover:-translate-y-0.5 hover:bg-[#EA580C]"
            >
              Masuk Sistem

              <Send
                :size="17"
                aria-hidden="true"
              />
            </RouterLink>

            <RouterLink
              :to="{
                name: 'about-sipbpnt',
              }"
              class="inline-flex min-h-[52px] items-center justify-center gap-2 rounded-full border border-white/70 bg-transparent px-7 text-sm font-medium text-white transition-colors hover:bg-white hover:text-[#0F766E]"
            >
              Pelajari SIPBPNT

              <ArrowRight
                :size="17"
                aria-hidden="true"
              />
            </RouterLink>
          </div>

          <div
            v-reveal="{
              direction: 'up',
              delay: 360,
            }"
            class="mt-8 flex flex-col gap-3 border-t border-white/25 pt-6 text-sm text-white/85 sm:flex-row sm:flex-wrap sm:gap-6"
          >
            <div class="flex items-center gap-2">
              <Check
                :size="18"
                class="text-[#FDBA74]"
                aria-hidden="true"
              />

              Akses berbasis peran
            </div>

            <div class="flex items-center gap-2">
              <Check
                :size="18"
                class="text-white"
                aria-hidden="true"
              />

              Responsif untuk perangkat seluler
            </div>

            <div class="flex items-center gap-2">
              <Check
                :size="18"
                class="text-[#FDBA74]"
                aria-hidden="true"
              />

              Data terintegrasi
            </div>
          </div>
        </div>
      </div>

      <SectionWave
        target-color="#FFFFFF"
        accent-color="#08332A"
        :duration="38"
      />
    </section>

    <!-- Strip fitur ringkas -->
    <section class="relative bg-white py-16 sm:py-20">
      <div
        class="mx-auto grid w-full max-w-7xl grid-cols-2 gap-x-8 gap-y-10 px-5 sm:grid-cols-3 sm:px-6 lg:px-8"
      >
        <div
          v-for="(feature, index) in quickFeatures"
          :key="feature.title"
          v-reveal="{
            direction: 'up',
            delay: index * 60,
          }"
          class="flex items-center gap-3"
        >
          <div
            :class="[
              'flex size-12 shrink-0 items-center justify-center rounded-2xl text-white',
              feature.tone === 'orange'
                ? 'bg-[#F97316]'
                : 'bg-[#0F766E]',
            ]"
          >
            <component
              :is="feature.icon"
              :size="22"
              aria-hidden="true"
            />
          </div>

          <span
            class="text-sm font-medium leading-5 text-government-800"
          >
            {{ feature.title }}
          </span>
        </div>
      </div>
    </section>

    <!-- Tentang BPNT -->
    <section
      class="relative overflow-hidden bg-cream-100 py-24 sm:py-28"
    >
      

      <div
        class="relative mx-auto grid w-full max-w-7xl items-center gap-14 px-5 sm:px-6 lg:grid-cols-2 lg:gap-20 lg:px-8"
      >
        <div
          v-reveal="{
            direction: 'left',
          }"
        >
          <span
            class="border-l-4 border-[#F97316] pl-3 text-sm font-medium uppercase tracking-[0.12em] text-[#EA580C]"
          >
            Tentang BPNT
          </span>

          <h2
            class="mt-5 text-3xl font-semibold leading-tight tracking-[-0.03em] text-government-800 sm:text-4xl"
          >
            Bantuan pangan melalui mekanisme
            non-tunai.
          </h2>

          <p
            class="mt-6 max-w-xl text-base leading-8 text-government-500"
          >
            Bantuan Pangan Non Tunai merupakan
            program bantuan pangan bagi Keluarga
            Penerima Manfaat yang disalurkan
            melalui mekanisme pembayaran
            non-tunai sesuai ketentuan pemerintah.
          </p>

          <RouterLink
            :to="{ name: 'about-bpnt' }"
            class="group mt-7 inline-flex items-center gap-2 text-sm font-medium text-[#EA580C] transition-colors hover:text-[#0F766E]"
          >
            Selengkapnya tentang BPNT

            <ArrowRight
              :size="17"
              class="transition-transform group-hover:translate-x-1"
              aria-hidden="true"
            />
          </RouterLink>
        </div>

        <div
          class="grid grid-cols-1 gap-8 sm:grid-cols-2"
        >
          <div
            v-reveal="{
              direction: 'right',
              delay: 90,
            }"
            class="border-l-2 border-[#F97316] pl-5"
          >
            <Users
              :size="29"
              class="text-[#F97316]"
              aria-hidden="true"
            />

            <h3
              class="mt-4 font-medium text-government-800"
            >
              Keluarga Penerima Manfaat
            </h3>

            <p
              class="mt-2 text-sm leading-7 text-government-500"
            >
              Penerima harus tercatat dalam data
              yang berlaku.
            </p>
          </div>

          <div
            v-reveal="{
              direction: 'right',
              delay: 180,
            }"
            class="border-l-2 border-[#0F766E] pl-5"
          >
            <Store
              :size="29"
              class="text-[#0F766E]"
              aria-hidden="true"
            />

            <h3
              class="mt-4 font-medium text-government-800"
            >
              E-Warung
            </h3>

            <p
              class="mt-2 text-sm leading-7 text-government-500"
            >
              Menjadi tempat pelaksanaan transaksi
              bantuan.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Tentang SIPBPNT -->
    <section
      class="relative overflow-hidden bg-white py-24 sm:py-28"
    >
      

      <div
        class="relative mx-auto grid w-full max-w-7xl items-center gap-14 px-5 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:gap-20 lg:px-8"
      >
        <div
          v-reveal="{
            direction: 'left',
          }"
          class="flex min-h-[330px] items-center justify-center"
        >
          <div
            class="flex w-full max-w-sm flex-col items-center border-y border-[#F97316]/30 py-12 text-center"
          >
            <div
              class="flex size-24 items-center justify-center rounded-full bg-[#F97316] text-white"
            >
              <ClipboardCheck
                :size="42"
                :stroke-width="1.6"
                aria-hidden="true"
              />
            </div>

            <span
              class="mt-6 text-sm font-medium text-government-800"
            >
              Pendataan
            </span>

            <span
              class="my-3 h-7 w-px bg-[#F97316]/40"
              aria-hidden="true"
            />

            <span
              class="text-sm font-medium text-government-800"
            >
              Monitoring
            </span>

            <span
              class="my-3 h-7 w-px bg-[#F97316]/40"
              aria-hidden="true"
            />

            <span
              class="text-sm font-medium text-government-800"
            >
              Pelaporan
            </span>
          </div>
        </div>

        <div
          v-reveal="{
            direction: 'right',
            delay: 100,
          }"
        >
          <span
            class="border-l-4 border-[#0F766E] pl-3 text-sm font-medium uppercase tracking-[0.12em] text-[#0F766E]"
          >
            Tentang SIPBPNT
          </span>

          <h2
            class="mt-5 text-3xl font-semibold leading-tight tracking-[-0.03em] text-government-800 sm:text-4xl"
          >
            Satu sistem untuk mendukung proses
            BPNT.
          </h2>

          <p
            class="mt-6 max-w-xl text-base leading-8 text-government-500"
          >
            SIPBPNT membantu Dinas Sosial dalam
            mengelola data BNBA, pendataan KPM,
            transaksi, status penyaluran,
            verifikasi, monitoring, dan laporan
            dalam satu sistem terintegrasi.
          </p>

          <RouterLink
            :to="{
              name: 'about-sipbpnt',
            }"
            class="group mt-7 inline-flex items-center gap-2 text-sm font-medium text-[#0F766E] transition-colors hover:text-[#EA580C]"
          >
            Mengenal SIPBPNT

            <ArrowRight
              :size="17"
              class="transition-transform group-hover:translate-x-1"
              aria-hidden="true"
            />
          </RouterLink>
        </div>
      </div>
    </section>

    <!-- Mengapa dibangun -->
    <section
      class="relative overflow-hidden bg-cream-100 py-24 sm:py-28"
    >
      <div
        class="mx-auto grid w-full max-w-7xl items-center gap-14 px-5 sm:px-6 lg:grid-cols-2 lg:gap-20 lg:px-8"
      >
        <div
          v-reveal="{
            direction: 'left',
          }"
        >
          <span
            class="border-l-4 border-[#F97316] pl-3 text-sm font-medium uppercase tracking-[0.12em] text-[#EA580C]"
          >
            Latar Belakang
          </span>

          <h2
            class="mt-5 text-3xl font-semibold leading-tight tracking-[-0.03em] text-government-800 sm:text-4xl"
          >
            Mengapa SIPBPNT dibangun?
          </h2>

          <p
            class="mt-6 max-w-xl text-base leading-8 text-government-500"
          >
            SIPBPNT dikembangkan untuk mendukung
            proses pendataan, verifikasi,
            monitoring, dan pelaporan penyaluran
            BPNT agar lebih efektif,
            terdokumentasi, dan mudah dipantau
            oleh Dinas Sosial Kota Mojokerto.
          </p>
        </div>

        <div>
          <div
            v-reveal="{
              direction: 'right',
              delay: 80,
            }"
            class="flex gap-4 border-b border-government-200 pb-6"
          >
            <ClipboardCheck
              :size="27"
              class="shrink-0 text-[#F97316]"
              aria-hidden="true"
            />

            <div>
              <h3
                class="font-medium text-government-800"
              >
                Pendataan terstruktur
              </h3>

              <p
                class="mt-1 text-sm leading-7 text-government-500"
              >
                Data lapangan tidak lagi tersebar
                pada dokumen pencatatan berbeda.
              </p>
            </div>
          </div>

          <div
            v-reveal="{
              direction: 'right',
              delay: 160,
            }"
            class="flex gap-4 border-b border-government-200 py-6"
          >
            <Monitor
              :size="27"
              class="shrink-0 text-[#0F766E]"
              aria-hidden="true"
            />

            <div>
              <h3
                class="font-medium text-government-800"
              >
                Monitoring lebih cepat
              </h3>

              <p
                class="mt-1 text-sm leading-7 text-government-500"
              >
                Progres pelaksanaan dapat diketahui
                tanpa menunggu rekap manual.
              </p>
            </div>
          </div>

          <div
            v-reveal="{
              direction: 'right',
              delay: 240,
            }"
            class="flex gap-4 pt-6"
          >
            <FileText
              :size="27"
              class="shrink-0 text-[#F97316]"
              aria-hidden="true"
            />

            <div>
              <h3
                class="font-medium text-government-800"
              >
                Laporan lebih tertib
              </h3>

              <p
                class="mt-1 text-sm leading-7 text-government-500"
              >
                Laporan disusun berdasarkan data
                yang telah tercatat dan divalidasi.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Manfaat -->
    <section
      class="relative overflow-hidden bg-white py-24 sm:py-28"
    >
      

      <div
        class="relative mx-auto w-full max-w-7xl px-5 sm:px-6 lg:px-8"
      >
        <div
          v-reveal="{
            direction: 'up',
          }"
          class="max-w-3xl"
        >
          <span
            class="border-l-4 border-[#F97316] pl-3 text-sm font-medium uppercase tracking-[0.12em] text-[#EA580C]"
          >
            Manfaat SIPBPNT
          </span>

          <h2
            class="mt-5 text-3xl font-semibold tracking-[-0.03em] text-government-800 sm:text-4xl"
          >
            Mendukung pelaksanaan BPNT yang lebih
            teratur.
          </h2>
        </div>

        <div
          class="mt-12 grid gap-x-14 md:grid-cols-2"
        >
          <article
            v-for="(benefit, index) in benefits"
            :key="benefit.title"
            v-reveal="{
              direction:
                index % 2 === 0
                  ? 'left'
                  : 'right',
              delay:
                (index % 2) * 90,
            }"
            class="flex gap-5 border-b border-government-200 py-7"
          >
            <div
              class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-cream-100 text-[#F97316]"
            >
              <component
                :is="benefit.icon"
                :size="24"
                aria-hidden="true"
              />
            </div>

            <div>
              <h3
                class="font-medium text-government-800"
              >
                {{ benefit.title }}
              </h3>

              <p
                class="mt-2 text-sm leading-7 text-government-500"
              >
                {{ benefit.description }}
              </p>
            </div>
          </article>
        </div>

        <RouterLink
          :to="{ name: 'benefits' }"
          class="group mt-9 inline-flex items-center gap-2 text-sm font-medium text-[#EA580C] transition-colors hover:text-[#0F766E]"
        >
          Lihat seluruh manfaat

          <ArrowRight
            :size="17"
            class="transition-transform group-hover:translate-x-1"
            aria-hidden="true"
          />
        </RouterLink>
      </div>
    </section>

    <!-- Testimoni -->
    <section
      class="relative overflow-hidden bg-cream-100 py-24 sm:py-28"
    >
      <div
        class="mx-auto w-full max-w-5xl px-5 sm:px-6 lg:px-8"
      >
        <div
          v-reveal="{
            direction: 'up',
          }"
          class="text-center"
        >
          <span
            class="text-sm font-medium uppercase tracking-[0.12em] text-[#EA580C]"
          >
            Perspektif Pengguna
          </span>

          <h2
            class="mt-4 text-3xl font-semibold tracking-[-0.03em] text-government-800 sm:text-4xl"
          >
            Dirancang berdasarkan kebutuhan
            operasional.
          </h2>
        </div>

        <div
          v-reveal="{
            direction: 'pop',
            delay: 100,
          }"
          class="mt-12 rounded-3xl border border-government-200 bg-white py-9 shadow-sm sm:px-8"
          @mouseenter="stopTimer"
          @mouseleave="startTimer"
          @focusin="stopTimer"
          @focusout="startTimer"
        >
          <Transition
            mode="out-in"
            enter-active-class="transition-all duration-300"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition-all duration-200"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="-translate-y-2 opacity-0"
          >
            <article
              :key="activeTestimonialIndex"
              aria-live="polite"
              class="px-6 sm:px-2"
            >
              <Quote
                :size="38"
                class="text-[#F97316]/30"
                aria-hidden="true"
              />

              <p
                class="mt-5 max-w-3xl text-lg leading-9 text-government-700 sm:text-xl"
              >
                "{{ currentTestimonial.quote }}"
              </p>

              <div
                class="mt-7 flex items-center gap-4"
              >
                <div
                  class="flex size-11 items-center justify-center rounded-full bg-[#0F766E] text-sm font-medium text-white"
                >
                  {{ currentTestimonial.initials }}
                </div>

                <div>
                  <strong
                    class="block text-sm font-medium text-government-800"
                  >
                    {{ currentTestimonial.role }}
                  </strong>

                  <span
                    class="text-xs text-government-500"
                  >
                    {{
                      currentTestimonial.category
                    }}
                  </span>
                </div>
              </div>
            </article>
          </Transition>

          <div
            class="mt-8 flex items-center justify-between px-6 sm:px-2"
          >
            <div class="flex gap-2">
              <button
                v-for="(_, index) in testimonials"
                :key="index"
                type="button"
                :aria-label="`Tampilkan testimoni ${index + 1}`"
                :class="[
                  'h-2.5 rounded-full transition-all',
                  activeTestimonialIndex === index
                    ? 'w-8 bg-[#F97316]'
                    : 'w-2.5 bg-government-300',
                ]"
                @click="selectTestimonial(index)"
              />
            </div>

            <div class="flex gap-2">
              <button
                type="button"
                class="flex size-10 items-center justify-center rounded-full border border-government-200 text-government-600 transition-colors hover:border-[#0F766E] hover:text-[#0F766E]"
                aria-label="Testimoni sebelumnya"
                @click="
                  previousTestimonial();
                  restartTimer()
                "
              >
                <ChevronLeft
                  :size="19"
                  aria-hidden="true"
                />
              </button>

              <button
                type="button"
                class="flex size-10 items-center justify-center rounded-full border border-government-200 text-government-600 transition-colors hover:border-[#0F766E] hover:text-[#0F766E]"
                aria-label="Testimoni berikutnya"
                @click="
                  nextTestimonial();
                  restartTimer()
                "
              >
                <ChevronRight
                  :size="19"
                  aria-hidden="true"
                />
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ dan Kontak -->
    <section
      class="relative overflow-hidden bg-white py-24 sm:py-28"
    >
      

      <div
        class="relative mx-auto grid w-full max-w-7xl gap-12 px-5 sm:px-6 lg:grid-cols-2 lg:gap-20 lg:px-8"
      >
        <div
          v-reveal="{
            direction: 'left',
          }"
        >
          <span
            class="border-l-4 border-[#F97316] pl-3 text-sm font-medium uppercase tracking-[0.12em] text-[#EA580C]"
          >
            Pertanyaan Umum
          </span>

          <h2
            class="mt-5 text-3xl font-semibold text-government-800"
          >
            Informasi yang sering ditanyakan.
          </h2>

          <p
            class="mt-5 max-w-lg text-base leading-8 text-government-500"
          >
            Temukan penjelasan mengenai pengguna,
            keamanan data, proses pendataan, dan
            penggunaan SIPBPNT.
          </p>

          <RouterLink
            :to="{ name: 'faq' }"
            class="group mt-7 inline-flex items-center gap-2 text-sm font-medium text-[#EA580C] transition-colors hover:text-[#0F766E]"
          >
            Buka halaman FAQ

            <ArrowRight
              :size="17"
              class="transition-transform group-hover:translate-x-1"
              aria-hidden="true"
            />
          </RouterLink>
        </div>

        <div
          v-reveal="{
            direction: 'right',
            delay: 100,
          }"
        >
          <span
            class="border-l-4 border-[#0F766E] pl-3 text-sm font-medium uppercase tracking-[0.12em] text-[#0F766E]"
          >
            Kontak
          </span>

          <h2
            class="mt-5 text-3xl font-semibold text-government-800"
          >
            Hubungi Dinas Sosial.
          </h2>

          <p
            class="mt-5 max-w-lg text-base leading-8 text-government-500"
          >
            Gunakan saluran resmi Dinas Sosial Kota
            Mojokerto untuk memperoleh informasi
            lebih lanjut.
          </p>

          <RouterLink
            :to="{ name: 'contact' }"
            class="group mt-7 inline-flex items-center gap-2 text-sm font-medium text-[#EA580C] transition-colors hover:text-[#0F766E]"
          >
            Lihat informasi kontak

            <ArrowRight
              :size="17"
              class="transition-transform group-hover:translate-x-1"
              aria-hidden="true"
            />
          </RouterLink>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.hero-bg {
  animation: hero-zoom 16s ease-in-out infinite alternate;
  transform-origin: center;
}

@keyframes hero-zoom {
  from {
    transform: scale(1);
  }

  to {
    transform: scale(1.08);
  }
}

@media (prefers-reduced-motion: reduce) {
  .hero-bg {
    animation: none;
  }
}
</style>