import http from './http'
import type { User } from '@/types/user'

export interface UserFilters {
  role?: string
  is_active?: boolean
  search?: string
  page?: number
}

export interface PaginatedUsers {
  data: User[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
  links: {
    first: string | null
    last: string | null
    prev: string | null
    next: string | null
  }
}

export interface CreateUserPayload {
  name: string
  email: string
  password: string
  role: string
  is_active?: boolean
}

export interface UpdateUserPayload {
  name?: string
  email?: string
  password?: string
  role?: string
  is_active?: boolean
}

export const userService = {
  async list(filters: UserFilters = {}): Promise<PaginatedUsers> {
    const response = await http.get<PaginatedUsers>('/users', { params: filters })
    return response.data
  },

  async get(id: string): Promise<User> {
    const response = await http.get<{ data: User }>(`/users/${id}`)
    return response.data.data
  },

  async create(payload: CreateUserPayload): Promise<User> {
    const response = await http.post<{ data: User }>('/users', payload)
    return response.data.data
  },

  async update(id: string, payload: UpdateUserPayload): Promise<User> {
    const response = await http.put<{ data: User }>(`/users/${id}`, payload)
    return response.data.data
  },

  async toggleActive(id: string): Promise<User> {
    const response = await http.patch<{ data: User }>(`/users/${id}/toggle-active`)
    return response.data.data
  },

  async remove(id: string): Promise<void> {
    await http.delete(`/users/${id}`)
  },
}
