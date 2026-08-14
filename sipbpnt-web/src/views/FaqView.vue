<script setup lang="ts">
import { ref } from 'vue'
import { ChevronDown } from 'lucide-vue-next'

import PageHeader from '@/components/landing/PageHeader.vue'

const openFaqIndex = ref<number | null>(0)

const faqs = [
  {
    question:
      'Siapa yang dapat mengakses SIPBPNT?',
    answer:
      'SIPBPNT digunakan oleh petugas yang memiliki akun resmi, yaitu Admin Dinas Sosial, Manager, Surveyor, dan Kepala Dinas.',
  },
  {
    question:
      'Apakah KPM harus memiliki akun?',
    answer:
      'Tidak. KPM tidak perlu membuat akun karena pendataan dilakukan oleh surveyor saat pelaksanaan penyaluran.',
  },
  {
    question:
      'Bagaimana NIK KPM ditampilkan?',
    answer:
      'NIK tidak ditampilkan secara penuh pada halaman operasional. Bagian tengah NIK disamarkan dan akses dibatasi berdasarkan peran.',
  },
  {
    question:
      'Apakah SIPBPNT dapat digunakan melalui telepon genggam?',
    answer:
      'Ya. Antarmuka SIPBPNT dirancang responsif, khususnya untuk membantu surveyor bekerja di lapangan.',
  },
  {
    question:
      'Apakah periode BPNT ditentukan melalui SIPBPNT?',
    answer:
      'Tidak. Periode mengikuti jadwal dan kebijakan pemerintah. SIPBPNT digunakan untuk mencatat dan memantau pelaksanaannya.',
  },
]

function toggleFaq(index: number): void {
  openFaqIndex.value =
    openFaqIndex.value === index
      ? null
      : index
}
</script>

<template>
  <div>
    <PageHeader
      eyebrow="FAQ"
      title="Informasi yang sering ditanyakan."
      description="Penjelasan mengenai pengguna, keamanan data, pendataan KPM, dan penggunaan SIPBPNT."
    />

    <section class="bg-white py-20 sm:py-24">
      <div
        class="mx-auto w-full max-w-4xl px-5 sm:px-6 lg:px-8"
      >
        <div class="space-y-3">
          <article
            v-for="(faq, index) in faqs"
            :key="faq.question"
            class="overflow-hidden rounded-2xl border border-government-200 bg-white"
          >
            <h2>
              <button
                type="button"
                class="flex w-full items-center justify-between gap-5 px-5 py-5 text-left sm:px-6"
                :aria-expanded="
                  openFaqIndex === index
                "
                :aria-controls="`faq-${index}`"
                @click="toggleFaq(index)"
              >
                <span
                  class="text-sm font-medium leading-6 text-government-800 sm:text-base"
                >
                  {{ faq.question }}
                </span>

                <span
                  :class="[
                    'flex size-9 shrink-0 items-center justify-center rounded-xl transition-all duration-300',
                    openFaqIndex === index
                      ? 'rotate-180 bg-brand-500 text-white'
                      : 'bg-brand-50 text-brand-500',
                  ]"
                >
                  <ChevronDown
                    :size="18"
                    aria-hidden="true"
                  />
                </span>
              </button>
            </h2>

            <div
              :id="`faq-${index}`"
              :class="[
                'grid transition-all duration-300',
                openFaqIndex === index
                  ? 'grid-rows-[1fr] opacity-100'
                  : 'grid-rows-[0fr] opacity-0',
              ]"
            >
              <div class="overflow-hidden">
                <p
                  class="border-t border-government-100 px-5 pb-6 pt-5 text-sm leading-7 text-government-500 sm:px-6"
                >
                  {{ faq.answer }}
                </p>
              </div>
            </div>
          </article>
        </div>
      </div>
    </section>
  </div>
</template>