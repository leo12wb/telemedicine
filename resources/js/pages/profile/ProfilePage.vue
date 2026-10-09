<template>
  <div class="max-w-lg">
    <h2 class="text-xl font-semibold text-gray-800 mb-6">Meu perfil</h2>

    <div class="bg-white rounded-lg shadow p-6 space-y-4">
      <form @submit.prevent="handleUpdate" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Nome</label>
          <input
            v-model="form.name"
            type="text"
            required
            class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">E-mail</label>
          <input
            v-model="form.email"
            type="email"
            required
            class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">
            Nova senha <span class="text-gray-400 font-normal">(deixe em branco para manter)</span>
          </label>
          <input
            v-model="form.password"
            type="password"
            class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Perfil</label>
          <input
            :value="authStore.user?.role"
            disabled
            class="mt-1 block w-full border border-gray-200 rounded-md px-3 py-2 bg-gray-50 text-gray-500 cursor-not-allowed"
          />
        </div>

        <p v-if="successMessage" class="text-sm text-green-600">{{ successMessage }}</p>
        <p v-if="errorMessage" class="text-sm text-red-600">{{ errorMessage }}</p>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:opacity-50"
        >
          {{ loading ? 'Salvando...' : 'Salvar alterações' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUserStore } from '@/stores/user'

const authStore = useAuthStore()
const userStore = useUserStore()

const loading = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

const form = ref({
  name: authStore.user?.name ?? '',
  email: authStore.user?.email ?? '',
  password: '',
})

onMounted(() => {
  form.value.name = authStore.user?.name ?? ''
  form.value.email = authStore.user?.email ?? ''
})

async function handleUpdate() {
  if (!authStore.user) return

  loading.value = true
  successMessage.value = ''
  errorMessage.value = ''

  try {
    const payload: { name: string; email: string; password?: string } = {
      name: form.value.name,
      email: form.value.email,
    }
    if (form.value.password) {
      payload.password = form.value.password
    }

    const updated = await userStore.updateUser(authStore.user.id, payload)
    authStore.user = updated
    form.value.password = ''
    successMessage.value = 'Perfil atualizado com sucesso.'
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } }
    errorMessage.value = err.response?.data?.message ?? 'Erro ao atualizar perfil.'
  } finally {
    loading.value = false
  }
}
</script>
