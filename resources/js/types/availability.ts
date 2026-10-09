export interface DoctorSchedule {
  id: string
  doctor_id: string
  day_of_week: number
  start_time: string
  end_time: string
  slot_duration_minutes: number
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface DoctorBlock {
  id: string
  doctor_id: string
  block_date: string
  block_start: string | null
  block_end: string | null
  reason: string | null
  is_all_day: boolean
  created_at: string
}

export interface AvailabilitySlot {
  time: string
  schedule_id: string
}

export const DAY_NAMES = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'] as const
