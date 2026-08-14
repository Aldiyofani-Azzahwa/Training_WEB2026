<script setup lang="ts">
import { publicSite } from '@/config/publicSite'

withDefaults(
  defineProps<{
    footer?: boolean
  }>(),
  {
    footer: false,
  },
)
</script>

<template>
  <div
    :class="[
      'inline-flex min-w-0 items-center bg-transparent',
      footer
        ? 'w-full max-w-[270px]'
        : 'w-full max-w-[160px] sm:max-w-[175px]',
    ]"
  >
    <img
      :src="publicSite.logoPath"
      :alt="`${publicSite.name} - ${publicSite.fullName}`"
      :class="[
        'block h-auto w-full bg-transparent object-contain',
        footer
          ? 'max-h-[100px]'
          : 'max-h-[58px]',
      ]"
      width="2047"
      height="775"
      draggable="false"
    />
  </div>
</template>