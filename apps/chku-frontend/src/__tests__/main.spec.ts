import { beforeEach, describe, expect, it, vi } from 'vitest'

const { createApp, isReady, mount, use } = vi.hoisted(() => {
  const mount = vi.fn()
  const use = vi.fn()

  return {
    mount,
    use,
    createApp: vi.fn(() => ({ use, mount })),
    isReady: vi.fn(),
  }
})

vi.mock('vue', () => ({
  createApp,
}))

vi.mock('pinia', () => ({
  createPinia: vi.fn(() => ({ install: vi.fn() })),
}))

vi.mock('@tanstack/vue-query', () => ({
  VueQueryPlugin: { install: vi.fn() },
}))

vi.mock('@/queries/client', () => ({
  queryClient: {},
}))

vi.mock('@/router', () => ({
  default: {
    isReady,
  },
}))

vi.mock('@/App.vue', () => ({
  default: {},
}))

describe('main', () => {
  beforeEach(() => {
    vi.resetModules()
    vi.clearAllMocks()
    localStorage.clear()
    document.documentElement.removeAttribute('data-theme')
  })

  it('mounts the app after the router is ready', async () => {
    let resolveRouter: () => void
    isReady.mockReturnValueOnce(
      new Promise<void>((resolve) => {
        resolveRouter = resolve
      }),
    )

    await import('@/main')

    expect(createApp).toHaveBeenCalledOnce()
    expect(isReady).toHaveBeenCalledOnce()
    expect(mount).not.toHaveBeenCalled()

    resolveRouter!()
    await Promise.resolve()

    expect(mount).toHaveBeenCalledWith('#app')
  })
})
