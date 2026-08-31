import {
  mount,
} from '@vue/test-utils'

import {
  describe,
  expect,
  it,
} from 'vitest'

import KtpOcrScanner from '@/components/surveyor/KtpOcrScanner.vue'

describe(
  'KTP OCR scanner',
  () => {
    it(
      'starts in photo mode and provides a manual NIK fallback',
      async () => {
        const wrapper =
          mount(
            KtpOcrScanner,
          )

        expect(
          wrapper
            .find(
              '[data-testid="start-ocr-scanner"]',
            )
            .exists(),
        ).toBe(true)

        expect(
          wrapper.text(),
        ).toContain(
          'Foto KTP & Baca NIK',
        )

        expect(
          wrapper.text(),
        ).toContain(
          'Buka Kamera KTP',
        )

        await wrapper
          .get(
            '[data-testid="use-manual-nik"]',
          )
          .trigger('click')

        expect(
          wrapper.emitted(
            'manual',
          ),
        ).toHaveLength(1)
      },
    )
  },
)