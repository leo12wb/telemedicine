import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import type { User } from '@/types/user'

// Mock do serviço de autenticação
vi.mock('@/services/auth', () => ({
  authService: {
    me: vi.fn(),
    login: vi.fn(),
    register: vi.fn(),
    logout: vi.fn(),
  },
}))

const mockUser: User = {
  id: 'uuid-123',
  name: 'Maria Souza',
  email: 'maria@example.com',
  role: 'paciente',
  is_active: true,
  email_verified_at: null,
  created_at: '2024-01-01T00:00:00Z',
  updated_at: '2024-01-01T00:00:00Z',
}

// Importação do mock após vi.mock
import { authService } from '@/services/auth'

describe('useAuthStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
  })

  describe('init()', () => {
    it('valida token existente e popula o usuário', async () => {
      localStorage.setItem('auth_token', 'valid-token')
      vi.mocked(authService.me).mockResolvedValueOnce(mockUser)

      const auth = useAuthStore()
      await auth.init()

      expect(authService.me).toHaveBeenCalledOnce()
      expect(auth.user).toEqual(mockUser)
      expect(auth.isAuthenticated).toBe(true)
      expect(auth.initialized).toBe(true)
    })

    it('limpa auth quando token é inválido', async () => {
      localStorage.setItem('auth_token', 'expired-token')
      vi.mocked(authService.me).mockRejectedValueOnce(new Error('401'))

      const auth = useAuthStore()
      await auth.init()

      expect(auth.user).toBeNull()
      expect(auth.token).toBeNull()
      expect(auth.isAuthenticated).toBe(false)
      expect(auth.initialized).toBe(true)
      expect(localStorage.getItem('auth_token')).toBeNull()
    })

    it('não chama me() sem token armazenado', async () => {
      const auth = useAuthStore()
      await auth.init()

      expect(authService.me).not.toHaveBeenCalled()
      expect(auth.user).toBeNull()
      expect(auth.initialized).toBe(true)
    })
  })

  describe('login()', () => {
    it('armazena token e usuário após login bem-sucedido', async () => {
      vi.mocked(authService.login).mockResolvedValueOnce({
        user: mockUser,
        token: 'new-token',
      })

      const auth = useAuthStore()
      await auth.login('maria@example.com', 'senha123')

      expect(authService.login).toHaveBeenCalledWith({
        email: 'maria@example.com',
        password: 'senha123',
      })
      expect(auth.user).toEqual(mockUser)
      expect(auth.token).toBe('new-token')
      expect(localStorage.getItem('auth_token')).toBe('new-token')
      expect(auth.isAuthenticated).toBe(true)
    })

    it('propaga erro em caso de credenciais inválidas', async () => {
      const apiError = { response: { data: { message: 'Credenciais inválidas.' } } }
      vi.mocked(authService.login).mockRejectedValueOnce(apiError)

      const auth = useAuthStore()

      await expect(auth.login('maria@example.com', 'errada')).rejects.toEqual(apiError)
      expect(auth.user).toBeNull()
      expect(auth.isAuthenticated).toBe(false)
    })
  })

  describe('register()', () => {
    it('armazena token e usuário após registro bem-sucedido', async () => {
      vi.mocked(authService.register).mockResolvedValueOnce({
        user: mockUser,
        token: 'new-token',
      })

      const auth = useAuthStore()
      await auth.register('Maria Souza', 'maria@example.com', 'senha@123', 'senha@123')

      expect(authService.register).toHaveBeenCalledWith({
        name: 'Maria Souza',
        email: 'maria@example.com',
        password: 'senha@123',
        password_confirmation: 'senha@123',
      })
      expect(auth.user).toEqual(mockUser)
      expect(auth.token).toBe('new-token')
      expect(auth.isAuthenticated).toBe(true)
    })
  })

  describe('logout()', () => {
    it('chama API e limpa estado local', async () => {
      vi.mocked(authService.logout).mockResolvedValueOnce(undefined)

      const auth = useAuthStore()
      auth.user = mockUser
      auth.token = 'some-token'
      localStorage.setItem('auth_token', 'some-token')

      await auth.logout()

      expect(authService.logout).toHaveBeenCalledOnce()
      expect(auth.user).toBeNull()
      expect(auth.token).toBeNull()
      expect(localStorage.getItem('auth_token')).toBeNull()
      expect(auth.isAuthenticated).toBe(false)
    })

    it('limpa estado mesmo se a API retornar erro', async () => {
      vi.mocked(authService.logout).mockRejectedValueOnce(new Error('network error'))

      const auth = useAuthStore()
      auth.user = mockUser
      auth.token = 'some-token'
      localStorage.setItem('auth_token', 'some-token')

      await auth.logout()

      expect(auth.user).toBeNull()
      expect(auth.token).toBeNull()
      expect(auth.isAuthenticated).toBe(false)
    })
  })

  describe('isAuthenticated', () => {
    it('é false quando não há usuário', () => {
      const auth = useAuthStore()
      expect(auth.isAuthenticated).toBe(false)
    })

    it('é true quando usuário está definido', () => {
      const auth = useAuthStore()
      auth.user = mockUser
      expect(auth.isAuthenticated).toBe(true)
    })
  })
})
