<script setup lang="ts">
import {
  CreditCard,
  ShieldCheck,
  ShoppingBasket,
  Users,
} from '@lucide/vue'

import PageHeader from '@/components/landing/PageHeader.vue'

const points = [
  {
    icon: Users,
    title: 'Keluarga Penerima Manfaat',
    description:
      'Penerima bantuan harus tercatat dalam data penerima yang berlaku.',
  },
  {
    icon: CreditCard,
    title: 'Mekanisme Non-Tunai',
    description:
      'Penyaluran dilaksanakan melalui mekanisme pembayaran non-tunai.',
  },
  {
    icon: ShoppingBasket,
    title: 'Kebutuhan Pangan',
    description:
      'Bantuan digunakan sesuai ketentuan program bantuan pangan.',
  },
  {
    icon: ShieldCheck,
    title: 'Pelaksanaan Terpantau',
    description:
      'Proses penyaluran membutuhkan pencatatan dan validasi yang tertib.',
  },
]
</script>

<template>
  <div>
    <PageHeader
      eyebrow="Tentang BPNT"
      title="Bantuan pangan melalui mekanisme non-tunai."
      description="Informasi ringkas mengenai Bantuan Pangan Non Tunai dan kebutuhan pendataan dalam pelaksanaan program."
    />

    <section class="bg-white py-20 sm:py-24">
      <div
        class="mx-auto grid w-full max-w-7xl gap-14 px-5 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8"
      >
        <div>
          <span
            class="text-sm font-medium uppercase tracking-[0.12em] text-brand-600"
          >
            Pengertian
          </span>

          <h2
            class="mt-4 text-3xl font-semibold tracking-[-0.03em] text-government-800 sm:text-4xl"
          >
            Apa yang dimaksud dengan BPNT?
          </h2>

          <p
            class="mt-6 text-base leading-8 text-government-500"
          >
            Bantuan Pangan Non Tunai merupakan
            bantuan pangan yang disalurkan kepada
            Keluarga Penerima Manfaat melalui
            mekanisme pembayaran non-tunai untuk
            digunakan berdasarkan ketentuan
            program pemerintah.
          </p>

          <div
            class="mt-8 border-l-4 border-brand-500 pl-5"
          >
            <p
              class="text-sm leading-7 text-government-600"
            >
              Jadwal dan periode pelaksanaan
              mengikuti kebijakan pemerintah,
              bukan ditentukan melalui SIPBPNT.
            </p>
          </div>
        </div>

        <div class="grid gap-x-10 md:grid-cols-2">
          <article
            v-for="point in points"
            :key="point.title"
            class="flex gap-4 border-b border-government-200 py-7"
          >
            <div
              class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-brand-500"
            >
              <component
                :is="point.icon"
                :size="24"
                aria-hidden="true"
              />
            </div>

            <div>
              <h3
                class="font-medium text-government-800"
              >
                {{ point.title }}
              </h3>

              <p
                class="mt-2 text-sm leading-7 text-government-500"
              >
                {{ point.description }}
              </p>
            </div>
          </article>
        </div>
      </div>
    </section>
  </div>
</template>