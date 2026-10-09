import { describe, it, expect, vi } from 'vitest'
import type { AdminDashboard, DoctorDashboard, PatientDashboard } from '@/services/dashboard'

vi.mock('@/services/dashboard', () => ({
  dashboardService: {
    get: vi.fn(),
  },
}))

import { dashboardService } from '@/services/dashboard'

describe('dashboardService.get()', () => {
  it('retorna estrutura admin com totals e appointments_by_status', async () => {
    const adminPayload: AdminDashboard = {
      totals: { doctors: 5, doctors_active: 4, patients: 20, users: 25 },
      appointments_by_status: { agendada: 3, concluida: 10, cancelada: 2 },
      today_appointments: [],
      upcoming_appointments: [],
    }
    vi.mocked(dashboardService.get).mockResolvedValueOnce(adminPayload)

    const data = await dashboardService.get() as AdminDashboard

    expect(data.totals.doctors).toBe(5)
    expect(data.totals.doctors_active).toBe(4)
    expect(data.appointments_by_status['concluida']).toBe(10)
  })

  it('retorna estrutura doctor com today_appointments e absent_this_week', async () => {
    const doctorPayload: DoctorDashboard = {
      today_appointments: [
        { id: '1', scheduled_date: '2030-01-10', scheduled_time: '09:00', status: 'agendada', status_label: 'Agendada', doctor_name: null, patient_name: 'Pedro' },
      ],
      upcoming_appointments: [],
      week_summary: { agendada: 2, concluida: 1 },
      absent_this_week: 1,
    }
    vi.mocked(dashboardService.get).mockResolvedValueOnce(doctorPayload)

    const data = await dashboardService.get() as DoctorDashboard

    expect(data.today_appointments).toHaveLength(1)
    expect(data.absent_this_week).toBe(1)
  })

  it('retorna estrutura patient com upcoming_appointments e total_concluded', async () => {
    const patientPayload: PatientDashboard = {
      upcoming_appointments: [
        { id: '1', scheduled_date: '2030-01-15', scheduled_time: '10:00', status: 'agendada', status_label: 'Agendada', doctor_name: 'Dr. Ana', patient_name: null },
      ],
      recent_history: [],
      total_concluded: 7,
    }
    vi.mocked(dashboardService.get).mockResolvedValueOnce(patientPayload)

    const data = await dashboardService.get() as PatientDashboard

    expect(data.upcoming_appointments).toHaveLength(1)
    expect(data.total_concluded).toBe(7)
  })
})
