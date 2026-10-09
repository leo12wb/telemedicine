import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAvailabilityStore } from '@/stores/availability'
import type { AvailabilitySlot, DoctorBlock, DoctorSchedule } from '@/types/availability'

vi.mock('@/services/availability', () => ({
  availabilityService: {
    listSchedules: vi.fn(),
    createSchedule: vi.fn(),
    updateSchedule: vi.fn(),
    removeSchedule: vi.fn(),
    listBlocks: vi.fn(),
    createBlock: vi.fn(),
    removeBlock: vi.fn(),
    getSlots: vi.fn(),
  },
}))

import { availabilityService } from '@/services/availability'

const makeSchedule = (overrides: Partial<DoctorSchedule> = {}): DoctorSchedule => ({
  id: 'sched-1',
  doctor_id: 'doc-1',
  day_of_week: 1,
  start_time: '08:00:00',
  end_time: '12:00:00',
  slot_duration_minutes: 30,
  is_active: true,
  created_at: '',
  updated_at: '',
  ...overrides,
})

const makeBlock = (overrides: Partial<DoctorBlock> = {}): DoctorBlock => ({
  id: 'block-1',
  doctor_id: 'doc-1',
  block_date: '2030-01-01',
  block_start: null,
  block_end: null,
  reason: null,
  is_all_day: true,
  created_at: '',
  ...overrides,
})

const makeSlot = (time: string): AvailabilitySlot => ({ time, schedule_id: 'sched-1' })

describe('useAvailabilityStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('fetchSchedules()', () => {
    it('popula a lista de horários', async () => {
      vi.mocked(availabilityService.listSchedules).mockResolvedValueOnce([
        makeSchedule(),
        makeSchedule({ id: 'sched-2', day_of_week: 3 }),
      ])

      const store = useAvailabilityStore()
      await store.fetchSchedules('doc-1')

      expect(store.schedules).toHaveLength(2)
    })
  })

  describe('createSchedule()', () => {
    it('adiciona o horário criado à lista', async () => {
      const created = makeSchedule({ id: 'sched-new' })
      vi.mocked(availabilityService.createSchedule).mockResolvedValueOnce(created)

      const store = useAvailabilityStore()
      await store.createSchedule('doc-1', {
        day_of_week: 1,
        start_time: '08:00',
        end_time: '12:00',
        slot_duration_minutes: 30,
      })

      expect(store.schedules).toHaveLength(1)
      expect(store.schedules[0].id).toBe('sched-new')
    })
  })

  describe('updateSchedule()', () => {
    it('atualiza o horário na lista', async () => {
      const original = makeSchedule({ is_active: true })
      const updated = makeSchedule({ is_active: false })
      vi.mocked(availabilityService.updateSchedule).mockResolvedValueOnce(updated)

      const store = useAvailabilityStore()
      store.schedules = [original]

      await store.updateSchedule('doc-1', 'sched-1', { is_active: false })

      expect(store.schedules[0].is_active).toBe(false)
    })
  })

  describe('removeSchedule()', () => {
    it('remove o horário da lista', async () => {
      vi.mocked(availabilityService.removeSchedule).mockResolvedValueOnce(undefined)

      const store = useAvailabilityStore()
      store.schedules = [makeSchedule({ id: 'sched-1' }), makeSchedule({ id: 'sched-2' })]

      await store.removeSchedule('doc-1', 'sched-1')

      expect(store.schedules).toHaveLength(1)
      expect(store.schedules[0].id).toBe('sched-2')
    })
  })

  describe('fetchBlocks()', () => {
    it('popula a lista de bloqueios', async () => {
      vi.mocked(availabilityService.listBlocks).mockResolvedValueOnce([makeBlock(), makeBlock({ id: 'block-2' })])

      const store = useAvailabilityStore()
      await store.fetchBlocks('doc-1')

      expect(store.blocks).toHaveLength(2)
    })
  })

  describe('createBlock()', () => {
    it('adiciona o bloqueio criado à lista', async () => {
      const created = makeBlock({ id: 'block-new' })
      vi.mocked(availabilityService.createBlock).mockResolvedValueOnce(created)

      const store = useAvailabilityStore()
      await store.createBlock('doc-1', { block_date: '2030-01-01' })

      expect(store.blocks[0].id).toBe('block-new')
    })
  })

  describe('removeBlock()', () => {
    it('remove o bloqueio da lista', async () => {
      vi.mocked(availabilityService.removeBlock).mockResolvedValueOnce(undefined)

      const store = useAvailabilityStore()
      store.blocks = [makeBlock({ id: 'block-1' }), makeBlock({ id: 'block-2' })]

      await store.removeBlock('doc-1', 'block-1')

      expect(store.blocks).toHaveLength(1)
      expect(store.blocks[0].id).toBe('block-2')
    })
  })

  describe('fetchSlots()', () => {
    it('popula os slots disponíveis', async () => {
      vi.mocked(availabilityService.getSlots).mockResolvedValueOnce([
        makeSlot('08:00'),
        makeSlot('08:30'),
        makeSlot('09:00'),
      ])

      const store = useAvailabilityStore()
      await store.fetchSlots('doc-1', '2030-01-06')

      expect(store.slots).toHaveLength(3)
      expect(store.slots[0].time).toBe('08:00')
    })
  })
})
