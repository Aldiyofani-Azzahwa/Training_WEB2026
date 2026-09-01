<script setup lang="ts">
import {
  Archive,
  Database,
  FileSpreadsheet,
  FileText,
  Monitor,
  ShieldCheck,
} from '@lucide/vue'

import PageHeader from '@/components/landing/PageHeader.vue'

const benefits = [
  {
    icon: Database,
    title: 'Pendataan lebih cepat',
    description:
      'Data BNBA dan KPM diproses secara terstruktur dalam satu sistem.',
  },
  {
    icon: Monitor,
    title: 'Monitoring lebih mudah',
    description:
      'Perkembangan transaksi dapat dipantau berdasarkan periode dan lokasi.',
  },
  {
    icon: FileSpreadsheet,
    title: 'Rekap otomatis',
    description:
      'Data transaksi tersusun tanpa perekapan ulang secara manual.',
  },
  {
    icon: FileText,
    title: 'Pelaporan lebih efisien',
    description:
      'Laporan dapat disiapkan dalam format yang dibutuhkan Dinas Sosial.',
  },
  {
    icon: Archive,
    title: 'Dokumentasi lebih tertata',
    description:
      'Foto, nominal, status, dan catatan pendataan tersimpan terpusat.',
  },
  {
    icon: ShieldCheck,
    title: 'Data lebih terlindungi',
    description:
      'Akses dan data sensitif dibatasi berdasarkan peran pengguna.',
  },
]
</script>

<template>
  <div>
    <PageHeader
      eyebrow="Manfaat"
      title="Mendukung pelaksanaan BPNT yang lebih teratur."
      description="SIPBPNT membantu mengurangi pekerjaan manual dan menyediakan informasi yang dibutuhkan setiap petugas."
    />

    <section class="bg-white py-20 sm:py-24">
      <div
        class="mx-auto grid w-full max-w-7xl gap-x-14 px-5 sm:px-6 md:grid-cols-2 lg:px-8"
      >
        <article
          v-for="benefit in benefits"
          :key="benefit.title"
          class="flex gap-5 border-b border-government-200 py-8"
        >
          <div
            class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-orange-500"
          >
            <component
              :is="benefit.icon"
              :size="25"
              aria-hidden="true"
            />
          </div>

          <div>
            <h2
              class="text-lg font-medium text-government-800"
            >
              {{ benefit.title }}
            </h2>

            <p
              class="mt-2 text-sm leading-7 text-government-500"
            >
              {{ benefit.description }}
            </p>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>