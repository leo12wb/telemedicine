import http from './http'

export interface AppointmentSummary {
  id: string
  scheduled_date: string
  scheduled_time: string
  status: string
  status_label: string
  doctor_name: string | null
  patient_name: string | null
}

export interface AdminDashboard {
  totals: {
    doctors: number
    doctors_active: number
    patients: number
    users: number
  }
  appointments_by_status: Record<string, number>
  today_appointments: AppointmentSummary[]
  upcoming_appointments: AppointmentSummary[]
}

export interface DoctorDashboard {
  today_appointments: AppointmentSummary[]
  upcoming_appointments: AppointmentSummary[]
  week_summary: Record<string, number>
  absent_this_week: number
}

export interface PatientDashboard {
  upcoming_appointments: AppointmentSummary[]
  recent_history: AppointmentSummary[]
  total_concluded: number
}

export const dashboardService = {
  async get(): Promise<AdminDashboard | DoctorDashboard | PatientDashboard> {
    const response = await http.get<{ data: AdminDashboard | DoctorDashboard | PatientDashboard }>('/dashboard')
    return response.data.data
  },
}
