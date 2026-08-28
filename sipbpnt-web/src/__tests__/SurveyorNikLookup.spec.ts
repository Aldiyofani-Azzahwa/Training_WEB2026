import {
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest'

import {
  flushPromises,
  mount,
} from '@vue/test-utils'

import ScanKtpView
  from '@/views/surveyor/ScanKtpView.vue'

import {
  surveyorWorkspaceService,
} from '@/services/surveyorWorkspaceService'

import type {
  SurveyorEWarung,
  SurveyorNikLookupResult,
  SurveyorWorkspaceContext,
} from '@/types/surveyorWorkspace'

vi.mock(
  '@/services/surveyorWorkspaceService',
  () => ({
    surveyorWorkspaceService: {
      getContext:
        vi.fn<
          () => Promise<SurveyorWorkspaceContext>
        >(),

      getActiveEWarungs:
        vi.fn<
          () => Promise<SurveyorEWarung[]>
        >(),

      lookupNik:
        vi.fn<
          (
            nik: string,
          ) => Promise<SurveyorNikLookupResult>
        >(),
    },
  }),
)

const getContextMock =
  vi.mocked(
    surveyorWorkspaceService
      .getContext,
  )

const getActiveEWarungsMock =
  vi.mocked(
    surveyorWorkspaceService
      .getActiveEWarungs,
  )

const lookupNikMock =
  vi.mocked(
    surveyorWorkspaceService
      .lookupNik,
  )

const workspaceContext:
  SurveyorWorkspaceContext = {
    surveyor: {
      id:
        10,

      name:
        'Andi Saputra',

      username:
        'andi.surveyor',
    },

    period: {
      id:
        5,

      code:
        'BPNT-2026-08',

      name:
        'BPNT Agustus 2026',

      year:
        2026,
    },

    assignment: {
      id:
        20,

      kecamatan: {
        id:
          3,

        name:
          'Kranggan',
      },

      kelurahan: {
        id:
          15,

        name:
          'Jagalan',
      },
    },

    kpm_count:
      245,
  }

const insideLookupResult:
  SurveyorNikLookupResult = {
    participant: {
      id:
        101,

      kpm: {
        id:
          201,

        nik:
          '3576********0001',

        full_name:
          'Budi Santoso',

        birth_place:
          'Mojokerto',

        birth_date:
          '1975-01-01',

        address:
          'Jalan Merdeka Nomor 1',

        rt:
          '001',

        rw:
          '002',
      },

      wilayah: {
        kelurahan: {
          id:
            15,

          name:
            'Jagalan',
        },

        kecamatan: {
          id:
            3,

          name:
            'Kranggan',
        },
      },

      saldo_bpnt:
        200000,
    },

    scope: {
      outside_assignment:
        false,

      label:
        'KPM Wilayah Jagalan',

      surveyor_kelurahan: {
        id:
          15,

        name:
          'Jagalan',
      },
    },
  }

const outsideLookupResult:
  SurveyorNikLookupResult = {
    ...insideLookupResult,

    participant: {
      ...insideLookupResult
        .participant,

      wilayah: {
        kelurahan: {
          id:
            14,

          name:
            'Miji',
        },

        kecamatan: {
          id:
            3,

          name:
            'Kranggan',
        },
      },
    },

    scope: {
      outside_assignment:
        true,

      label:
        'KPM Luar Wilayah',

      surveyor_kelurahan: {
        id:
          15,

        name:
          'Jagalan',
      },
    },
  }

describe(
  'Surveyor Exact NIK Lookup',
  () => {
    beforeEach(() => {
      vi.clearAllMocks()

      getContextMock
        .mockResolvedValue(
          workspaceContext,
        )

      getActiveEWarungsMock
        .mockResolvedValue([])
    })

    function mountLookupView() {
      return mount(
        ScanKtpView,
        {
          global: {
            stubs: {
              RouterLink:
                true,
            },
          },
        },
      )
    }

    it(
      'normalizes input and performs exact 16-digit lookup',
      async () => {
        lookupNikMock
          .mockResolvedValue(
            insideLookupResult,
          )

        const wrapper =
          mountLookupView()

        await flushPromises()

        const input =
          wrapper.get<HTMLInputElement>(
            '[data-testid="nik-input"]',
          )

        await input.setValue(
          '3576-0101 0101-0001',
        )

        expect(
          input.element.value,
        ).toBe(
          '3576010101010001',
        )

        await wrapper
          .get('form')
          .trigger(
            'submit',
          )

        await flushPromises()

        expect(
          lookupNikMock,
        ).toHaveBeenCalledWith(
          '3576010101010001',
        )

        expect(
          wrapper
            .find(
              '[data-testid="lookup-result"]',
            )
            .exists(),
        ).toBe(
          true,
        )

        expect(
          wrapper.text(),
        ).toContain(
          'Budi Santoso',
        )

        expect(
          wrapper.text(),
        ).toContain(
          '3576********0001',
        )

        expect(
  wrapper
    .get(
      '[data-testid="lookup-result"]',
    )
    .text(),
).toContain(
  'Jagalan',
)

        /*
         * Raw NIK dibersihkan setelah lookup sukses.
         */
        expect(
          input.element.value,
        ).toBe(
          '',
        )
      },
    )

    it(
  'allows cross-region KPM result without blocking it',
  async () => {
    lookupNikMock
      .mockResolvedValue(
        outsideLookupResult,
      )

    const wrapper =
      mountLookupView()

    await flushPromises()

    await wrapper
      .get<HTMLInputElement>(
        '[data-testid="nik-input"]',
      )
      .setValue(
        '3576010101010001',
      )

    await wrapper
      .get('form')
      .trigger(
        'submit',
      )

    await flushPromises()

    expect(
      wrapper.text(),
    ).toContain(
      'KPM Luar Wilayah',
    )

    expect(
      wrapper.text(),
    ).toContain(
      'Miji',
    )

    expect(
      wrapper
        .find(
          '[data-testid="outside-assignment-notice"]',
        )
        .exists(),
    ).toBe(
      true,
    )

    expect(
      wrapper
        .find(
          '[data-testid="transaction-button"]',
        )
        .exists(),
    ).toBe(
      true,
    )
  },
)

    it(
      'rejects NIK that is not exactly 16 digits',
      async () => {
        const wrapper =
          mountLookupView()

        await flushPromises()

        await wrapper
          .get<HTMLInputElement>(
            '[data-testid="nik-input"]',
          )
          .setValue(
            '357601010101001',
          )

        await wrapper
          .get('form')
          .trigger(
            'submit',
          )

        await flushPromises()

        expect(
          wrapper
            .find(
              '[data-testid="nik-validation-error"]',
            )
            .exists(),
        ).toBe(
          true,
        )

        expect(
          wrapper.text(),
        ).toContain(
          'NIK harus terdiri dari 16 digit angka',
        )

        expect(
          lookupNikMock,
        ).not.toHaveBeenCalled()
      },
    )

    it(
      'does not show lookup form when Surveyor has no assignment',
      async () => {
        getContextMock
          .mockResolvedValue({
            ...workspaceContext,

            assignment:
              null,

            kpm_count:
              0,
          })

        const wrapper =
          mountLookupView()

        await flushPromises()

        expect(
          wrapper
            .find(
              '[data-testid="lookup-no-assignment"]',
            )
            .exists(),
        ).toBe(
          true,
        )

        expect(
          wrapper
            .find(
              '[data-testid="nik-input"]',
            )
            .exists(),
        ).toBe(
          false,
        )

        expect(
          lookupNikMock,
        ).not.toHaveBeenCalled()
      },
    )

    it(
      'shows lookup error returned by backend',
      async () => {
        lookupNikMock
          .mockRejectedValue(
            new Error(
              'Participant not found',
            ),
          )

        const wrapper =
          mountLookupView()

        await flushPromises()

        await wrapper
          .get<HTMLInputElement>(
            '[data-testid="nik-input"]',
          )
          .setValue(
            '3576010101010001',
          )

        await wrapper
          .get('form')
          .trigger(
            'submit',
          )

        await flushPromises()

        expect(
          wrapper
            .find(
              '[data-testid="lookup-error"]',
            )
            .exists(),
        ).toBe(
          true,
        )

        expect(
          wrapper.text(),
        ).toContain(
          'KPM dengan NIK tersebut tidak ditemukan pada periode aktif',
        )
      },
    )
  },
)