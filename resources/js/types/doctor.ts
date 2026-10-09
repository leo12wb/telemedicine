import type { User } from './user'
import type { Specialty } from './specialty'

export interface Doctor {
  id: string
  crm: string
  crm_uf: string
  phone: string | null
  bio: string | null
  photo_url: string | null
  is_active: boolean
  user: User
  specialties: Specialty[]
  created_at: string
}
