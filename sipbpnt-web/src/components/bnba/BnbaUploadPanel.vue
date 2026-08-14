<script setup lang="ts">
import { ref } from 'vue'
import {
  FileSpreadsheet,
  LoaderCircle,
  UploadCloud,
  X,
} from 'lucide-vue-next'

defineProps<{
  selectedFile: File | null
  uploadProgress: number
  isUploading: boolean
  canUpload: boolean
  hasSelectedPeriod: boolean
}>()

const emit = defineEmits<{
  selectFile: [file: File | null]
  upload: []
}>()

const inputRef =
  ref<HTMLInputElement | null>(null)

const isDragging = ref(false)

function openFilePicker(): void {
  if (inputRef.value) {
    inputRef.value.click()
  }
}

function handleFileInput(
  event: Event,
): void {
  const target =
    event.target as HTMLInputElement

  emit(
    'selectFile',
    target.files?.[0] ?? null,
  )
}

function handleDrop(
  event: DragEvent,
): void {
  isDragging.value = false

  emit(
    'selectFile',
    event.dataTransfer
      ?.files?.[0]
      ?? null,
  )
}

function clearFile(): void {
  emit('selectFile', null)

  if (inputRef.value) {
    inputRef.value.value = ''
  }
}

function formatFileSize(
  bytes: number,
): string {
  if (bytes < 1024) {
    return `${bytes} B`
  }

  if (bytes < 1024 * 1024) {
    return `${(
      bytes / 1024
    ).toFixed(1)} KB`
  }

  return `${(
    bytes / 1024 / 1024
  ).toFixed(2)} MB`
}
</script>

<template>
  <section
    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"
  >
    <div class="mb-5">
      <div
        class="mb-2 flex items-center gap-2 text-[#E8312D]"
      >
        <FileSpreadsheet
          :size="20"
          aria-hidden="true"
        />

        <span
          class="text-sm font-semibold"
        >
          File BNBA
        </span>
      </div>

      <h2
        class="text-lg font-bold text-slate-900"
      >
        Upload data BNBA
      </h2>

      <p
        class="mt-1 text-sm leading-6 text-slate-500"
      >
        Gunakan file Excel BNBA dengan struktur
        kolom yang sudah ditentukan.
      </p>
    </div>

    <input
      ref="inputRef"
      type="file"
      class="sr-only"
      accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
      :disabled="isUploading"
      @change="handleFileInput"
    >

    <button
      v-if="!selectedFile"
      type="button"
      class="flex min-h-52 w-full flex-col items-center justify-center rounded-2xl border-2 border-dashed px-6 py-8 text-center transition focus:outline-none focus:ring-4 focus:ring-[#E8312D]/10"
      :class="
        isDragging
          ? 'border-[#E8312D] bg-[#E8312D]/5'
          : 'border-slate-300 bg-slate-50 hover:border-[#E8312D]/60 hover:bg-[#E8312D]/[0.02]'
      "
      :disabled="isUploading"
      @click="openFilePicker"
      @dragenter.prevent="isDragging = true"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="handleDrop"
    >
      <span
        class="mb-4 flex size-14 items-center justify-center rounded-2xl bg-[#E8312D]/10 text-[#E8312D]"
      >
        <UploadCloud
          :size="28"
          aria-hidden="true"
        />
      </span>

      <span
        class="font-bold text-slate-800"
      >
        Pilih atau tarik file Excel ke sini
      </span>

      <span
        class="mt-2 text-sm text-slate-500"
      >
        Format .xlsx atau .xls, maksimal 10 MB
      </span>
    </button>

    <div
      v-else
      class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
    >
      <div
        class="flex items-start gap-4"
      >
        <div
          class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-[#006855]/10 text-[#006855]"
        >
          <FileSpreadsheet
            :size="24"
            aria-hidden="true"
          />
        </div>

        <div class="min-w-0 flex-1">
          <p
            class="truncate font-bold text-slate-900"
          >
            {{ selectedFile.name }}
          </p>

          <p
            class="mt-1 text-sm text-slate-500"
          >
            {{ formatFileSize(selectedFile.size) }}
          </p>
        </div>

        <button
          type="button"
          class="flex size-10 shrink-0 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-200 hover:text-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
          aria-label="Hapus file"
          :disabled="isUploading"
          @click="clearFile"
        >
          <X
            :size="20"
            aria-hidden="true"
          />
        </button>
      </div>

      <div
        v-if="isUploading || uploadProgress > 0"
        class="mt-4"
      >
        <div
          class="mb-2 flex items-center justify-between text-xs font-semibold"
        >
          <span class="text-slate-600">
            {{
              isUploading
                ? 'Mengunggah dan memproses...'
                : 'Upload selesai'
            }}
          </span>

          <span class="text-[#006855]">
            {{ uploadProgress }}%
          </span>
        </div>

        <div
          class="h-2 overflow-hidden rounded-full bg-slate-200"
        >
          <div
            class="h-full rounded-full bg-[#006855] transition-all duration-300"
            :style="{
              width: `${uploadProgress}%`,
            }"
          />
        </div>
      </div>
    </div>

    <div
      v-if="!hasSelectedPeriod"
      class="mt-4 rounded-xl bg-[#FFAF1C]/15 px-4 py-3 text-sm font-medium text-amber-900"
    >
      Pilih periode BPNT sebelum melakukan upload.
    </div>

    <button
      type="button"
      :disabled="!canUpload"
      class="mt-5 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#E8312D] px-5 text-sm font-bold text-white transition hover:bg-[#cb2724] focus:outline-none focus:ring-4 focus:ring-[#E8312D]/20 disabled:cursor-not-allowed disabled:opacity-50"
      @click="emit('upload')"
    >
      <LoaderCircle
        v-if="isUploading"
        :size="19"
        class="animate-spin"
        aria-hidden="true"
      />

      <UploadCloud
        v-else
        :size="19"
        aria-hidden="true"
      />

      {{
        isUploading
          ? 'Memproses BNBA...'
          : 'Upload dan Validasi'
      }}
    </button>
  </section>
</template>