import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Settings } from '@/types/setting'
import { settingService } from '@/services/setting'

export const useSettingStore = defineStore('setting', () => {
  const settings = ref<Settings | null>(null)
  const loading = ref(false)

  async function fetchSettings() {
    loading.value = true
    try {
      settings.value = await settingService.get()
    } finally {
      loading.value = false
    }
  }

  async function updateSettings(payload: Partial<Settings>) {
    const updated = await settingService.update(payload)
    settings.value = updated
    return updated
  }

  return { settings, loading, fetchSettings, updateSettings }
})
