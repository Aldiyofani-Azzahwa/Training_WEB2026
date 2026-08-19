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
  Landmark,
  Monitor,
  Quote,
  Store,
  Users,
} from '@lucide/vue'

import BatikCorner from '@/components/landing/BatikCorner.vue'
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
      class="relative isolate flex min-h-[760px] items-center overflow-hidden bg-[#E8312D] pb-28 pt-32 sm:min-h-[800px] sm:pb-32 sm:pt-36 lg:min-h-[820px]"
    >
      <!-- Foto kantor -->
      <div
        class="absolute inset-0 -z-30 bg-cover bg-center bg-no-repeat"
        :style="{
          backgroundImage:
            `url('/images/kantor.jpg')`,
          backgroundPosition:
            'center 42%',
        }"
        aria-hidden="true"
      />

      <!-- Overlay merah transparan -->
      <div
        class="absolute inset-0 -z-20 bg-[linear-gradient(90deg,rgba(232,49,45,0.72)_0%,rgba(232,49,45,0.52)_48%,rgba(232,49,45,0.22)_100%)]"
        aria-hidden="true"
      />

      <div
        class="absolute inset-0 -z-10 bg-government-900/10"
        aria-hidden="true"
      />

      <div
        class="mx-auto w-full max-w-7xl px-5 sm:px-6 lg:px-8"
      >
        <div class="max-w-3xl">
          <div
            v-reveal="{
              direction: 'left',
            }"
            class="inline-flex items-center gap-3 border-l-4 border-[#FFAF1C] pl-3 text-xs font-medium uppercase tracking-[0.13em] text-white"
          >
            <Landmark
              :size="17"
              aria-hidden="true"
            />

            Dinas Sosial Kota Mojokerto
          </div>

          <h1
            v-reveal="{
              direction: 'left',
              delay: 100,
            }"
            class="mt-7 text-[42px] font-semibold leading-[1.12] tracking-[-0.04em] text-white sm:text-5xl lg:text-[64px]"
          >
            Sistem Informasi Pendataan dan
            Monitoring BPNT
          </h1>

          <p
            v-reveal="{
              direction: 'up',
              delay: 190,
            }"
            class="mt-6 max-w-2xl text-base leading-8 text-white/90 sm:text-lg"
          >
            Sistem resmi yang membantu proses
            pendataan KPM, pencatatan transaksi,
            validasi, monitoring, dan pelaporan
            penyaluran Bantuan Pangan Non Tunai
            di Kota Mojokerto.
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
              class="inline-flex min-h-[52px] items-center justify-center gap-2 rounded-[14px] bg-white px-6 text-sm font-semibold text-[#E8312D] transition-all duration-200 hover:-translate-y-0.5 hover:bg-[#FFAF1C] hover:text-government-900"
            >
              Masuk Sistem

              <ArrowRight
                :size="18"
                class="text-[#006855]"
                aria-hidden="true"
              />
            </RouterLink>

            <RouterLink
              :to="{
                name: 'about-sipbpnt',
              }"
              class="inline-flex min-h-[52px] items-center justify-center gap-2 rounded-[14px] border border-white/75 bg-transparent px-6 text-sm font-medium text-white transition-colors hover:border-white hover:bg-white hover:text-[#E8312D]"
            >
              Pelajari SIPBPNT

              <ArrowRight
                :size="18"
                aria-hidden="true"
              />
            </RouterLink>
          </div>

          <div
            v-reveal="{
              direction: 'up',
              delay: 360,
            }"
            class="mt-8 flex flex-col gap-3 border-t border-white/30 pt-6 text-sm text-white/90 sm:flex-row sm:flex-wrap sm:gap-6"
          >
            <div class="flex items-center gap-2">
              <Check
                :size="18"
                class="text-[#FFAF1C]"
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
                class="text-[#FFAF1C]"
                aria-hidden="true"
              />

              Data terintegrasi
            </div>
          </div>
        </div>
      </div>

      <SectionWave
        target-color="#FFFFFF"
        accent-color="#E8312D"
        :duration="38"
      />
    </section>

    <!-- Tentang BPNT -->
    <section
      class="relative overflow-hidden bg-white py-24 sm:py-28"
    >
      <BatikCorner
        position="top-right"
        color="#E8312D"
        :opacity="0.04"
      />

      <div
        class="relative mx-auto grid w-full max-w-7xl items-center gap-14 px-5 sm:px-6 lg:grid-cols-2 lg:gap-20 lg:px-8"
      >
        <div
          v-reveal="{
            direction: 'left',
          }"
        >
          <span
            class="border-l-4 border-brand-500 pl-3 text-sm font-medium uppercase tracking-[0.12em] text-brand-600"
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
            class="group mt-7 inline-flex items-center gap-2 text-sm font-medium text-brand-600 transition-colors hover:text-government-green-500"
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
            class="border-l-2 border-brand-500 pl-5"
          >
            <Users
              :size="29"
              class="text-brand-500"
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
            class="border-l-2 border-[#FFAF1C] pl-5"
          >
            <Store
              :size="29"
              class="text-[#006855]"
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
      class="relative overflow-hidden bg-cream-100 py-24 sm:py-28"
    >
      <BatikCorner
        position="bottom-left"
        color="#006855"
        :opacity="0.035"
      />

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
            class="flex w-full max-w-sm flex-col items-center border-y border-brand-200 py-12 text-center"
          >
            <div
              class="flex size-24 items-center justify-center rounded-full bg-brand-500 text-white"
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
              class="my-3 h-7 w-px bg-brand-300"
              aria-hidden="true"
            />

            <span
              class="text-sm font-medium text-government-800"
            >
              Monitoring
            </span>

            <span
              class="my-3 h-7 w-px bg-brand-300"
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
            class="border-l-4 border-brand-500 pl-3 text-sm font-medium uppercase tracking-[0.12em] text-brand-600"
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
            class="group mt-7 inline-flex items-center gap-2 text-sm font-medium text-brand-600 transition-colors hover:text-government-green-500"
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
      class="relative overflow-hidden bg-white py-24 sm:py-28"
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
            class="border-l-4 border-brand-500 pl-3 text-sm font-medium uppercase tracking-[0.12em] text-brand-600"
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
              class="shrink-0 text-brand-500"
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
              class="shrink-0 text-[#006855]"
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
              class="shrink-0 text-[#FFAF1C]"
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
      class="relative overflow-hidden bg-cream-100 py-24 sm:py-28"
    >
      <BatikCorner
        position="top-right"
        color="#FFAF1C"
        :opacity="0.045"
      />

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
            class="border-l-4 border-brand-500 pl-3 text-sm font-medium uppercase tracking-[0.12em] text-brand-600"
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
            class="flex gap-5 border-b border-brand-200/70 py-7"
          >
            <div
              class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-white text-brand-500"
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
          class="group mt-9 inline-flex items-center gap-2 text-sm font-medium text-brand-600 transition-colors hover:text-government-green-500"
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
      class="relative overflow-hidden bg-white py-24 sm:py-28"
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
            class="text-sm font-medium uppercase tracking-[0.12em] text-brand-600"
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
          class="mt-12 border-y border-government-200 py-9 sm:px-8"
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
            >
              <Quote
                :size="38"
                class="text-brand-200"
                aria-hidden="true"
              />

              <p
                class="mt-5 max-w-3xl text-lg leading-9 text-government-700 sm:text-xl"
              >
                “{{ currentTestimonial.quote }}”
              </p>

              <div
                class="mt-7 flex items-center gap-4"
              >
                <div
                  class="flex size-11 items-center justify-center rounded-full bg-[#006855] text-sm font-medium text-white"
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
            class="mt-8 flex items-center justify-between"
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
                    ? 'w-8 bg-brand-500'
                    : 'w-2.5 bg-government-300',
                ]"
                @click="selectTestimonial(index)"
              />
            </div>

            <div class="flex gap-2">
              <button
                type="button"
                class="flex size-10 items-center justify-center rounded-full border border-government-200 text-government-600 transition-colors hover:border-brand-300 hover:text-brand-600"
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
                class="flex size-10 items-center justify-center rounded-full border border-government-200 text-government-600 transition-colors hover:border-brand-300 hover:text-brand-600"
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
      class="relative overflow-hidden bg-cream-100 py-24 sm:py-28"
    >
      <BatikCorner
        position="bottom-left"
        color="#006855"
        :opacity="0.035"
      />

      <div
        class="relative mx-auto grid w-full max-w-7xl gap-12 px-5 sm:px-6 lg:grid-cols-2 lg:gap-20 lg:px-8"
      >
        <div
          v-reveal="{
            direction: 'left',
          }"
        >
          <span
            class="border-l-4 border-brand-500 pl-3 text-sm font-medium uppercase tracking-[0.12em] text-brand-600"
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
            class="group mt-7 inline-flex items-center gap-2 text-sm font-medium text-brand-600 transition-colors hover:text-government-green-500"
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
            class="border-l-4 border-[#006855] pl-3 text-sm font-medium uppercase tracking-[0.12em] text-[#006855]"
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
            class="group mt-7 inline-flex items-center gap-2 text-sm font-medium text-brand-600 transition-colors hover:text-government-green-500"
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