import {
  describe,
  expect,
  it,
} from 'vitest'

import { shallowMount } from '@vue/test-utils'
import App from '@/App.vue'

describe('App', () => {
  it('renders the router outlet', () => {
    const wrapper = shallowMount(
      App,
      {
        global: {
          stubs: {
            RouterView: {
              template:
                '<div data-testid="router-view" />',
            },
          },
        },
      },
    )

    expect(
      wrapper
        .find(
          '[data-testid="router-view"]',
        )
        .exists(),
    ).toBe(true)
  })
})