import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { User } from '@/types/user'
import { userService, type UserFilters, type CreateUserPayload, type UpdateUserPayload } from '@/services/user'

export const useUserStore = defineStore('user', () => {
  const users = ref<User[]>([])
  const currentUser = ref<User | null>(null)
  const loading = ref(false)
  const meta = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })

  async function fetchUsers(filters: UserFilters = {}) {
    loading.value = true
    try {
      const result = await userService.list(filters)
      users.value = result.data
      meta.value = result.meta
    } finally {
      loading.value = false
    }
  }

  async function fetchUser(id: string) {
    loading.value = true
    try {
      currentUser.value = await userService.get(id)
    } finally {
      loading.value = false
    }
  }

  async function createUser(payload: CreateUserPayload) {
    const user = await userService.create(payload)
    users.value.unshift(user)
    meta.value.total++
    return user
  }

  async function updateUser(id: string, payload: UpdateUserPayload) {
    const updated = await userService.update(id, payload)
    const index = users.value.findIndex((u) => u.id === id)
    if (index !== -1) users.value[index] = updated
    if (currentUser.value?.id === id) currentUser.value = updated
    return updated
  }

  async function toggleActive(id: string) {
    const updated = await userService.toggleActive(id)
    const index = users.value.findIndex((u) => u.id === id)
    if (index !== -1) users.value[index] = updated
    return updated
  }

  async function removeUser(id: string) {
    await userService.remove(id)
    users.value = users.value.filter((u) => u.id !== id)
    meta.value.total--
  }

  return {
    users,
    currentUser,
    loading,
    meta,
    fetchUsers,
    fetchUser,
    createUser,
    updateUser,
    toggleActive,
    removeUser,
  }
})
