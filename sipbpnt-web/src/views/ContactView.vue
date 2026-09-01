<script setup lang="ts">
import {
  ExternalLink,
  Mail,
  MapPin,
  Phone,
} from '@lucide/vue'

import PageHeader from '@/components/landing/PageHeader.vue'

import { publicSite } from '@/config/publicSite'

const googleMapsUrl =
  'https://www.google.com/maps/search/?api=1&query=Dinas+Sosial+Kota+Mojokerto'
</script>

<template>
  <div>
    <PageHeader
      eyebrow="Kontak"
      title="Hubungi Dinas Sosial Kota Mojokerto."
      description="Gunakan saluran resmi untuk memperoleh informasi mengenai layanan dan pelaksanaan program."
      wave-target-color="#FFFFFF"
      wave-primary-color="#F96D27"
      wave-secondary-color="#FFBA45"
    />

    <section class="bg-white py-20 sm:py-24">
      <div
        class="mx-auto w-full max-w-7xl px-5 sm:px-6 lg:px-8"
      >
        <div
          class="grid gap-14 lg:grid-cols-[0.82fr_1.18fr] lg:items-start lg:gap-16"
        >
          <!-- Informasi kontak -->
          <div>
            <span
              class="text-sm font-medium uppercase tracking-[0.12em] text-orange-600"
            >
              Informasi Kontak
            </span>

            <h2
              class="mt-4 text-3xl font-semibold tracking-[-0.03em] text-government-800 sm:text-4xl"
            >
              Saluran resmi pelayanan.
            </h2>

            <p
              class="mt-5 max-w-lg text-base leading-8 text-government-500"
            >
              Hubungi Dinas Sosial Kota Mojokerto
              melalui alamat, nomor telepon, atau
              email resmi berikut.
            </p>

            <div class="mt-9">
              <div
                class="flex gap-4 border-b border-government-200 py-6"
              >
                <div
                  class="flex size-11 shrink-0 items-center justify-center rounded-[14px] bg-orange-50 text-orange-500"
                >
                  <MapPin
                    :size="22"
                    aria-hidden="true"
                  />
                </div>

                <div>
                  <span
                    class="text-xs font-medium uppercase tracking-[0.08em] text-government-400"
                  >
                    Alamat
                  </span>

                  <p
                    class="mt-2 text-sm leading-7 text-government-700"
                  >
                    {{ publicSite.address }}
                  </p>
                </div>
              </div>

              <a
                :href="publicSite.telephoneHref"
                class="group flex gap-4 border-b border-government-200 py-6"
              >
                <div
                  class="flex size-11 shrink-0 items-center justify-center rounded-[14px] bg-orange-50 text-orange-500 transition-colors group-hover:bg-orange-500 group-hover:text-white"
                >
                  <Phone
                    :size="22"
                    aria-hidden="true"
                  />
                </div>

                <div>
                  <span
                    class="text-xs font-medium uppercase tracking-[0.08em] text-government-400"
                  >
                    Telepon
                  </span>

                  <p
                    class="mt-2 text-sm text-government-700"
                  >
                    {{ publicSite.telephone }}
                  </p>
                </div>
              </a>

              <a
                :href="publicSite.emailHref"
                class="group flex gap-4 py-6"
              >
                <div
                  class="flex size-11 shrink-0 items-center justify-center rounded-[14px] bg-orange-50 text-orange-500 transition-colors group-hover:bg-orange-500 group-hover:text-white"
                >
                  <Mail
                    :size="22"
                    aria-hidden="true"
                  />
                </div>

                <div>
                  <span
                    class="text-xs font-medium uppercase tracking-[0.08em] text-government-400"
                  >
                    Email
                  </span>

                  <p
                    class="mt-2 break-all text-sm text-government-700"
                  >
                    {{ publicSite.email }}
                  </p>
                </div>
              </a>
            </div>
          </div>

          <!-- Google Maps -->
          <div>
            <div
              class="overflow-hidden rounded-2xl border border-government-200 bg-government-100"
            >
              <div
                class="relative aspect-[4/3] w-full sm:aspect-[16/10]"
              >
                <iframe
                  src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3955.9441964651564!2d112.43888887500172!3d-7.471414892540275!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e780d91d9f3f9f9%3A0xe8d898fb12ba79de!2sDinas%20Sosial%20Kota%20Mojokerto!5e0!3m2!1sid!2sid!4v1785893184984!5m2!1sid!2sid"
                  title="Peta lokasi Dinas Sosial Kota Mojokerto"
                  class="absolute inset-0 h-full w-full"
                  style="border: 0"
                  loading="lazy"
                  allowfullscreen
                  referrerpolicy="strict-origin-when-cross-origin"
                />
              </div>
            </div>

            <div
              class="flex flex-col gap-4 border-x border-b border-government-200 bg-cream-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between"
            >
              <div class="flex items-start gap-3">
                <MapPin
                  :size="21"
                  class="mt-1 shrink-0 text-orange-500"
                  aria-hidden="true"
                />

                <div>
                  <h3
                    class="text-sm font-medium text-government-800"
                  >
                    Dinas Sosial Kota Mojokerto
                  </h3>

                  <p
                    class="mt-1 text-xs leading-6 text-government-500"
                  >
                    Jl. Benteng Pancasila No. 25,
                    Kota Mojokerto.
                  </p>
                </div>
              </div>

              <a
                :href="googleMapsUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-[14px] bg-orange-500 px-5 text-sm font-medium text-white transition-colors hover:bg-orange-600"
              >
                Buka di Google Maps

                <ExternalLink
                  :size="17"
                  aria-hidden="true"
                />
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>