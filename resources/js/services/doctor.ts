import http from './http'
import type { Doctor } from '@/types/doctor'

export interface DoctorFilters {
  specialty_id?: string
  search?: string
  page?: number
}

export interface PaginatedDoctors {
  data: Doctor[]
  meta: { current_page: number; last_page: number; per_page: number; total: number }
}

export interface CreateDoctorPayload {
  name: string
  email: string
  password: string
  crm: string
  crm_uf: string
  phone?: string
  bio?: string
  specialty_ids?: string[]
}

export interface UpdateDoctorPayload {
  phone?: string | null
  bio?: string | null
  crm?: string
  crm_uf?: string
  specialty_ids?: string[]
  is_active?: boolean
}

export const doctorService = {
  async list(filters: DoctorFilters = {}): Promise<PaginatedDoctors> {
    const response = await http.get<PaginatedDoctors>('/doctors', { params: filters })
    return response.data
  },

  async get(id: string): Promise<Doctor> {
    const response = await http.get<{ data: Doctor }>(`/doctors/${id}`)
    return response.data.data
  },

  async create(payload: CreateDoctorPayload): Promise<Doctor> {
    const response = await http.post<{ data: Doctor }>('/doctors', payload)
    return response.data.data
  },

  async update(id: string, payload: UpdateDoctorPayload): Promise<Doctor> {
    const response = await http.put<{ data: Doctor }>(`/doctors/${id}`, payload)
    return response.data.data
  },

  async toggleActive(id: string): Promise<Doctor> {
    const response = await http.patch<{ data: Doctor }>(`/doctors/${id}/toggle-active`)
    return response.data.data
  },

  async remove(id: string): Promise<void> {
    await http.delete(`/doctors/${id}`)
  },
}
