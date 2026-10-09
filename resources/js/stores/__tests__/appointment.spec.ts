import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAppointmentStore } from '@/stores/appointment'
import type { Appointment } from '@/types/appointment'

vi.mock('@/services/appointment', () => ({
  appointmentService: {
    list: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
    cancel: vi.fn(),
    reschedule: vi.fn(),
    start: vi.fn(),
    finish: vi.fn(),
    updateNotes: vi.fn(),
  },
}))

import { appointmentService } from '@/services/appointment'

const makeAppt = (overrides: Partial<Appointment> = {}): Appointment => ({
  id: 'appt-1',
  doctor: { id: 'doc-1', name: 'Dr. Ana', crm: '12345', crm_uf: 'SP', photo_url: null },
  patient: { id: 'pat-1', name: 'João' },
  scheduled_date: '2030-06-10',
  scheduled_time: '09:00:00',
  duration_minutes: 30,
  status: 'agendada',
  status_label: 'Agendada',
  notes: null,
  cancellation_reason: null,
  started_at: null,
  ended_at: null,
  created_at: '',
  ...overrides,
})

describe('useAppointmentStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('fetchAppointments()', () => {
    it('popula a lista de consultas', async () => {
      vi.mocked(appointmentService.list).mockResolvedValueOnce({
        data: [makeAppt(), makeAppt({ id: 'appt-2' })],
        meta: { current_page: 1, last_page: 1, per_page: 15, total: 2 },
      })

      const store = useAppointmentStore()
      await store.fetchAppointments()

      expect(store.appointments).toHaveLength(2)
      expect(store.meta.total).toBe(2)
    })
  })

  describe('createAppointment()', () => {
    it('adiciona a consulta criada no início da lista', async () => {
      const created = makeAppt({ id: 'appt-new' })
      vi.mocked(appointmentService.create).mockResolvedValueOnce(created)

      const store = useAppointmentStore()
      store.meta.total = 0

      await store.createAppointment({
        doctor_id: 'doc-1',
        scheduled_date: '2030-06-10',
        scheduled_time: '09:00',
      })

      expect(store.appointments[0].id).toBe('appt-new')
      expect(store.meta.total).toBe(1)
    })
  })

  describe('cancelAppointment()', () => {
    it('atualiza status na lista para cancelada', async () => {
      const original = makeAppt({ status: 'agendada' })
      const cancelled = makeAppt({ status: 'cancelada', status_label: 'Cancelada' })
      vi.mocked(appointmentService.cancel).mockResolvedValueOnce(cancelled)

      const store = useAppointmentStore()
      store.appointments = [original]

      await store.cancelAppointment('appt-1', 'Motivo teste')

      expect(store.appointments[0].status).toBe('cancelada')
    })
  })

  describe('startAppointment()', () => {
    it('atualiza status para em_andamento', async () => {
      const original = makeAppt({ status: 'agendada' })
      const started = makeAppt({ status: 'em_andamento', status_label: 'Em andamento', started_at: '2030-06-10T09:00:00Z' })
      vi.mocked(appointmentService.start).mockResolvedValueOnce(started)

      const store = useAppointmentStore()
      store.appointments = [original]

      await store.startAppointment('appt-1')

      expect(store.appointments[0].status).toBe('em_andamento')
    })
  })

  describe('finishAppointment()', () => {
    it('atualiza status para concluida', async () => {
      const original = makeAppt({ status: 'em_andamento' })
      const finished = makeAppt({ status: 'concluida', status_label: 'Concluída', ended_at: '2030-06-10T09:30:00Z' })
      vi.mocked(appointmentService.finish).mockResolvedValueOnce(finished)

      const store = useAppointmentStore()
      store.appointments = [original]

      await store.finishAppointment('appt-1', 'concluida', 'Tudo normal.')

      expect(store.appointments[0].status).toBe('concluida')
    })
  })

  describe('updateNotes()', () => {
    it('atualiza as notas na lista', async () => {
      const original = makeAppt({ status: 'em_andamento', notes: null })
      const updated = makeAppt({ status: 'em_andamento', notes: 'PA normal.' })
      vi.mocked(appointmentService.updateNotes).mockResolvedValueOnce(updated)

      const store = useAppointmentStore()
      store.appointments = [original]

      await store.updateNotes('appt-1', 'PA normal.')

      expect(store.appointments[0].notes).toBe('PA normal.')
    })
  })
})
