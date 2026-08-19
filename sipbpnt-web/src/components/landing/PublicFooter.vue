<script setup lang="ts">
import {
  Mail,
  MapPin,
  Phone,
} from '@lucide/vue'

import BrandLogo from './BrandLogo.vue'
import BatikCorner from './BatikCorner.vue'
import FooterWave from './FooterWave.vue'

import {
  revealDirective as vReveal,
} from '@/directives/reveal'

import {
  publicSite,
} from '@/config/publicSite'

const currentYear =
  new Date().getFullYear()
</script>

<template>
  <footer
    class="relative mt-20 bg-[#FFF8F3] text-government-700"
  >
    <FooterWave
      background-color="#FFF8F3"
      accent-color="#E8312D"
      :duration="40"
    />

    <div class="relative overflow-hidden">
      <BatikCorner
        position="bottom-right"
        color="#006855"
        :opacity="0.035"
        size-class="h-56 w-56 sm:h-72 sm:w-72"
      />

      <div
        class="relative z-10 mx-auto w-full max-w-7xl px-5 pb-7 pt-16 sm:px-6 sm:pt-20 lg:px-8"
      >
        <!-- Identitas -->
        <div
          v-reveal="{
            direction: 'up',
          }"
          class="border-b border-brand-200/70 pb-8"
        >
          <BrandLogo footer />

          <p
            class="mt-4 max-w-xl text-sm leading-7 text-government-500"
          >
            {{ publicSite.fullName }} milik
            {{ publicSite.agency }}.
          </p>
        </div>

        <!-- Informasi kontak -->
        <div
          class="grid gap-7 border-b border-brand-200/70 py-9 md:grid-cols-2 lg:grid-cols-[1.45fr_0.65fr_1.2fr] lg:gap-9"
        >
          <!-- Alamat -->
          <div
            v-reveal="{
              direction: 'left',
              delay: 80,
            }"
            class="flex min-w-0 gap-4"
          >
            <div
              class="flex size-11 shrink-0 items-center justify-center rounded-[14px] bg-white text-[#E8312D]"
            >
              <MapPin
                :size="21"
                aria-hidden="true"
              />
            </div>

            <div class="min-w-0">
              <strong
                class="block text-sm font-medium text-government-800"
              >
                Alamat
              </strong>

              <p
                class="mt-2 text-sm leading-7 text-government-500"
              >
                {{ publicSite.address }}
              </p>
            </div>
          </div>

          <!-- Telepon -->
          <a
            v-reveal="{
              direction: 'up',
              delay: 150,
            }"
            :href="publicSite.telephoneHref"
            class="group flex min-w-0 gap-4"
          >
            <div
              class="flex size-11 shrink-0 items-center justify-center rounded-[14px] bg-white text-[#E8312D] transition-colors group-hover:bg-[#E8312D] group-hover:text-white"
            >
              <Phone
                :size="21"
                aria-hidden="true"
              />
            </div>

            <div class="min-w-0">
              <strong
                class="block text-sm font-medium text-government-800"
              >
                Telepon
              </strong>

              <p
                class="mt-2 whitespace-nowrap text-sm text-government-500"
              >
                {{ publicSite.telephone }}
              </p>
            </div>
          </a>

          <!-- Email -->
          <a
            v-reveal="{
              direction: 'right',
              delay: 220,
            }"
            :href="publicSite.emailHref"
            class="group flex min-w-0 gap-4 lg:-ml-3"
          >
            <div
              class="flex size-11 shrink-0 items-center justify-center rounded-[14px] bg-white text-[#E8312D] transition-colors group-hover:bg-[#006855] group-hover:text-white"
            >
              <Mail
                :size="21"
                aria-hidden="true"
              />
            </div>

            <div class="min-w-0">
              <strong
                class="block text-sm font-medium text-government-800"
              >
                Email
              </strong>

              <p
                class="mt-2 text-[13px] leading-6 text-government-500 sm:text-sm lg:whitespace-nowrap"
              >
                {{ publicSite.email }}
              </p>
            </div>
          </a>
        </div>

        <div
          class="flex flex-col gap-3 py-6 text-xs text-government-500 sm:flex-row sm:items-center sm:justify-between"
        >
          <p>
            © {{ currentYear }}
            {{ publicSite.agency }}.
          </p>

          <p>
            Sistem Informasi Pemerintahan Kota
            Mojokerto
          </p>
        </div>
      </div>
    </div>
  </footer>
</template>