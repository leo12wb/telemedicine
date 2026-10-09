export type UserRole = 'admin' | 'medico' | 'paciente'

export interface User {
  id: string
  name: string
  email: string
  role: UserRole
  is_active: boolean
  email_verified_at: string | null
  created_at: string
  updated_at: string
}
