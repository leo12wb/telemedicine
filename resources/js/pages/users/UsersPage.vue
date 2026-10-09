<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-semibold text-gray-800">Usuários</h2>
      <button
        @click="showCreateModal = true"
        class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700"
      >
        Novo usuário
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
        v-model="filters.role"
        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        @change="fetchUsers"
      >
        <option value="">Todos os perfis</option>
        <option value="admin">Admin</option>
        <option value="medico">Médico</option>
        <option value="paciente">Paciente</option>
      </select>
      <select
        v-model="filters.is_active"
        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        @change="fetchUsers"
      >
        <option :value="undefined">Todos os status</option>
        <option :value="true">Ativos</option>
        <option :value="false">Inativos</option>
      </select>
    </div>

    <!-- Tabela -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">E-mail</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Perfil</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="userStore.loading">
            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Carregando...</td>
          </tr>
          <tr v-else-if="userStore.users.length === 0">
            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Nenhum usuário encontrado.</td>
          </tr>
          <tr v-for="user in userStore.users" :key="user.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ user.name }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ user.email }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">
              <span
                :class="roleBadgeClass(user.role)"
                class="px-2 py-1 rounded text-xs font-medium"
              >{{ user.role }}</span>
            </td>
            <td class="px-6 py-4 text-sm">
              <span
                :class="user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                class="px-2 py-1 rounded text-xs font-medium"
              >{{ user.is_active ? 'Ativo' : 'Inativo' }}</span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <button
                @click="handleToggleActive(user.id)"
                class="text-blue-600 hover:underline"
              >
                {{ user.is_active ? 'Desativar' : 'Ativar' }}
              </button>
              <button
                @click="handleDelete(user.id)"
                class="text-red-600 hover:underline"
              >
                Excluir
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Paginação -->
      <div v-if="userStore.meta.last_page > 1" class="px-6 py-3 flex items-center justify-between border-t border-gray-200">
        <span class="text-sm text-gray-700">
          Total: {{ userStore.meta.total }} usuários
        </span>
        <div class="flex gap-2">
          <button
            :disabled="userStore.meta.current_page === 1"
            @click="changePage(userStore.meta.current_page - 1)"
            class="px-3 py-1 text-sm border rounded disabled:opacity-50 hover:bg-gray-50"
          >Anterior</button>
          <span class="px-3 py-1 text-sm">
            {{ userStore.meta.current_page }} / {{ userStore.meta.last_page }}
          </span>
          <button
            :disabled="userStore.meta.current_page === userStore.meta.last_page"
            @click="changePage(userStore.meta.current_page + 1)"
            class="px-3 py-1 text-sm border rounded disabled:opacity-50 hover:bg-gray-50"
          >Próxima</button>
        </div>
      </div>
    </div>

    <!-- Modal criar usuário -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl">
        <h3 class="text-lg font-semibold mb-4">Novo usuário</h3>
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
            <label class="block text-sm font-medium text-gray-700">Perfil</label>
            <select v-model="form.role" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="admin">Admin</option>
              <option value="medico">Médico</option>
              <option value="paciente">Paciente</option>
            </select>
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
import { useUserStore } from '@/stores/user'
import type { UserFilters } from '@/services/user'

const userStore = useUserStore()

const filters = ref<UserFilters & { search?: string; role?: string; is_active?: boolean }>({
  search: '',
  role: '',
  is_active: undefined,
  page: 1,
})

const showCreateModal = ref(false)
const formLoading = ref(false)
const formError = ref('')
const form = ref({ name: '', email: '', password: '', role: 'paciente' })

let debounceTimer: ReturnType<typeof setTimeout>

function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchUsers, 400)
}

async function fetchUsers() {
  const params: UserFilters = { page: filters.value.page }
  if (filters.value.search) params.search = filters.value.search
  if (filters.value.role) params.role = filters.value.role
  if (filters.value.is_active !== undefined) params.is_active = filters.value.is_active
  await userStore.fetchUsers(params)
}

async function changePage(page: number) {
  filters.value.page = page
  await fetchUsers()
}

async function handleToggleActive(id: string) {
  await userStore.toggleActive(id)
}

async function handleDelete(id: string) {
  if (!confirm('Confirma a exclusão deste usuário?')) return
  await userStore.removeUser(id)
}

async function handleCreate() {
  formLoading.value = true
  formError.value = ''
  try {
    await userStore.createUser(form.value)
    closeModal()
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } }
    formError.value = err.response?.data?.message ?? 'Erro ao criar usuário.'
  } finally {
    formLoading.value = false
  }
}

function closeModal() {
  showCreateModal.value = false
  form.value = { name: '', email: '', password: '', role: 'paciente' }
  formError.value = ''
}

function roleBadgeClass(role: string) {
  return {
    admin: 'bg-purple-100 text-purple-800',
    medico: 'bg-blue-100 text-blue-800',
    paciente: 'bg-gray-100 text-gray-800',
  }[role] ?? 'bg-gray-100 text-gray-800'
}

onMounted(fetchUsers)
</script>
