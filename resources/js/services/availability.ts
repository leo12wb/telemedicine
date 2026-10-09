import http from './http'
import type { AvailabilitySlot, DoctorBlock, DoctorSchedule } from '@/types/availability'

export interface CreateSchedulePayload {
  day_of_week: number
  start_time: string
  end_time: string
  slot_duration_minutes: number
  is_active?: boolean
}

export interface UpdateSchedulePayload {
  day_of_week?: number
  start_time?: string
  end_time?: string
  slot_duration_minutes?: number
  is_active?: boolean
}

export interface CreateBlockPayload {
  block_date: string
  block_start?: string | null
  block_end?: string | null
  reason?: string | null
}

export interface BlockFilters {
  from?: string
  to?: string
}

export const availabilityService = {
  // ─── Schedules ─────────────────────────────────────────────────────────────

  async listSchedules(doctorId: string): Promise<DoctorSchedule[]> {
    const response = await http.get<{ data: DoctorSchedule[] }>(`/doctors/${doctorId}/schedules`)
    return response.data.data
  },

  async createSchedule(doctorId: string, payload: CreateSchedulePayload): Promise<DoctorSchedule> {
    const response = await http.post<{ data: DoctorSchedule }>(`/doctors/${doctorId}/schedules`, payload)
    return response.data.data
  },

  async updateSchedule(doctorId: string, scheduleId: string, payload: UpdateSchedulePayload): Promise<DoctorSchedule> {
    const response = await http.put<{ data: DoctorSchedule }>(`/doctors/${doctorId}/schedules/${scheduleId}`, payload)
    return response.data.data
  },

  async removeSchedule(doctorId: string, scheduleId: string): Promise<void> {
    await http.delete(`/doctors/${doctorId}/schedules/${scheduleId}`)
  },

  // ─── Blocks ─────────────────────────────────────────────────────────────────

  async listBlocks(doctorId: string, filters: BlockFilters = {}): Promise<DoctorBlock[]> {
    const response = await http.get<{ data: DoctorBlock[] }>(`/doctors/${doctorId}/blocks`, { params: filters })
    return response.data.data
  },

  async createBlock(doctorId: string, payload: CreateBlockPayload): Promise<DoctorBlock> {
    const response = await http.post<{ data: DoctorBlock }>(`/doctors/${doctorId}/blocks`, payload)
    return response.data.data
  },

  async removeBlock(doctorId: string, blockId: string): Promise<void> {
    await http.delete(`/doctors/${doctorId}/blocks/${blockId}`)
  },

  // ─── Slots ──────────────────────────────────────────────────────────────────

  async getSlots(doctorId: string, date: string): Promise<AvailabilitySlot[]> {
    const response = await http.get<{ data: AvailabilitySlot[] }>(`/doctors/${doctorId}/availability`, {
      params: { date },
    })
    return response.data.data
  },
}
