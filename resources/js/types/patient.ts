import type { User } from './user'

export interface Patient {
  id: string
  cpf: string | null
  birth_date: string | null
  phone: string | null
  health_insurance: string | null
  health_insurance_number: string | null
  user: User
  created_at: string
}
