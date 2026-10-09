<template>
  <div class="min-h-screen bg-gray-50">
    <nav class="bg-white shadow px-6 py-4 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">Sistema de Telemedicina</h1>
      <div class="flex items-center gap-4">
        <span class="text-sm text-gray-600">{{ auth.user?.name }}</span>
        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">{{ auth.user?.role }}</span>
        <button
          @click="handleLogout"
          class="text-sm text-red-600 hover:underline"
        >
          Sair
        </button>
      </div>
    </nav>

    <main class="max-w-7xl mx-auto px-6 py-8">
      <h2 class="text-xl font-semibold text-gray-800 mb-4">
        Bem-vindo, {{ auth.user?.name }}!
      </h2>
      <p class="text-gray-600">
        Infraestrutura configurada com sucesso. Os módulos serão implementados na Fase 5.
      </p>
    </main>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>
