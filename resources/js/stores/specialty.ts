import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Specialty } from '@/types/specialty'
import { specialtyService, type SpecialtyPayload } from '@/services/specialty'

export const useSpecialtyStore = defineStore('specialty', () => {
  const specialties = ref<Specialty[]>([])
  const activeSpecialties = ref<Specialty[]>([])
  const loading = ref(false)
  const meta = ref({ current_page: 1, last_page: 1, per_page: 50, total: 0 })

  async function fetchSpecialties() {
    loading.value = true
    try {
      const result = await specialtyService.list()
      specialties.value = result.data
      meta.value = result.meta
    } finally {
      loading.value = false
    }
  }

  async function fetchActive() {
    activeSpecialties.value = await specialtyService.listActive()
  }

  async function createSpecialty(payload: SpecialtyPayload) {
    const specialty = await specialtyService.create(payload)
    specialties.value.push(specialty)
    if (specialty.is_active) activeSpecialties.value.push(specialty)
    meta.value.total++
    return specialty
  }

  async function updateSpecialty(id: string, payload: Partial<SpecialtyPayload>) {
    const updated = await specialtyService.update(id, payload)
    const index = specialties.value.findIndex((s) => s.id === id)
    if (index !== -1) specialties.value[index] = updated
    activeSpecialties.value = activeSpecialties.value.filter((s) => s.id !== id)
    if (updated.is_active) activeSpecialties.value.push(updated)
    return updated
  }

  async function toggleActive(id: string) {
    const updated = await specialtyService.toggleActive(id)
    const index = specialties.value.findIndex((s) => s.id === id)
    if (index !== -1) specialties.value[index] = updated
    activeSpecialties.value = activeSpecialties.value.filter((s) => s.id !== id)
    if (updated.is_active) activeSpecialties.value.push(updated)
    return updated
  }

  async function removeSpecialty(id: string) {
    await specialtyService.remove(id)
    specialties.value = specialties.value.filter((s) => s.id !== id)
    activeSpecialties.value = activeSpecialties.value.filter((s) => s.id !== id)
    meta.value.total--
  }

  return {
    specialties,
    activeSpecialties,
    loading,
    meta,
    fetchSpecialties,
    fetchActive,
    createSpecialty,
    updateSpecialty,
    toggleActive,
    removeSpecialty,
  }
})
