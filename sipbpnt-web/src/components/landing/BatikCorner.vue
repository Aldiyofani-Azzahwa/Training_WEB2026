<script setup lang="ts">
import { computed } from 'vue'

type OrnamentPosition =
  | 'top-left'
  | 'top-right'
  | 'bottom-left'
  | 'bottom-right'

const props = withDefaults(
  defineProps<{
    position?: OrnamentPosition
    color?: string
    opacity?: number
    sizeClass?: string
  }>(),
  {
    position: 'top-right',
    color: '#E8312D',
    opacity: 0.045,
    sizeClass:
      'h-52 w-52 sm:h-64 sm:w-64',
  },
)

const positionClass = computed(() => {
  const positions: Record<
    OrnamentPosition,
    string
  > = {
    'top-left':
      'left-0 top-0 -translate-x-[18%] -translate-y-[18%]',

    'top-right':
      'right-0 top-0 translate-x-[18%] -translate-y-[18%]',

    'bottom-left':
      'bottom-0 left-0 -translate-x-[18%] translate-y-[18%]',

    'bottom-right':
      'bottom-0 right-0 translate-x-[18%] translate-y-[18%]',
  }

  return positions[props.position]
})
</script>

<template>
  <svg
    viewBox="0 0 360 360"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    :class="[
      'pointer-events-none absolute',
      positionClass,
      sizeClass,
    ]"
    :style="{
      opacity,
    }"
    aria-hidden="true"
  >
    <g
      :stroke="color"
      stroke-width="5"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <!-- Sulur utama -->
      <path
        d="
          M20 330
          C74 292 81 229 132 197
          C177 169 231 176 260 130
          C281 96 277 61 324 25
        "
      />

      <path
        d="
          M44 349
          C98 315 112 263 161 236
          C208 210 251 216 286 174
          C312 143 313 112 347 81
        "
        opacity="0.72"
      />

      <!-- Daun kiri -->
      <path
        d="
          M96 238
          C56 235 37 210 38 174
          C75 174 103 193 112 223
          C109 230 104 235 96 238Z
        "
      />

      <path
        d="
          M140 190
          C103 176 92 147 103 116
          C137 128 158 151 158 180
          C153 186 147 189 140 190Z
        "
      />

      <!-- Daun kanan -->
      <path
        d="
          M188 177
          C192 137 217 116 254 111
          C252 148 230 174 200 187
          C194 185 190 182 188 177Z
        "
      />

      <path
        d="
          M257 130
          C263 91 289 69 327 66
          C324 102 303 128 272 142
          C265 140 260 136 257 130Z
        "
      />

      <!-- Isen batik sederhana -->
      <path
        d="M61 286L88 266L111 283L137 260"
        opacity="0.65"
      />

      <path
        d="M164 238L190 219L216 235L241 214"
        opacity="0.65"
      />

      <path
        d="M235 188L260 168L285 184L310 162"
        opacity="0.65"
      />

      <path
        d="
          M75 318
          C83 303 95 297 111 300
        "
        opacity="0.55"
      />

      <path
        d="
          M184 265
          C193 251 205 246 220 250
        "
        opacity="0.55"
      />
    </g>
  </svg>
</template>