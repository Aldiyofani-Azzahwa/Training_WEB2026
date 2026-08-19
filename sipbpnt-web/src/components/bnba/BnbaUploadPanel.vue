<script setup lang="ts">
import {
  computed,
  ref,
} from 'vue'

import {
  FileSpreadsheet,
  LoaderCircle,
  UploadCloud,
  X,
} from '@lucide/vue'

const props = defineProps<{
  selectedFile: File | null
  uploadProgress: number
  isUploading: boolean
  canUpload: boolean
}>()

const emit = defineEmits<{
  selectFile: [
    file: File | null,
  ]

  upload: []
}>()

const inputRef =
  ref<HTMLInputElement | null>(
    null,
  )

const isDragging =
  ref(false)

const selectedFileName =
  computed(
    () =>
      props.selectedFile
        ?.name
      ?? '',
  )

const selectedFileSize =
  computed(() => {
    if (
      props.selectedFile
      === null
    ) {
      return ''
    }

    return formatFileSize(
      props.selectedFile.size,
    )
  })

function openFilePicker():
  void {
  if (
    props.isUploading
  ) {
    return
  }

  inputRef.value
    ?.click()
}

function handleFileInput(
  event: Event,
): void {
  const input =
    event.currentTarget

  if (
    !(
      input
      instanceof HTMLInputElement
    )
  ) {
    return
  }

  const file =
    input.files
      ?.item(0)
    ?? null

  emit(
    'selectFile',
    file,
  )
}

function handleDrop(
  event: DragEvent,
): void {
  isDragging.value =
    false

  if (
    props.isUploading
  ) {
    return
  }

  const file =
    event.dataTransfer
      ?.files
      .item(0)
    ?? null

  emit(
    'selectFile',
    file,
  )
}

function clearFile():
  void {
  if (
    props.isUploading
  ) {
    return
  }

  emit(
    'selectFile',
    null,
  )

  if (
    inputRef.value
    !== null
  ) {
    inputRef.value.value =
      ''
  }
}

function formatFileSize(
  bytes: number,
): string {
  if (
    bytes < 1024
  ) {
    return `${bytes} B`
  }

  if (
    bytes
    <
    1024 * 1024
  ) {
    return `${
      (
        bytes
        / 1024
      ).toFixed(1)
    } KB`
  }

  return `${
    (
      bytes
      / 1024
      / 1024
    ).toFixed(2)
  } MB`
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
        Upload Data BNBA
      </h2>

      <p
        class="mt-1 text-sm leading-6 text-slate-500"
      >
        Gunakan file Excel BNBA dengan
        struktur kolom yang sudah ditentukan.
      </p>
    </div>

    <input
      ref="inputRef"
      type="file"
      class="sr-only"
      accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
      :disabled="
        isUploading
      "
      @change="
        handleFileInput
      "
    >

    <button
      v-if="
        !selectedFile
      "
      type="button"
      :disabled="
        isUploading
      "
      class="flex min-h-52 w-full flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-8 text-center transition hover:border-[#E8312D]/60 disabled:cursor-not-allowed disabled:opacity-50"
      :class="
        isDragging
          ? 'border-[#E8312D] bg-[#E8312D]/5'
          : ''
      "
      @click="
        openFilePicker
      "
      @dragenter.prevent="
        isDragging = true
      "
      @dragover.prevent="
        isDragging = true
      "
      @dragleave.prevent="
        isDragging = false
      "
      @drop.prevent="
        handleDrop
      "
    >
      <span
        class="flex size-14 items-center justify-center rounded-2xl bg-[#E8312D]/10 text-[#E8312D]"
      >
        <UploadCloud
          :size="28"
          aria-hidden="true"
        />
      </span>

      <strong
        class="mt-4 text-slate-800"
      >
        Pilih atau tarik file Excel ke sini
      </strong>

      <span
        class="mt-2 text-sm text-slate-500"
      >
        Format .xlsx atau .xls,
        maksimal 10 MB
      </span>
    </button>

    <div
      v-else
      class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
    >
      <div
        class="flex items-center gap-4"
      >
        <div
          class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-[#006855]/10 text-[#006855]"
        >
          <FileSpreadsheet
            :size="24"
            aria-hidden="true"
          />
        </div>

        <div
          class="min-w-0 flex-1"
        >
          <strong
            class="block truncate text-slate-900"
          >
            {{ selectedFileName }}
          </strong>

          <small
            class="mt-1 block text-slate-500"
          >
            {{ selectedFileSize }}
          </small>
        </div>

        <button
          type="button"
          :disabled="
            isUploading
          "
          class="flex size-10 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-200 disabled:opacity-50"
          aria-label="Hapus file"
          @click="
            clearFile
          "
        >
          <X
            :size="20"
            aria-hidden="true"
          />
        </button>
      </div>

      <div
        v-if="
          isUploading
          ||
          uploadProgress > 0
        "
        class="mt-4"
      >
        <div
          class="mb-2 flex justify-between text-xs font-semibold text-slate-500"
        >
          <span>
            {{
              isUploading
                ? 'Mengunggah dan memproses...'
                : 'Upload selesai'
            }}
          </span>

          <span>
            {{ uploadProgress }}%
          </span>
        </div>

        <div
          class="h-2 overflow-hidden rounded-full bg-slate-200"
        >
          <div
            class="h-full rounded-full bg-[#006855] transition-all"
            :style="{
              width:
                `${uploadProgress}%`,
            }"
          />
        </div>
      </div>
    </div>

    <button
      type="button"
      :disabled="
        !canUpload
      "
      class="mt-5 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#E8312D] px-5 text-sm font-bold text-white transition hover:bg-[#cb2724] disabled:cursor-not-allowed disabled:opacity-50"
      @click="
        emit('upload')
      "
    >
      <LoaderCircle
        v-if="
          isUploading
        "
        :size="19"
        class="animate-spin"
      />

      <UploadCloud
        v-else
        :size="19"
      />

      {{
        isUploading
          ? 'Memproses BNBA...'
          : 'Upload dan Validasi'
      }}
    </button>
  </section>
</template>