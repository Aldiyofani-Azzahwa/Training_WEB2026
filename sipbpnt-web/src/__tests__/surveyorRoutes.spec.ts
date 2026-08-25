import {
  describe,
  expect,
  it,
} from 'vitest'

import {
  surveyorRoute,
} from '@/router/surveyorRoutes'

describe(
  'surveyor routes',
  () => {
    it(
      'uses a dedicated authenticated Surveyor workspace',
      () => {
        expect(
          surveyorRoute.path,
        ).toBe(
          '/surveyor',
        )

        expect(
          surveyorRoute.meta,
        ).toMatchObject({
          requiresAuth:
            true,

          roles: [
            'surveyor',
          ],
        })
      },
    )

    it(
      'provides the five final bottom-navigation routes',
      () => {
        const routeNames =
          (
            surveyorRoute.children
            ?? []
          )
            .map(
              (route) =>
                route.name,
            )

        expect(
          routeNames,
        ).toEqual([
          'surveyor-home',
          'surveyor-kpm',
          'surveyor-scan-ktp',
          'surveyor-transactions',
          'surveyor-history',
        ])
      },
    )

    it(
      'does not accept period or wilayah parameters in route paths',
      () => {
        const paths =
          (
            surveyorRoute.children
            ?? []
          )
            .map(
              (route) =>
                route.path,
            )

        expect(
          paths.some(
            (path) =>
              path.includes(
                ':period',
              )
              ||
              path.includes(
                ':kelurahan',
              )
              ||
              path.includes(
                ':kecamatan',
              ),
          ),
        ).toBe(
          false,
        )
      },
    )
  },
)