import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useDoctorStore } from '@/stores/doctor'
import type { Doctor } from '@/types/doctor'

vi.mock('@/services/doctor', () => ({
  doctorService: {
    list: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    toggleActive: vi.fn(),
    remove: vi.fn(),
    uploadPhoto: vi.fn(),
    deletePhoto: vi.fn(),
  },
}))

import { doctorService } from '@/services/doctor'

const makeDoctor = (overrides: Partial<Doctor> = {}): Doctor => ({
  id: 'uuid-1',
  crm: '12345',
  crm_uf: 'SP',
  phone: null,
  bio: null,
  photo_url: null,
  is_active: true,
  user: { id: 'user-1', name: 'Dr. João', email: 'joao@x.com', role: 'medico', is_active: true, email_verified_at: null, created_at: '', updated_at: '' },
  specialties: [],
  created_at: '2024-01-01T00:00:00Z',
  ...overrides,
})

describe('useDoctorStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('fetchDoctors()', () => {
    it('popula a lista de médicos', async () => {
      vi.mocked(doctorService.list).mockResolvedValueOnce({
        data: [makeDoctor(), makeDoctor({ id: 'uuid-2' })],
        meta: { current_page: 1, last_page: 1, per_page: 15, total: 2 },
      })

      const store = useDoctorStore()
      await store.fetchDoctors()

      expect(store.doctors).toHaveLength(2)
      expect(store.meta.total).toBe(2)
    })
  })

  describe('createDoctor()', () => {
    it('adiciona o médico no início da lista', async () => {
      const created = makeDoctor({ id: 'uuid-new' })
      vi.mocked(doctorService.create).mockResolvedValueOnce(created)

      const store = useDoctorStore()
      store.meta.total = 0

      await store.createDoctor({ name: 'Dr. X', email: 'x@x.com', password: 'Abc@1234', crm: '99', crm_uf: 'SP' })

      expect(store.doctors[0]).toEqual(created)
      expect(store.meta.total).toBe(1)
    })
  })

  describe('updateDoctor()', () => {
    it('atualiza médico na lista e em currentDoctor', async () => {
      const original = makeDoctor()
      const updated = makeDoctor({ bio: 'Nova bio' })
      vi.mocked(doctorService.update).mockResolvedValueOnce(updated)

      const store = useDoctorStore()
      store.doctors = [original]
      store.currentDoctor = original

      await store.updateDoctor(original.id, { bio: 'Nova bio' })

      expect(store.doctors[0].bio).toBe('Nova bio')
      expect(store.currentDoctor?.bio).toBe('Nova bio')
    })
  })

  describe('toggleActive()', () => {
    it('atualiza status na lista', async () => {
      const original = makeDoctor({ is_active: true })
      const toggled = makeDoctor({ is_active: false })
      vi.mocked(doctorService.toggleActive).mockResolvedValueOnce(toggled)

      const store = useDoctorStore()
      store.doctors = [original]

      await store.toggleActive(original.id)

      expect(store.doctors[0].is_active).toBe(false)
    })
  })

  describe('removeDoctor()', () => {
    it('remove médico da lista', async () => {
      vi.mocked(doctorService.remove).mockResolvedValueOnce(undefined)

      const store = useDoctorStore()
      store.doctors = [makeDoctor({ id: 'uuid-1' }), makeDoctor({ id: 'uuid-2' })]
      store.meta.total = 2

      await store.removeDoctor('uuid-1')

      expect(store.doctors).toHaveLength(1)
      expect(store.doctors[0].id).toBe('uuid-2')
      expect(store.meta.total).toBe(1)
    })
  })
})
