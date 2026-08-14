<script setup lang="ts">
withDefaults(
  defineProps<{
    targetColor?: string
    accentColor?: string
    duration?: number
    heightClass?: string
  }>(),
  {
    targetColor: '#FFFFFF',
    accentColor: '#E8312D',
    duration: 38,
    heightClass:
      'h-20 sm:h-24 lg:h-28',
  },
)
</script>

<template>
  <div
    :class="[
      'pointer-events-none absolute inset-x-0 bottom-[-1px] z-20 overflow-hidden',
      heightClass,
    ]"
    :style="{
      backgroundColor: accentColor,
    }"
    aria-hidden="true"
  >
    <!-- Lapisan transparan -->
    <svg
      class="wave-move-right absolute inset-x-0 bottom-0 h-full w-[200%] max-w-none"
      :style="{
        animationDuration: `${duration + 8}s`,
      }"
      viewBox="0 0 2880 160"
      preserveAspectRatio="none"
    >
      <path
        :fill="targetColor"
        fill-opacity="0.38"
        d="
          M0 66
          C280 29 510 100 780 69
          C1025 41 1240 99 1440 70
          L1440 160
          L0 160
          Z

          M1440 66
          C1720 29 1950 100 2220 69
          C2465 41 2680 99 2880 70
          L2880 160
          L1440 160
          Z
        "
      />
    </svg>

    <!-- Lapisan utama -->
    <svg
      class="wave-move-left absolute inset-x-0 bottom-0 h-full w-[200%] max-w-none"
      :style="{
        animationDuration: `${duration}s`,
      }"
      viewBox="0 0 2880 160"
      preserveAspectRatio="none"
    >
      <path
        :fill="targetColor"
        d="
          M0 82
          C300 55 520 103 795 82
          C1038 64 1250 102 1440 83
          L1440 160
          L0 160
          Z

          M1440 82
          C1740 55 1960 103 2235 82
          C2478 64 2690 102 2880 83
          L2880 160
          L1440 160
          Z
        "
      />
    </svg>
  </div>
</template>