<template>
  <div>
    <h2 class="text-xl font-semibold text-gray-800 mb-4">
      Bem-vindo, {{ authStore.user?.name }}!
    </h2>
    <p class="text-gray-600">
      Selecione uma opção no menu para continuar.
    </p>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
</script>
