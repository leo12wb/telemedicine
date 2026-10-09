import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useSpecialtyStore } from '@/stores/specialty'
import type { Specialty } from '@/types/specialty'

vi.mock('@/services/specialty', () => ({
  specialtyService: {
    list: vi.fn(),
    listActive: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    toggleActive: vi.fn(),
    remove: vi.fn(),
  },
}))

import { specialtyService } from '@/services/specialty'

const makeSpecialty = (overrides: Partial<Specialty> = {}): Specialty => ({
  id: 'uuid-1',
  name: 'Cardiologia',
  description: null,
  is_active: true,
  created_at: '2024-01-01T00:00:00Z',
  ...overrides,
})

const emptyMeta = { current_page: 1, last_page: 1, per_page: 50, total: 0 }

describe('useSpecialtyStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('fetchSpecialties()', () => {
    it('popula a lista de especialidades', async () => {
      const items = [makeSpecialty(), makeSpecialty({ id: 'uuid-2', name: 'Neurologia' })]
      vi.mocked(specialtyService.list).mockResolvedValueOnce({
        data: items,
        meta: { ...emptyMeta, total: 2 },
      })

      const store = useSpecialtyStore()
      await store.fetchSpecialties()

      expect(store.specialties).toHaveLength(2)
      expect(store.meta.total).toBe(2)
    })
  })

  describe('fetchActive()', () => {
    it('popula apenas especialidades ativas', async () => {
      const items = [makeSpecialty(), makeSpecialty({ id: 'uuid-2' })]
      vi.mocked(specialtyService.listActive).mockResolvedValueOnce(items)

      const store = useSpecialtyStore()
      await store.fetchActive()

      expect(store.activeSpecialties).toHaveLength(2)
    })
  })

  describe('createSpecialty()', () => {
    it('adiciona nova especialidade à lista', async () => {
      const created = makeSpecialty({ id: 'uuid-new', name: 'Dermatologia' })
      vi.mocked(specialtyService.create).mockResolvedValueOnce(created)

      const store = useSpecialtyStore()
      store.meta.total = 0

      await store.createSpecialty({ name: 'Dermatologia' })

      expect(store.specialties).toContainEqual(created)
      expect(store.activeSpecialties).toContainEqual(created)
      expect(store.meta.total).toBe(1)
    })

    it('especialidade inativa não vai para activeSpecialties', async () => {
      const created = makeSpecialty({ is_active: false })
      vi.mocked(specialtyService.create).mockResolvedValueOnce(created)

      const store = useSpecialtyStore()
      await store.createSpecialty({ name: 'X', is_active: false })

      expect(store.activeSpecialties).toHaveLength(0)
    })
  })

  describe('toggleActive()', () => {
    it('atualiza status na lista e em activeSpecialties', async () => {
      const original = makeSpecialty({ is_active: true })
      const toggled = makeSpecialty({ is_active: false })
      vi.mocked(specialtyService.toggleActive).mockResolvedValueOnce(toggled)

      const store = useSpecialtyStore()
      store.specialties = [original]
      store.activeSpecialties = [original]

      await store.toggleActive(original.id)

      expect(store.specialties[0].is_active).toBe(false)
      expect(store.activeSpecialties).toHaveLength(0)
    })
  })

  describe('removeSpecialty()', () => {
    it('remove da lista e de activeSpecialties', async () => {
      vi.mocked(specialtyService.remove).mockResolvedValueOnce(undefined)

      const store = useSpecialtyStore()
      const s = makeSpecialty()
      store.specialties = [s]
      store.activeSpecialties = [s]
      store.meta.total = 1

      await store.removeSpecialty(s.id)

      expect(store.specialties).toHaveLength(0)
      expect(store.activeSpecialties).toHaveLength(0)
      expect(store.meta.total).toBe(0)
    })
  })
})
