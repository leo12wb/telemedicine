import http from './http'
import type { User } from '@/types/user'

interface LoginPayload {
  email: string
  password: string
}

interface RegisterPayload {
  name: string
  email: string
  password: string
  password_confirmation: string
}

interface AuthResponse {
  user: User
  token: string
}

export const authService = {
  async login(payload: LoginPayload): Promise<AuthResponse> {
    const response = await http.post<{ data: AuthResponse }>('/auth/login', payload)
    return response.data.data
  },

  async register(payload: RegisterPayload): Promise<AuthResponse> {
    const response = await http.post<{ data: AuthResponse }>('/auth/register', payload)
    return response.data.data
  },

  async logout(): Promise<void> {
    await http.post('/auth/logout')
  },

  async me(): Promise<User> {
    const response = await http.get<{ data: User }>('/auth/me')
    return response.data.data
  },
}
