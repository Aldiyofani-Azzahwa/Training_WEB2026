import type {
  ObjectDirective,
} from 'vue'

export type RevealDirection =
  | 'left'
  | 'right'
  | 'up'
  | 'pop'

export interface RevealOptions {
  direction?: RevealDirection
  delay?: number
  threshold?: number
}

const observers = new WeakMap<
  HTMLElement,
  IntersectionObserver
>()

const directionClasses: Record<
  RevealDirection,
  string
> = {
  left: 'reveal-from-left',
  right: 'reveal-from-right',
  up: 'reveal-from-up',
  pop: 'reveal-pop',
}

/**
 * Memberikan animasi ketika elemen pertama kali
 * memasuki viewport.
 *
 * Setelah tampil, observer langsung dihentikan
 * sehingga animasi tidak berulang.
 */
export const revealDirective:
  ObjectDirective<
    HTMLElement,
    RevealOptions | undefined
  > = {
    mounted(element, binding): void {
      const options = binding.value ?? {}

      const direction =
        options.direction ?? 'up'

      const delay = Math.max(
        options.delay ?? 0,
        0,
      )

      const threshold =
        options.threshold ?? 0.13

      element.classList.add(
        'reveal-base',
        directionClasses[direction],
      )

      element.style.setProperty(
        '--reveal-delay',
        `${delay}ms`,
      )

      const reducedMotion =
        window.matchMedia(
          '(prefers-reduced-motion: reduce)',
        ).matches

      if (reducedMotion) {
        element.classList.add(
          'is-revealed',
        )

        return
      }

      const observer =
        new IntersectionObserver(
          (entries) => {
            const entry = entries[0]

            if (
              !entry ||
              !entry.isIntersecting
            ) {
              return
            }

            element.classList.add(
              'is-revealed',
            )

            observer.unobserve(element)
            observer.disconnect()

            observers.delete(element)
          },
          {
            threshold,
            rootMargin:
              '0px 0px -50px 0px',
          },
        )

      observer.observe(element)
      observers.set(element, observer)
    },

    unmounted(element): void {
      const observer =
        observers.get(element)

      observer?.disconnect()
      observers.delete(element)
    },
  }