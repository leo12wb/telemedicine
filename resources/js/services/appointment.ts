import http from './http'
import type { Appointment, AppointmentStatus } from '@/types/appointment'

export interface AppointmentFilters {
  status?: AppointmentStatus
  date?: string
  from?: string
  to?: string
  page?: number
}

export interface PaginatedAppointments {
  data: Appointment[]
  meta: { current_page: number; last_page: number; per_page: number; total: number }
}

export interface CreateAppointmentPayload {
  doctor_id: string
  scheduled_date: string
  scheduled_time: string
}

export const appointmentService = {
  async list(filters: AppointmentFilters = {}): Promise<PaginatedAppointments> {
    const response = await http.get<PaginatedAppointments>('/appointments', { params: filters })
    return response.data
  },

  async get(id: string): Promise<Appointment> {
    const response = await http.get<{ data: Appointment }>(`/appointments/${id}`)
    return response.data.data
  },

  async create(payload: CreateAppointmentPayload): Promise<Appointment> {
    const response = await http.post<{ data: Appointment }>('/appointments', payload)
    return response.data.data
  },

  async cancel(id: string, reason?: string): Promise<Appointment> {
    const response = await http.patch<{ data: Appointment }>(`/appointments/${id}/cancel`, { reason })
    return response.data.data
  },

  async reschedule(id: string, scheduled_date: string, scheduled_time: string): Promise<Appointment> {
    const response = await http.patch<{ data: Appointment }>(`/appointments/${id}/reschedule`, {
      scheduled_date,
      scheduled_time,
    })
    return response.data.data
  },

  async start(id: string): Promise<Appointment> {
    const response = await http.patch<{ data: Appointment }>(`/appointments/${id}/start`)
    return response.data.data
  },

  async finish(id: string, outcome: 'concluida' | 'paciente_ausente', notes?: string): Promise<Appointment> {
    const response = await http.patch<{ data: Appointment }>(`/appointments/${id}/finish`, { outcome, notes })
    return response.data.data
  },

  async updateNotes(id: string, notes: string): Promise<Appointment> {
    const response = await http.patch<{ data: Appointment }>(`/appointments/${id}/notes`, { notes })
    return response.data.data
  },
}
