<script setup lang="ts">
withDefaults(
  defineProps<{
    backgroundColor?: string
    accentColor?: string
    duration?: number
  }>(),
  {
    backgroundColor: '#FFF8F3',
    accentColor: '#E8312D',
    duration: 40,
  },
)
</script>

<template>
  <div
    class="pointer-events-none absolute inset-x-0 top-0 h-16 -translate-y-[99%] overflow-hidden sm:h-20"
    aria-hidden="true"
  >
    <svg
      class="wave-move-right absolute inset-x-0 bottom-0 h-full w-[200%] max-w-none"
      :style="{
        animationDuration: `${duration + 8}s`,
      }"
      viewBox="0 0 2880 150"
      preserveAspectRatio="none"
    >
      <path
        :fill="accentColor"
        fill-opacity="0.2"
        d="
          M0 69
          C285 30 510 107 780 74
          C1024 45 1238 104 1440 75
          L1440 150
          L0 150
          Z

          M1440 69
          C1725 30 1950 107 2220 74
          C2464 45 2678 104 2880 75
          L2880 150
          L1440 150
          Z
        "
      />
    </svg>

    <svg
      class="wave-move-left absolute inset-x-0 bottom-0 h-[72%] w-[200%] max-w-none"
      :style="{
        animationDuration: `${duration}s`,
      }"
      viewBox="0 0 2880 150"
      preserveAspectRatio="none"
    >
      <path
        :fill="backgroundColor"
        d="
          M0 94
          C295 66 520 113 795 94
          C1038 77 1248 111 1440 96
          L1440 150
          L0 150
          Z

          M1440 94
          C1735 66 1960 113 2235 94
          C2478 77 2688 111 2880 96
          L2880 150
          L1440 150
          Z
        "
      />
    </svg>
  </div>
</template>