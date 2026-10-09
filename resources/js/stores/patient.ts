import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Patient } from '@/types/patient'
import { patientService, type CreatePatientPayload, type UpdatePatientPayload } from '@/services/patient'

export const usePatientStore = defineStore('patient', () => {
  const patients = ref<Patient[]>([])
  const ownProfile = ref<Patient | null>(null)
  const loading = ref(false)
  const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

  async function fetchPatients(filters: { search?: string; page?: number } = {}) {
    loading.value = true
    try {
      const result = await patientService.list(filters)
      patients.value = result.data
      meta.value = result.meta
    } finally {
      loading.value = false
    }
  }

  async function fetchProfile() {
    loading.value = true
    try {
      ownProfile.value = await patientService.profile()
    } finally {
      loading.value = false
    }
  }

  async function createPatient(payload: CreatePatientPayload) {
    const patient = await patientService.create(payload)
    patients.value.unshift(patient)
    meta.value.total++
    return patient
  }

  async function updatePatient(id: string, payload: UpdatePatientPayload) {
    const updated = await patientService.update(id, payload)
    const index = patients.value.findIndex((p) => p.id === id)
    if (index !== -1) patients.value[index] = updated
    if (ownProfile.value?.id === id) ownProfile.value = updated
    return updated
  }

  async function removePatient(id: string) {
    await patientService.remove(id)
    patients.value = patients.value.filter((p) => p.id !== id)
    meta.value.total--
  }

  return {
    patients,
    ownProfile,
    loading,
    meta,
    fetchPatients,
    fetchProfile,
    createPatient,
    updatePatient,
    removePatient,
  }
})
