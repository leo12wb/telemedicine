<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-semibold text-gray-800">Pacientes</h2>
      <button
        v-if="authStore.user?.role === 'admin'"
        @click="showCreateModal = true"
        class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700"
      >
        Novo paciente
      </button>
    </div>

    <!-- Filtro -->
    <div class="mb-4">
      <input
        v-model="search"
        type="text"
        placeholder="Buscar por nome ou e-mail..."
        class="border border-gray-300 rounded-md px-3 py-2 text-sm w-72 focus:outline-none focus:ring-2 focus:ring-blue-500"
        @input="debouncedFetch"
      />
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">E-mail</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nascimento</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Convênio</th>
            <th v-if="authStore.user?.role === 'admin'" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="store.loading">
            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Carregando...</td>
          </tr>
          <tr v-else-if="store.patients.length === 0">
            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Nenhum paciente encontrado.</td>
          </tr>
          <tr v-for="p in store.patients" :key="p.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ p.user.name }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ p.user.email }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(p.birth_date) }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ p.health_insurance ?? '—' }}</td>
            <td v-if="authStore.user?.role === 'admin'" class="px-6 py-4 text-right text-sm">
              <button @click="handleDelete(p.id)" class="text-red-600 hover:underline">Excluir</button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="store.meta.last_page > 1" class="px-6 py-3 flex items-center justify-between border-t border-gray-200">
        <span class="text-sm text-gray-700">Total: {{ store.meta.total }} pacientes</span>
        <div class="flex gap-2">
          <button :disabled="store.meta.current_page === 1" @click="changePage(store.meta.current_page - 1)" class="px-3 py-1 text-sm border rounded disabled:opacity-50 hover:bg-gray-50">Anterior</button>
          <span class="px-3 py-1 text-sm">{{ store.meta.current_page }} / {{ store.meta.last_page }}</span>
          <button :disabled="store.meta.current_page === store.meta.last_page" @click="changePage(store.meta.current_page + 1)" class="px-3 py-1 text-sm border rounded disabled:opacity-50 hover:bg-gray-50">Próxima</button>
        </div>
      </div>
    </div>

    <!-- Modal criar paciente (admin) -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl">
        <h3 class="text-lg font-semibold mb-4">Novo paciente</h3>
        <form @submit.prevent="handleCreate" class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700">Nome</label>
            <input v-model="form.name" type="text" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">E-mail</label>
            <input v-model="form.email" type="email" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Senha</label>
            <input v-model="form.password" type="password" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Data de nascimento <span class="text-gray-400 font-normal">(opcional)</span></label>
            <input v-model="form.birth_date" type="date" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Convênio <span class="text-gray-400 font-normal">(opcional)</span></label>
            <input v-model="form.health_insurance" type="text" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showCreateModal = false; formError = ''" class="px-4 py-2 text-sm border rounded-md hover:bg-gray-50">Cancelar</button>
            <button type="submit" :disabled="formLoading" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
              {{ formLoading ? 'Salvando...' : 'Salvar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { usePatientStore } from '@/stores/patient'
import { useAuthStore } from '@/stores/auth'

const store = usePatientStore()
const authStore = useAuthStore()

const search = ref('')
const currentPage = ref(1)
const showCreateModal = ref(false)
const formLoading = ref(false)
const formError = ref('')
const form = ref({ name: '', email: '', password: '', birth_date: '', health_insurance: '' })

let debounceTimer: ReturnType<typeof setTimeout>

function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchPatients, 400)
}

async function fetchPatients() {
  const params: { search?: string; page?: number } = { page: currentPage.value }
  if (search.value) params.search = search.value
  await store.fetchPatients(params)
}

async function changePage(page: number) {
  currentPage.value = page
  await fetchPatients()
}

async function handleDelete(id: string) {
  if (!confirm('Confirma a exclusão deste paciente?')) return
  await store.removePatient(id)
}

async function handleCreate() {
  formLoading.value = true
  formError.value = ''
  try {
    await store.createPatient({
      name: form.value.name,
      email: form.value.email,
      password: form.value.password,
      birth_date: form.value.birth_date || undefined,
      health_insurance: form.value.health_insurance || undefined,
    })
    showCreateModal.value = false
    form.value = { name: '', email: '', password: '', birth_date: '', health_insurance: '' }
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } }
    formError.value = err.response?.data?.message ?? 'Erro ao criar paciente.'
  } finally {
    formLoading.value = false
  }
}

function formatDate(date: string | null): string {
  if (!date) return '—'
  return new Date(date + 'T00:00:00').toLocaleDateString('pt-BR')
}

onMounted(fetchPatients)
</script>
