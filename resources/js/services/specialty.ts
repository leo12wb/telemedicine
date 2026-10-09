import http from './http'
import type { Specialty } from '@/types/specialty'

export interface PaginatedSpecialties {
  data: Specialty[]
  meta: { current_page: number; last_page: number; per_page: number; total: number }
}

export interface SpecialtyPayload {
  name: string
  description?: string | null
  is_active?: boolean
}

export const specialtyService = {
  async list(): Promise<PaginatedSpecialties> {
    const response = await http.get<PaginatedSpecialties>('/specialties')
    return response.data
  },

  async listActive(): Promise<Specialty[]> {
    const response = await http.get<{ data: Specialty[] }>('/specialties/active')
    return response.data.data
  },

  async get(id: string): Promise<Specialty> {
    const response = await http.get<{ data: Specialty }>(`/specialties/${id}`)
    return response.data.data
  },

  async create(payload: SpecialtyPayload): Promise<Specialty> {
    const response = await http.post<{ data: Specialty }>('/specialties', payload)
    return response.data.data
  },

  async update(id: string, payload: Partial<SpecialtyPayload>): Promise<Specialty> {
    const response = await http.put<{ data: Specialty }>(`/specialties/${id}`, payload)
    return response.data.data
  },

  async toggleActive(id: string): Promise<Specialty> {
    const response = await http.patch<{ data: Specialty }>(`/specialties/${id}/toggle-active`)
    return response.data.data
  },

  async remove(id: string): Promise<void> {
    await http.delete(`/specialties/${id}`)
  },
}
