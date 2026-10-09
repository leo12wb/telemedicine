import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Doctor } from '@/types/doctor'
import { doctorService, type DoctorFilters, type CreateDoctorPayload, type UpdateDoctorPayload } from '@/services/doctor'

export const useDoctorStore = defineStore('doctor', () => {
  const doctors = ref<Doctor[]>([])
  const currentDoctor = ref<Doctor | null>(null)
  const loading = ref(false)
  const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

  async function fetchDoctors(filters: DoctorFilters = {}) {
    loading.value = true
    try {
      const result = await doctorService.list(filters)
      doctors.value = result.data
      meta.value = result.meta
    } finally {
      loading.value = false
    }
  }

  async function fetchDoctor(id: string) {
    loading.value = true
    try {
      currentDoctor.value = await doctorService.get(id)
    } finally {
      loading.value = false
    }
  }

  async function createDoctor(payload: CreateDoctorPayload) {
    const doctor = await doctorService.create(payload)
    doctors.value.unshift(doctor)
    meta.value.total++
    return doctor
  }

  async function updateDoctor(id: string, payload: UpdateDoctorPayload) {
    const updated = await doctorService.update(id, payload)
    const index = doctors.value.findIndex((d) => d.id === id)
    if (index !== -1) doctors.value[index] = updated
    if (currentDoctor.value?.id === id) currentDoctor.value = updated
    return updated
  }

  async function toggleActive(id: string) {
    const updated = await doctorService.toggleActive(id)
    const index = doctors.value.findIndex((d) => d.id === id)
    if (index !== -1) doctors.value[index] = updated
    return updated
  }

  async function removeDoctor(id: string) {
    await doctorService.remove(id)
    doctors.value = doctors.value.filter((d) => d.id !== id)
    meta.value.total--
  }

  async function uploadPhoto(id: string, file: File) {
    const updated = await doctorService.uploadPhoto(id, file)
    const index = doctors.value.findIndex((d) => d.id === id)
    if (index !== -1) doctors.value[index] = updated
    if (currentDoctor.value?.id === id) currentDoctor.value = updated
    return updated
  }

  async function deletePhoto(id: string) {
    const updated = await doctorService.deletePhoto(id)
    const index = doctors.value.findIndex((d) => d.id === id)
    if (index !== -1) doctors.value[index] = updated
    if (currentDoctor.value?.id === id) currentDoctor.value = updated
    return updated
  }

  return {
    doctors,
    currentDoctor,
    loading,
    meta,
    fetchDoctors,
    fetchDoctor,
    createDoctor,
    updateDoctor,
    toggleActive,
    removeDoctor,
    uploadPhoto,
    deletePhoto,
  }
})
