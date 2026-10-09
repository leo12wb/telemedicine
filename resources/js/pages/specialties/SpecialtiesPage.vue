<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-semibold text-gray-800">Especialidades</h2>
      <button
        @click="showCreateModal = true"
        class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700"
      >
        Nova especialidade
      </button>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descrição</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="store.loading">
            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Carregando...</td>
          </tr>
          <tr v-else-if="store.specialties.length === 0">
            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">Nenhuma especialidade cadastrada.</td>
          </tr>
          <tr v-for="s in store.specialties" :key="s.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ s.name }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ s.description ?? '—' }}</td>
            <td class="px-6 py-4 text-sm">
              <span
                :class="s.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                class="px-2 py-1 rounded text-xs font-medium"
              >{{ s.is_active ? 'Ativa' : 'Inativa' }}</span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-3">
              <button @click="openEditModal(s)" class="text-blue-600 hover:underline">Editar</button>
              <button @click="handleToggle(s.id)" class="text-yellow-600 hover:underline">
                {{ s.is_active ? 'Desativar' : 'Ativar' }}
              </button>
              <button @click="handleDelete(s.id)" class="text-red-600 hover:underline">Excluir</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal criar/editar -->
    <div v-if="showCreateModal || editTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl">
        <h3 class="text-lg font-semibold mb-4">{{ editTarget ? 'Editar especialidade' : 'Nova especialidade' }}</h3>
        <form @submit.prevent="handleSave" class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700">Nome</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Descrição <span class="text-gray-400 font-normal">(opcional)</span></label>
            <textarea
              v-model="form.description"
              rows="3"
              class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="closeModal" class="px-4 py-2 text-sm border rounded-md hover:bg-gray-50">Cancelar</button>
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
import { useSpecialtyStore } from '@/stores/specialty'
import type { Specialty } from '@/types/specialty'

const store = useSpecialtyStore()

const showCreateModal = ref(false)
const editTarget = ref<Specialty | null>(null)
const formLoading = ref(false)
const formError = ref('')
const form = ref({ name: '', description: '' })

function openEditModal(specialty: Specialty) {
  editTarget.value = specialty
  form.value = { name: specialty.name, description: specialty.description ?? '' }
}

function closeModal() {
  showCreateModal.value = false
  editTarget.value = null
  form.value = { name: '', description: '' }
  formError.value = ''
}

async function handleSave() {
  formLoading.value = true
  formError.value = ''
  try {
    const payload = { name: form.value.name, description: form.value.description || null }
    if (editTarget.value) {
      await store.updateSpecialty(editTarget.value.id, payload)
    } else {
      await store.createSpecialty(payload)
    }
    closeModal()
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } }
    formError.value = err.response?.data?.message ?? 'Erro ao salvar.'
  } finally {
    formLoading.value = false
  }
}

async function handleToggle(id: string) {
  await store.toggleActive(id)
}

async function handleDelete(id: string) {
  if (!confirm('Confirma a exclusão desta especialidade?')) return
  await store.removeSpecialty(id)
}

onMounted(() => store.fetchSpecialties())
</script>
