<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-semibold text-gray-800">Médicos</h2>
      <button
        @click="showCreateModal = true"
        class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700"
      >
        Novo médico
      </button>
    </div>

    <!-- Filtros -->
    <div class="flex gap-3 mb-4">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Buscar por nome ou e-mail..."
        class="border border-gray-300 rounded-md px-3 py-2 text-sm w-64 focus:outline-none focus:ring-2 focus:ring-blue-500"
        @input="debouncedFetch"
      />
      <select
        v-model="filters.specialty_id"
        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        @change="fetchDoctors"
      >
        <option value="">Todas as especialidades</option>
        <option v-for="s in specialtyStore.activeSpecialties" :key="s.id" :value="s.id">
          {{ s.name }}
        </option>
      </select>
    </div>

    <!-- Tabela -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CRM</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Especialidades</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="doctorStore.loading">
            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Carregando...</td>
          </tr>
          <tr v-else-if="doctorStore.doctors.length === 0">
            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Nenhum médico encontrado.</td>
          </tr>
          <tr v-for="d in doctorStore.doctors" :key="d.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ d.user.name }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ d.crm }}/{{ d.crm_uf }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">
              <span
                v-for="s in d.specialties"
                :key="s.id"
                class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded mr-1"
              >{{ s.name }}</span>
              <span v-if="d.specialties.length === 0" class="text-gray-400">—</span>
            </td>
            <td class="px-6 py-4 text-sm">
              <span
                :class="d.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                class="px-2 py-1 rounded text-xs font-medium"
              >{{ d.is_active ? 'Ativo' : 'Inativo' }}</span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button @click="openEdit(d)" class="text-blue-600 hover:underline">Editar</button>
              <button @click="handleToggle(d.id)" class="text-yellow-600 hover:underline">
                {{ d.is_active ? 'Desativar' : 'Ativar' }}
              </button>
              <button @click="handleDelete(d.id)" class="text-red-600 hover:underline">Excluir</button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="doctorStore.meta.last_page > 1" class="px-6 py-3 flex items-center justify-between border-t border-gray-200">
        <span class="text-sm text-gray-700">Total: {{ doctorStore.meta.total }} médicos</span>
        <div class="flex gap-2">
          <button :disabled="doctorStore.meta.current_page === 1" @click="changePage(doctorStore.meta.current_page - 1)" class="px-3 py-1 text-sm border rounded disabled:opacity-50 hover:bg-gray-50">Anterior</button>
          <span class="px-3 py-1 text-sm">{{ doctorStore.meta.current_page }} / {{ doctorStore.meta.last_page }}</span>
          <button :disabled="doctorStore.meta.current_page === doctorStore.meta.last_page" @click="changePage(doctorStore.meta.current_page + 1)" class="px-3 py-1 text-sm border rounded disabled:opacity-50 hover:bg-gray-50">Próxima</button>
        </div>
      </div>
    </div>

    <!-- Modal criar/editar -->
    <div v-if="showCreateModal || editTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-lg shadow-xl max-h-screen overflow-y-auto">
        <h3 class="text-lg font-semibold mb-4">{{ editTarget ? 'Editar médico' : 'Novo médico' }}</h3>
        <form @submit.prevent="handleSave" class="space-y-3">
          <template v-if="!editTarget">
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
          </template>
          <div class="flex gap-3">
            <div class="flex-1">
              <label class="block text-sm font-medium text-gray-700">CRM</label>
              <input v-model="form.crm" type="text" :required="!editTarget" :disabled="!!editTarget" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-50" />
            </div>
            <div class="w-24">
              <label class="block text-sm font-medium text-gray-700">UF</label>
              <input v-model="form.crm_uf" type="text" maxlength="2" :required="!editTarget" :disabled="!!editTarget" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-50" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Telefone <span class="text-gray-400 font-normal">(opcional)</span></label>
            <input v-model="form.phone" type="text" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Bio <span class="text-gray-400 font-normal">(opcional)</span></label>
            <textarea v-model="form.bio" rows="2" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Especialidades</label>
            <div class="mt-1 border border-gray-300 rounded-md p-2 max-h-36 overflow-y-auto space-y-1">
              <label v-for="s in specialtyStore.activeSpecialties" :key="s.id" class="flex items-center gap-2 text-sm cursor-pointer">
                <input type="checkbox" :value="s.id" v-model="form.specialty_ids" class="rounded" />
                {{ s.name }}
              </label>
              <p v-if="specialtyStore.activeSpecialties.length === 0" class="text-sm text-gray-400">Nenhuma especialidade ativa.</p>
            </div>
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
import { useDoctorStore } from '@/stores/doctor'
import { useSpecialtyStore } from '@/stores/specialty'
import type { Doctor } from '@/types/doctor'

const doctorStore = useDoctorStore()
const specialtyStore = useSpecialtyStore()

const filters = ref({ search: '', specialty_id: '', page: 1 })
const showCreateModal = ref(false)
const editTarget = ref<Doctor | null>(null)
const formLoading = ref(false)
const formError = ref('')
const form = ref({
  name: '', email: '', password: '',
  crm: '', crm_uf: '', phone: '', bio: '',
  specialty_ids: [] as string[],
})

let debounceTimer: ReturnType<typeof setTimeout>

function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchDoctors, 400)
}

async function fetchDoctors() {
  const params: Record<string, unknown> = { page: filters.value.page }
  if (filters.value.search) params.search = filters.value.search
  if (filters.value.specialty_id) params.specialty_id = filters.value.specialty_id
  await doctorStore.fetchDoctors(params)
}

async function changePage(page: number) {
  filters.value.page = page
  await fetchDoctors()
}

function openEdit(d: Doctor) {
  editTarget.value = d
  form.value = {
    name: '', email: '', password: '',
    crm: d.crm, crm_uf: d.crm_uf,
    phone: d.phone ?? '',
    bio: d.bio ?? '',
    specialty_ids: d.specialties.map((s) => s.id),
  }
}

function closeModal() {
  showCreateModal.value = false
  editTarget.value = null
  form.value = { name: '', email: '', password: '', crm: '', crm_uf: '', phone: '', bio: '', specialty_ids: [] }
  formError.value = ''
}

async function handleSave() {
  formLoading.value = true
  formError.value = ''
  try {
    if (editTarget.value) {
      await doctorStore.updateDoctor(editTarget.value.id, {
        phone: form.value.phone || null,
        bio: form.value.bio || null,
        specialty_ids: form.value.specialty_ids,
      })
    } else {
      await doctorStore.createDoctor({
        name: form.value.name,
        email: form.value.email,
        password: form.value.password,
        crm: form.value.crm,
        crm_uf: form.value.crm_uf,
        phone: form.value.phone || undefined,
        bio: form.value.bio || undefined,
        specialty_ids: form.value.specialty_ids,
      })
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
  await doctorStore.toggleActive(id)
}

async function handleDelete(id: string) {
  if (!confirm('Confirma a exclusão deste médico?')) return
  await doctorStore.removeDoctor(id)
}

onMounted(async () => {
  await Promise.all([fetchDoctors(), specialtyStore.fetchActive()])
})
</script>
