import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useUserStore } from '@/stores/user'
import type { User } from '@/types/user'

vi.mock('@/services/user', () => ({
  userService: {
    list: vi.fn(),
    get: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    toggleActive: vi.fn(),
    remove: vi.fn(),
  },
}))

import { userService } from '@/services/user'

const makeUser = (overrides: Partial<User> = {}): User => ({
  id: 'uuid-1',
  name: 'João Silva',
  email: 'joao@example.com',
  role: 'paciente',
  is_active: true,
  email_verified_at: null,
  created_at: '2024-01-01T00:00:00Z',
  updated_at: '2024-01-01T00:00:00Z',
  ...overrides,
})

describe('useUserStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  describe('fetchUsers()', () => {
    it('popula a lista de usuários', async () => {
      const users = [makeUser(), makeUser({ id: 'uuid-2', name: 'Maria' })]
      vi.mocked(userService.list).mockResolvedValueOnce({
        data: users,
        meta: { current_page: 1, last_page: 1, per_page: 15, total: 2 },
        links: { first: null, last: null, prev: null, next: null },
      })

      const store = useUserStore()
      await store.fetchUsers()

      expect(store.users).toHaveLength(2)
      expect(store.meta.total).toBe(2)
      expect(store.loading).toBe(false)
    })

    it('passa filtros para o serviço', async () => {
      vi.mocked(userService.list).mockResolvedValueOnce({
        data: [],
        meta: { current_page: 1, last_page: 1, per_page: 15, total: 0 },
        links: { first: null, last: null, prev: null, next: null },
      })

      const store = useUserStore()
      await store.fetchUsers({ role: 'medico', is_active: true })

      expect(userService.list).toHaveBeenCalledWith({ role: 'medico', is_active: true })
    })
  })

  describe('createUser()', () => {
    it('adiciona o novo usuário no início da lista', async () => {
      const existing = makeUser({ id: 'uuid-existing' })
      const created = makeUser({ id: 'uuid-new', name: 'Novo' })

      vi.mocked(userService.create).mockResolvedValueOnce(created)

      const store = useUserStore()
      store.users = [existing]
      store.meta.total = 1

      await store.createUser({ name: 'Novo', email: 'novo@x.com', password: 'pass', role: 'paciente' })

      expect(store.users[0]).toEqual(created)
      expect(store.meta.total).toBe(2)
    })
  })

  describe('updateUser()', () => {
    it('atualiza o usuário na lista', async () => {
      const user = makeUser()
      const updated = makeUser({ name: 'Nome Atualizado' })

      vi.mocked(userService.update).mockResolvedValueOnce(updated)

      const store = useUserStore()
      store.users = [user]

      await store.updateUser(user.id, { name: 'Nome Atualizado' })

      expect(store.users[0].name).toBe('Nome Atualizado')
    })

    it('atualiza currentUser se for o mesmo', async () => {
      const user = makeUser()
      const updated = makeUser({ name: 'Atualizado' })

      vi.mocked(userService.update).mockResolvedValueOnce(updated)

      const store = useUserStore()
      store.currentUser = user

      await store.updateUser(user.id, { name: 'Atualizado' })

      expect(store.currentUser?.name).toBe('Atualizado')
    })
  })

  describe('toggleActive()', () => {
    it('atualiza o status na lista', async () => {
      const user = makeUser({ is_active: true })
      const toggled = makeUser({ is_active: false })

      vi.mocked(userService.toggleActive).mockResolvedValueOnce(toggled)

      const store = useUserStore()
      store.users = [user]

      await store.toggleActive(user.id)

      expect(store.users[0].is_active).toBe(false)
    })
  })

  describe('removeUser()', () => {
    it('remove o usuário da lista', async () => {
      vi.mocked(userService.remove).mockResolvedValueOnce(undefined)

      const store = useUserStore()
      store.users = [makeUser({ id: 'uuid-1' }), makeUser({ id: 'uuid-2' })]
      store.meta.total = 2

      await store.removeUser('uuid-1')

      expect(store.users).toHaveLength(1)
      expect(store.users[0].id).toBe('uuid-2')
      expect(store.meta.total).toBe(1)
    })
  })
})
