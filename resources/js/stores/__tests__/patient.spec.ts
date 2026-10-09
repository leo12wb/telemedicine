import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { usePatientStore } from '@/stores/patient'
import type { Patient } from '@/types/patient'

vi.mock('@/services/patient', () => ({
  patientService: {
    list: vi.fn(),
    profile: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: vi.fn(),
  },
}))

import { patientService } from '@/services/patient'

const makePatient = (overrides: Partial<Patient> = {}): Patient => ({
  id: 'uuid-1',
  cpf: null,
  birth_date: null,
  phone: null,
  health_insurance: null,
  health_insurance_number: null,
  user: { id: 'user-1', name: 'Ana', email: 'ana@x.com', role: 'paciente', is_active: true, email_verified_at: null, created_at: '', updated_at: '' },
  created_at: '2024-01-01T00:00:00Z',
  ...overrides,
})

describe('usePatientStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('fetchPatients()', () => {
    it('popula a lista de pacientes', async () => {
      vi.mocked(patientService.list).mockResolvedValueOnce({
        data: [makePatient(), makePatient({ id: 'uuid-2' })],
        meta: { current_page: 1, last_page: 1, per_page: 15, total: 2 },
      })

      const store = usePatientStore()
      await store.fetchPatients()

      expect(store.patients).toHaveLength(2)
      expect(store.meta.total).toBe(2)
    })
  })

  describe('fetchProfile()', () => {
    it('popula ownProfile', async () => {
      const p = makePatient({ health_insurance: 'Unimed' })
      vi.mocked(patientService.profile).mockResolvedValueOnce(p)

      const store = usePatientStore()
      await store.fetchProfile()

      expect(store.ownProfile?.health_insurance).toBe('Unimed')
    })
  })

  describe('createPatient()', () => {
    it('adiciona paciente no início da lista', async () => {
      const created = makePatient({ id: 'uuid-new' })
      vi.mocked(patientService.create).mockResolvedValueOnce(created)

      const store = usePatientStore()
      store.meta.total = 0
      await store.createPatient({ name: 'X', email: 'x@x.com', password: 'p' })

      expect(store.patients[0]).toEqual(created)
      expect(store.meta.total).toBe(1)
    })
  })

  describe('updatePatient()', () => {
    it('atualiza na lista e em ownProfile', async () => {
      const original = makePatient()
      const updated = makePatient({ phone: '11999998888' })
      vi.mocked(patientService.update).mockResolvedValueOnce(updated)

      const store = usePatientStore()
      store.patients = [original]
      store.ownProfile = original

      await store.updatePatient(original.id, { phone: '11999998888' })

      expect(store.patients[0].phone).toBe('11999998888')
      expect(store.ownProfile?.phone).toBe('11999998888')
    })
  })

  describe('removePatient()', () => {
    it('remove da lista', async () => {
      vi.mocked(patientService.remove).mockResolvedValueOnce(undefined)

      const store = usePatientStore()
      store.patients = [makePatient({ id: 'uuid-1' }), makePatient({ id: 'uuid-2' })]
      store.meta.total = 2

      await store.removePatient('uuid-1')

      expect(store.patients).toHaveLength(1)
      expect(store.meta.total).toBe(1)
    })
  })
})
