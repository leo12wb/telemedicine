export type AppointmentStatus =
  | 'agendada'
  | 'em_andamento'
  | 'concluida'
  | 'cancelada'
  | 'paciente_ausente'

export interface Appointment {
  id: string
  doctor: { id: string; name: string; crm: string; crm_uf: string; photo_url: string | null } | null
  patient: { id: string; name: string } | null
  scheduled_date: string
  scheduled_time: string
  duration_minutes: number
  status: AppointmentStatus
  status_label: string
  notes: string | null
  cancellation_reason: string | null
  started_at: string | null
  ended_at: string | null
  created_at: string
}

export const STATUS_COLORS: Record<AppointmentStatus, string> = {
  agendada:        'bg-blue-100 text-blue-800',
  em_andamento:    'bg-yellow-100 text-yellow-800',
  concluida:       'bg-green-100 text-green-800',
  cancelada:       'bg-red-100 text-red-800',
  paciente_ausente:'bg-gray-100 text-gray-600',
}
