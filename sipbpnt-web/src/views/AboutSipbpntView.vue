<script setup lang="ts">
import {
  Archive,
  ClipboardCheck,
  Database,
  FileText,
  Monitor,
  ShieldCheck,
} from '@lucide/vue'

import PageHeader from '@/components/landing/PageHeader.vue'

const functions = [
  {
    icon: Database,
    title: 'Pengelolaan BNBA',
    description:
      'Mengelola data penerima berdasarkan periode penyaluran.',
  },
  {
    icon: ClipboardCheck,
    title: 'Pendataan KPM',
    description:
      'Membantu surveyor memeriksa dan memperbarui status penerima.',
  },
  {
    icon: Monitor,
    title: 'Monitoring',
    description:
      'Menampilkan perkembangan transaksi dan tindak lanjut lapangan.',
  },
  {
    icon: FileText,
    title: 'Pelaporan',
    description:
      'Menyusun data menjadi laporan yang lebih terstruktur.',
  },
]
</script>

<template>
  <div>
    <PageHeader
      eyebrow="Tentang SIPBPNT"
      title="Satu sistem untuk mendukung proses BPNT."
      description="SIPBPNT menghubungkan proses pendataan, transaksi, validasi, monitoring, dan pelaporan dalam satu sistem."
    />

    <section class="bg-white py-20 sm:py-24">
      <div
        class="mx-auto grid w-full max-w-7xl gap-14 px-5 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8"
      >
        <div>
          <span
            class="text-sm font-medium uppercase tracking-[0.12em] text-orange-600"
          >
            Pengertian
          </span>

          <h2
            class="mt-4 text-3xl font-semibold tracking-[-0.03em] text-government-800 sm:text-4xl"
          >
            Apa itu SIPBPNT?
          </h2>

          <p
            class="mt-6 text-base leading-8 text-government-500"
          >
            SIPBPNT merupakan Sistem Informasi
            Pendataan dan Monitoring BPNT yang
            membantu Dinas Sosial Kota Mojokerto
            mengelola data penerima, transaksi,
            status penyaluran, validasi, dan
            laporan.
          </p>
        </div>

        <div class="grid gap-x-10 md:grid-cols-2">
          <article
            v-for="item in functions"
            :key="item.title"
            class="flex gap-4 border-b border-government-200 py-7"
          >
            <div
              class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-orange-50 text-orange-500"
            >
              <component
                :is="item.icon"
                :size="24"
                aria-hidden="true"
              />
            </div>

            <div>
              <h3
                class="font-medium text-government-800"
              >
                {{ item.title }}
              </h3>

              <p
                class="mt-2 text-sm leading-7 text-government-500"
              >
                {{ item.description }}
              </p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section class="bg-cream-100 py-20 sm:py-24">
      <div
        class="mx-auto grid w-full max-w-7xl items-center gap-14 px-5 sm:px-6 lg:grid-cols-2 lg:px-8"
      >
        <div>
          <span
            class="text-sm font-medium uppercase tracking-[0.12em] text-orange-600"
          >
            Latar Belakang
          </span>

          <h2
            class="mt-4 text-3xl font-semibold tracking-[-0.03em] text-government-800 sm:text-4xl"
          >
            Mengapa SIPBPNT dibangun?
          </h2>

          <p
            class="mt-6 text-base leading-8 text-government-500"
          >
            SIPBPNT dikembangkan agar proses
            pendataan, verifikasi, monitoring,
            dan pelaporan lebih efektif,
            terdokumentasi, dan mudah dipantau
            oleh Dinas Sosial Kota Mojokerto.
          </p>
        </div>

        <div class="space-y-6">
          <div
            class="flex gap-4 border-b border-orange-200 pb-6"
          >
            <Archive
              :size="26"
              class="shrink-0 text-orange-500"
              aria-hidden="true"
            />

            <div>
              <h3
                class="font-medium text-government-800"
              >
                Dokumentasi terpusat
              </h3>

              <p
                class="mt-1 text-sm leading-7 text-government-500"
              >
                Data transaksi dan pendataan
                tersimpan pada satu sistem.
              </p>
            </div>
          </div>

          <div
            class="flex gap-4 border-b border-orange-200 pb-6"
          >
            <Monitor
              :size="26"
              class="shrink-0 text-orange-500"
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
                Perkembangan dapat dipantau tanpa
                menunggu rekap manual.
              </p>
            </div>
          </div>

          <div class="flex gap-4">
            <ShieldCheck
              :size="26"
              class="shrink-0 text-orange-500"
              aria-hidden="true"
            />

            <div>
              <h3
                class="font-medium text-government-800"
              >
                Akses sesuai kewenangan
              </h3>

              <p
                class="mt-1 text-sm leading-7 text-government-500"
              >
                Pengguna memperoleh akses sesuai
                peran dan tugasnya.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>