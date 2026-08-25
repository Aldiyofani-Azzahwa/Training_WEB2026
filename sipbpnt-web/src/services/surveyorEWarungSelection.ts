import {
  computed,
  readonly,
  ref,
} from 'vue'

import type {
  SurveyorEWarung,
} from '@/types/surveyorWorkspace'

const STORAGE_KEY =
  'sipbpnt.surveyor.e-warung.v1'

interface StoredSelection {
  surveyor_id: number
  e_warung_id: number
}

const activeEWarungs =
  ref<SurveyorEWarung[]>([])

const selectedEWarung =
  ref<SurveyorEWarung | null>(null)

const hasSelectedEWarung =
  computed(
    () => selectedEWarung.value !== null,
  )

function browserStorage(): Storage | null {
  if (typeof window === 'undefined') {
    return null
  }

  return window.localStorage
}

function readStoredSelection(): StoredSelection | null {
  const storage =
    browserStorage()

  if (!storage) {
    return null
  }

  try {
    const rawValue =
      storage.getItem(
        STORAGE_KEY,
      )

    if (!rawValue) {
      return null
    }

    const parsed =
      JSON.parse(
        rawValue,
      ) as Partial<StoredSelection>

    if (
      !Number.isInteger(
        parsed.surveyor_id,
      )
      ||
      !Number.isInteger(
        parsed.e_warung_id,
      )
    ) {
      storage.removeItem(
        STORAGE_KEY,
      )

      return null
    }

    return {
      surveyor_id:
        Number(
          parsed.surveyor_id,
        ),

      e_warung_id:
        Number(
          parsed.e_warung_id,
        ),
    }
  } catch {
    storage.removeItem(
      STORAGE_KEY,
    )

    return null
  }
}

function persistSelection(
  surveyorId: number,
  eWarungId: number,
): void {
  browserStorage()?.setItem(
    STORAGE_KEY,

    JSON.stringify({
      surveyor_id:
        surveyorId,

      e_warung_id:
        eWarungId,
    } satisfies StoredSelection),
  )
}

function clearSelectedEWarung(): void {
  selectedEWarung.value =
    null

  browserStorage()?.removeItem(
    STORAGE_KEY,
  )
}

function synchronizeEWarungs(
  surveyorId: number,
  eWarungs: SurveyorEWarung[],
): SurveyorEWarung | null {
  /*
   * E-Warung nonaktif selalu dibuang,
   * termasuk jika server tidak sengaja
   * mengirimkannya.
   */
  activeEWarungs.value =
    eWarungs.filter(
      (eWarung) =>
        eWarung.is_active,
    )

  const stored =
    readStoredSelection()

  /*
   * Pilihan tidak boleh berpindah
   * otomatis ke akun Surveyor lain.
   */
  if (
    !stored
    ||
    stored.surveyor_id
      !== surveyorId
  ) {
    clearSelectedEWarung()

    return null
  }

  const activeSelection =
    activeEWarungs.value.find(
      (eWarung) =>
        eWarung.id
        === stored.e_warung_id,
    )
    ?? null

  /*
   * Jika Admin menonaktifkan E-Warung,
   * E-Warung tidak ada lagi dalam daftar
   * aktif dan pilihan lama dihapus.
   */
  if (!activeSelection) {
    clearSelectedEWarung()

    return null
  }

  selectedEWarung.value =
    activeSelection

  return activeSelection
}

function selectEWarung(
  surveyorId: number,
  eWarungId: number,
): SurveyorEWarung | null {
  const selection =
    activeEWarungs.value.find(
      (eWarung) =>
        eWarung.id === eWarungId
        &&
        eWarung.is_active,
    )
    ?? null

  if (!selection) {
    clearSelectedEWarung()

    return null
  }

  selectedEWarung.value =
    selection

  persistSelection(
    surveyorId,
    selection.id,
  )

  return selection
}

export function useSurveyorEWarungSelection() {
  return {
    activeEWarungs:
      readonly(
        activeEWarungs,
      ),

    selectedEWarung:
      readonly(
        selectedEWarung,
      ),

    hasSelectedEWarung,

    synchronizeEWarungs,
    selectEWarung,
    clearSelectedEWarung,
  }
}