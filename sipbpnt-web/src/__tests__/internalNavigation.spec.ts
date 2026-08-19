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
      'provides admin BNBA routes',
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
      },
    )

    it(
      'provides manager BNBA data access',
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