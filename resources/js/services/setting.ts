import http from './http'
import type { Settings } from '@/types/setting'

export const settingService = {
  async get(): Promise<Settings> {
    const response = await http.get<{ data: Settings }>('/settings')
    return response.data.data
  },

  async update(payload: Partial<Settings>): Promise<Settings> {
    const response = await http.put<{ data: Settings }>('/settings', payload)
    return response.data.data
  },
}
