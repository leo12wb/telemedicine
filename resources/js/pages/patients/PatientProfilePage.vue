<template>
  <div class="max-w-lg">
    <h2 class="text-xl font-semibold text-gray-800 mb-6">Meu perfil clínico</h2>

    <div v-if="store.loading" class="text-sm text-gray-500">Carregando...</div>

    <div v-else class="bg-white rounded-lg shadow p-6">
      <form @submit.prevent="handleSave" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">CPF <span class="text-gray-400 font-normal">(opcional)</span></label>
          <input
            v-model="form.cpf"
            type="text"
            placeholder="000.000.000-00"
            class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Data de nascimento</label>
          <input
            v-model="form.birth_date"
            type="date"
            class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Telefone</label>
          <input
            v-model="form.phone"
            type="text"
            class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Convênio</label>
          <input
            v-model="form.health_insurance"
            type="text"
            class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Número do convênio</label>
          <input
            v-model="form.health_insurance_number"
            type="text"
            class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <p v-if="successMessage" class="text-sm text-green-600">{{ successMessage }}</p>
        <p v-if="errorMessage" class="text-sm text-red-600">{{ errorMessage }}</p>

        <button
          type="submit"
          :disabled="saving"
          class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:opacity-50"
        >
          {{ saving ? 'Salvando...' : 'Salvar' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { usePatientStore } from '@/stores/patient'

const store = usePatientStore()
const saving = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

const form = ref({
  cpf: '',
  birth_date: '',
  phone: '',
  health_insurance: '',
  health_insurance_number: '',
})

onMounted(async () => {
  await store.fetchProfile()
  if (store.ownProfile) {
    form.value = {
      cpf: store.ownProfile.cpf ?? '',
      birth_date: store.ownProfile.birth_date ?? '',
      phone: store.ownProfile.phone ?? '',
      health_insurance: store.ownProfile.health_insurance ?? '',
      health_insurance_number: store.ownProfile.health_insurance_number ?? '',
    }
  }
})

async function handleSave() {
  if (!store.ownProfile) return
  saving.value = true
  successMessage.value = ''
  errorMessage.value = ''
  try {
    await store.updatePatient(store.ownProfile.id, {
      cpf: form.value.cpf || null,
      birth_date: form.value.birth_date || null,
      phone: form.value.phone || null,
      health_insurance: form.value.health_insurance || null,
      health_insurance_number: form.value.health_insurance_number || null,
    })
    successMessage.value = 'Perfil clínico atualizado com sucesso.'
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } }
    errorMessage.value = err.response?.data?.message ?? 'Erro ao salvar.'
  } finally {
    saving.value = false
  }
}
</script>
