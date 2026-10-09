import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Appointment } from '@/types/appointment'
import { appointmentService, type AppointmentFilters, type CreateAppointmentPayload } from '@/services/appointment'

export const useAppointmentStore = defineStore('appointment', () => {
  const appointments = ref<Appointment[]>([])
  const current = ref<Appointment | null>(null)
  const loading = ref(false)
  const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

  async function fetchAppointments(filters: AppointmentFilters = {}) {
    loading.value = true
    try {
      const result = await appointmentService.list(filters)
      appointments.value = result.data
      meta.value = result.meta
    } finally {
      loading.value = false
    }
  }

  async function fetchAppointment(id: string) {
    loading.value = true
    try {
      current.value = await appointmentService.get(id)
    } finally {
      loading.value = false
    }
  }

  async function createAppointment(payload: CreateAppointmentPayload) {
    const appt = await appointmentService.create(payload)
    appointments.value.unshift(appt)
    meta.value.total++
    return appt
  }

  function _replace(updated: Appointment) {
    const idx = appointments.value.findIndex((a) => a.id === updated.id)
    if (idx !== -1) appointments.value[idx] = updated
    if (current.value?.id === updated.id) current.value = updated
  }

  async function cancelAppointment(id: string, reason?: string) {
    const updated = await appointmentService.cancel(id, reason)
    _replace(updated)
    return updated
  }

  async function rescheduleAppointment(id: string, date: string, time: string) {
    const updated = await appointmentService.reschedule(id, date, time)
    // The old appointment gets cancelled; a new one is returned
    _replace(updated)
    return updated
  }

  async function startAppointment(id: string) {
    const updated = await appointmentService.start(id)
    _replace(updated)
    return updated
  }

  async function finishAppointment(id: string, outcome: 'concluida' | 'paciente_ausente', notes?: string) {
    const updated = await appointmentService.finish(id, outcome, notes)
    _replace(updated)
    return updated
  }

  async function updateNotes(id: string, notes: string) {
    const updated = await appointmentService.updateNotes(id, notes)
    _replace(updated)
    return updated
  }

  return {
    appointments,
    current,
    loading,
    meta,
    fetchAppointments,
    fetchAppointment,
    createAppointment,
    cancelAppointment,
    rescheduleAppointment,
    startAppointment,
    finishAppointment,
    updateNotes,
  }
})
