<template>
  <div class="min-h-screen bg-gray-50">
    <nav class="bg-white shadow px-6 py-4 flex items-center justify-between">
      <div class="flex items-center gap-6">
        <h1 class="text-lg font-semibold text-gray-900">Telemedicina</h1>
        <RouterLink to="/dashboard" class="text-sm text-gray-600 hover:text-gray-900">Dashboard</RouterLink>
        <RouterLink to="/doctors" class="text-sm text-gray-600 hover:text-gray-900">Médicos</RouterLink>
        <RouterLink
          v-if="authStore.user?.role === 'admin' || authStore.user?.role === 'medico'"
          to="/patients"
          class="text-sm text-gray-600 hover:text-gray-900"
        >Pacientes</RouterLink>
        <RouterLink
          v-if="authStore.user?.role === 'paciente'"
          to="/patient-profile"
          class="text-sm text-gray-600 hover:text-gray-900"
        >Meu perfil clínico</RouterLink>
        <RouterLink
          v-if="authStore.user?.role === 'admin'"
          to="/users"
          class="text-sm text-gray-600 hover:text-gray-900"
        >Usuários</RouterLink>
        <RouterLink
          v-if="authStore.user?.role === 'admin'"
          to="/specialties"
          class="text-sm text-gray-600 hover:text-gray-900"
        >Especialidades</RouterLink>
        <RouterLink to="/appointments" class="text-sm text-gray-600 hover:text-gray-900">Consultas</RouterLink>
        <RouterLink
          to="/availability"
          class="text-sm text-gray-600 hover:text-gray-900"
        >Disponibilidade</RouterLink>
        <RouterLink
          v-if="authStore.user?.role === 'admin'"
          to="/settings"
          class="text-sm text-gray-600 hover:text-gray-900"
        >Configurações</RouterLink>
      </div>
      <div class="flex items-center gap-4">
        <RouterLink to="/profile" class="text-sm text-gray-600 hover:text-gray-900">
          {{ authStore.user?.name }}
        </RouterLink>
        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">{{ authStore.user?.role }}</span>
        <button @click="handleLogout" class="text-sm text-red-600 hover:underline">Sair</button>
      </div>
    </nav>

    <main class="max-w-7xl mx-auto px-6 py-8">
      <RouterView />
    </main>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>
