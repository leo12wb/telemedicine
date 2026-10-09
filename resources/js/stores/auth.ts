import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/types/user'
import { authService } from '@/services/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const initialized = ref(false)

  // Após init(), isAuthenticated é confiável: user só é definido após validação com o backend
  const isAuthenticated = computed(() => !!user.value)

  function setToken(newToken: string) {
    token.value = newToken
    localStorage.setItem('auth_token', newToken)
  }

  function clearAuth() {
    user.value = null
    token.value = null
    localStorage.removeItem('auth_token')
  }

  /**
   * Inicializa o estado de auth ao carregar a aplicação.
   * Se existir token no localStorage, valida com o backend.
   * Deve ser chamado antes de montar o router.
   */
  async function init() {
    if (token.value) {
      try {
        user.value = await authService.me()
      } catch {
        clearAuth()
      }
    }
    initialized.value = true
  }

  async function login(email: string, password: string) {
    const data = await authService.login({ email, password })
    setToken(data.token)
    user.value = data.user
  }

  async function register(
    name: string,
    email: string,
    password: string,
    password_confirmation: string,
  ) {
    const data = await authService.register({ name, email, password, password_confirmation })
    setToken(data.token)
    user.value = data.user
  }

  async function logout() {
    try {
      await authService.logout()
    } catch {
      // Ignora erro de rede — sessão local é encerrada de qualquer forma
    } finally {
      clearAuth()
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    initialized,
    init,
    login,
    register,
    clearAuth,
    logout,
  }
})
