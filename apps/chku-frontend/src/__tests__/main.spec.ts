import { beforeEach, describe, expect, it, vi } from 'vitest'

const { createApp, isReady, mount } = vi.hoisted(() => {
  const mount = vi.fn<() => void>()
  const use = vi.fn<() => void>()

  return {
    mount,
    use,
    createApp: vi.fn<() => { use: typeof use; mount: typeof mount }>(() => ({ use, mount })),
    isReady: vi.fn<() => Promise<void>>(),
  }
})

vi.mock('vue', () => ({
  createApp,
}))

vi.mock('pinia', () => ({
  createPinia: vi.fn<() => { install: ReturnType<typeof vi.fn> }>(() => ({
    install: vi.fn<() => void>(),
  })),
}))

vi.mock('@tanstack/vue-query', () => ({
  VueQueryPlugin: { install: vi.fn<() => void>() },
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
