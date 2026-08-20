import {
  describe,
  expect,
  it,
} from 'vitest'

import {
  getInternalNavigation,
} from '@/config/internalNavigation'

describe(
  'internal navigation',
  () => {
    it(
      'provides admin BNBA, surveyor, and E-Warung routes',
      () => {
        const navigation =
          getInternalNavigation(
            'admin_dinsos',
          )

        const importBnba =
          navigation.find(
            (item) =>
              item.key
              === 'import-bnba',
          )

        const dataBnba =
          navigation.find(
            (item) =>
              item.key
              === 'data-bnba',
          )

        const surveyorAccounts =
          navigation.find(
            (item) =>
              item.key
              === 'surveyor-accounts',
          )

        const eWarung =
          navigation.find(
            (item) =>
              item.key
              === 'ewarung',
          )

        expect(
          importBnba,
        ).toBeDefined()

        expect(
          importBnba
            ?.routeName,
        ).toBe(
          'admin-bnba-import',
        )

        expect(
          dataBnba,
        ).toBeDefined()

        expect(
          dataBnba
            ?.routeName,
        ).toBe(
          'management-bnba',
        )

        expect(
          surveyorAccounts,
        ).toBeDefined()

        expect(
          surveyorAccounts
            ?.routeName,
        ).toBe(
          'admin-surveyors',
        )

        expect(
          surveyorAccounts
            ?.available,
        ).toBe(true)

        expect(
          eWarung,
        ).toBeDefined()

        expect(
          eWarung
            ?.routeName,
        ).toBe(
          'admin-e-warungs',
        )

        expect(
          eWarung
            ?.available,
        ).toBe(true)
      },
    )

    it(
      'provides manager BNBA data access without master E-Warung ownership',
      () => {
        const navigation =
          getInternalNavigation(
            'manager',
          )

        const dataBnba =
          navigation.find(
            (item) =>
              item.key
              === 'data-bnba',
          )

        const eWarung =
          navigation.find(
            (item) =>
              item.key
              === 'ewarung',
          )

        expect(
          dataBnba,
        ).toBeDefined()

        expect(
          dataBnba
            ?.available,
        ).toBe(true)

        expect(
          dataBnba
            ?.routeName,
        ).toBe(
          'management-bnba',
        )

        expect(
          eWarung,
        ).toBeUndefined()
      },
    )

    it(
      'keeps unfinished surveyor modules disabled',
      () => {
        const navigation =
          getInternalNavigation(
            'surveyor',
          )

        const search =
          navigation.find(
            (item) =>
              item.key
              === 'search-kpm',
          )

        expect(
          search,
        ).toBeDefined()

        expect(
          search
            ?.available,
        ).toBe(false)

        expect(
          search
            ?.routeName,
        ).toBeUndefined()
      },
    )

    it(
      'provides dashboard to every role',
      () => {
        const roles = [
          'admin_dinsos',
          'manager',
          'surveyor',
          'kepala_dinas',
        ] as const

        for (
          const role
          of roles
        ) {
          const navigation =
            getInternalNavigation(
              role,
            )

          const dashboard =
            navigation.find(
              (item) =>
                item.key
                === 'dashboard',
            )

          expect(
            dashboard,
          ).toBeDefined()

          expect(
            dashboard
              ?.routeName,
          ).toBe(
            'dashboard',
          )

          expect(
            dashboard
              ?.available,
          ).toBe(true)
        }
      },
    )
  },
)