import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { AvailabilitySlot, DoctorBlock, DoctorSchedule } from '@/types/availability'
import {
  availabilityService,
  type CreateBlockPayload,
  type CreateSchedulePayload,
  type UpdateSchedulePayload,
} from '@/services/availability'

export const useAvailabilityStore = defineStore('availability', () => {
  const schedules = ref<DoctorSchedule[]>([])
  const blocks = ref<DoctorBlock[]>([])
  const slots = ref<AvailabilitySlot[]>([])
  const loading = ref(false)

  // ─── Schedules ─────────────────────────────────────────────────────────────

  async function fetchSchedules(doctorId: string) {
    loading.value = true
    try {
      schedules.value = await availabilityService.listSchedules(doctorId)
    } finally {
      loading.value = false
    }
  }

  async function createSchedule(doctorId: string, payload: CreateSchedulePayload) {
    const schedule = await availabilityService.createSchedule(doctorId, payload)
    schedules.value.push(schedule)
    return schedule
  }

  async function updateSchedule(doctorId: string, scheduleId: string, payload: UpdateSchedulePayload) {
    const updated = await availabilityService.updateSchedule(doctorId, scheduleId, payload)
    const index = schedules.value.findIndex((s) => s.id === scheduleId)
    if (index !== -1) schedules.value[index] = updated
    return updated
  }

  async function removeSchedule(doctorId: string, scheduleId: string) {
    await availabilityService.removeSchedule(doctorId, scheduleId)
    schedules.value = schedules.value.filter((s) => s.id !== scheduleId)
  }

  // ─── Blocks ─────────────────────────────────────────────────────────────────

  async function fetchBlocks(doctorId: string, from?: string, to?: string) {
    loading.value = true
    try {
      blocks.value = await availabilityService.listBlocks(doctorId, { from, to })
    } finally {
      loading.value = false
    }
  }

  async function createBlock(doctorId: string, payload: CreateBlockPayload) {
    const block = await availabilityService.createBlock(doctorId, payload)
    blocks.value.push(block)
    return block
  }

  async function removeBlock(doctorId: string, blockId: string) {
    await availabilityService.removeBlock(doctorId, blockId)
    blocks.value = blocks.value.filter((b) => b.id !== blockId)
  }

  // ─── Slots ──────────────────────────────────────────────────────────────────

  async function fetchSlots(doctorId: string, date: string) {
    loading.value = true
    try {
      slots.value = await availabilityService.getSlots(doctorId, date)
    } finally {
      loading.value = false
    }
  }

  return {
    schedules,
    blocks,
    slots,
    loading,
    fetchSchedules,
    createSchedule,
    updateSchedule,
    removeSchedule,
    fetchBlocks,
    createBlock,
    removeBlock,
    fetchSlots,
  }
})
