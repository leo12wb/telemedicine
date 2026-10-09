import http from './http'
import type { Patient } from '@/types/patient'

export interface PaginatedPatients {
  data: Patient[]
  meta: { current_page: number; last_page: number; per_page: number; total: number }
}

export interface CreatePatientPayload {
  name: string
  email: string
  password: string
  cpf?: string
  birth_date?: string
  phone?: string
  health_insurance?: string
  health_insurance_number?: string
}

export interface UpdatePatientPayload {
  cpf?: string | null
  birth_date?: string | null
  phone?: string | null
  health_insurance?: string | null
  health_insurance_number?: string | null
}

export const patientService = {
  async list(filters: { search?: string; page?: number } = {}): Promise<PaginatedPatients> {
    const response = await http.get<PaginatedPatients>('/patients', { params: filters })
    return response.data
  },

  async profile(): Promise<Patient> {
    const response = await http.get<{ data: Patient }>('/patients/profile')
    return response.data.data
  },

  async get(id: string): Promise<Patient> {
    const response = await http.get<{ data: Patient }>(`/patients/${id}`)
    return response.data.data
  },

  async create(payload: CreatePatientPayload): Promise<Patient> {
    const response = await http.post<{ data: Patient }>('/patients', payload)
    return response.data.data
  },

  async update(id: string, payload: UpdatePatientPayload): Promise<Patient> {
    const response = await http.put<{ data: Patient }>(`/patients/${id}`, payload)
    return response.data.data
  },

  async remove(id: string): Promise<void> {
    await http.delete(`/patients/${id}`)
  },
}
