<template>
  <div class="max-w-xl">
    <h2 class="text-xl font-semibold text-gray-800 mb-6">Configurações do sistema</h2>

    <div v-if="store.loading" class="text-sm text-gray-500">Carregando...</div>

    <form v-else @submit.prevent="handleSave" class="bg-white rounded-lg shadow p-6 space-y-5">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Provedor de videoconferência</label>
        <select
          v-model="form.video_provider"
          class="block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="none">Desativado</option>
          <option value="jitsi">Jitsi</option>
          <option value="daily">Daily.co</option>
        </select>
      </div>

      <!-- Jitsi -->
      <template v-if="form.video_provider === 'jitsi'">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">URL do servidor Jitsi</label>
          <input
            v-model="form.jitsi_server_url"
            type="url"
            placeholder="https://meet.jit.si"
            class="block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <p class="text-xs text-gray-400 mt-1">Use https://meet.jit.si para o servidor público ou a URL do seu servidor auto-hospedado.</p>
        </div>
      </template>

      <!-- Daily.co -->
      <template v-if="form.video_provider === 'daily'">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Chave de API (Daily.co)</label>
          <input
            v-model="form.daily_api_key"
            type="password"
            placeholder="sk_live_..."
            class="block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Domínio (Daily.co)</label>
          <input
            v-model="form.daily_domain"
            type="text"
            placeholder="minha-clinica"
            class="block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <p class="text-xs text-gray-400 mt-1">Apenas o subdomínio (sem .daily.co).</p>
        </div>
      </template>

      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      <p v-if="success" class="text-sm text-green-600">Configurações salvas com sucesso.</p>

      <div class="flex justify-end">
        <button
          type="submit"
          :disabled="saving"
          class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
        >{{ saving ? 'Salvando...' : 'Salvar' }}</button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useSettingStore } from '@/stores/setting'
import type { VideoProvider } from '@/types/setting'

const store = useSettingStore()

const saving = ref(false)
const error = ref('')
const success = ref(false)

const form = ref<{
  video_provider: VideoProvider
  jitsi_server_url: string
  daily_api_key: string
  daily_domain: string
}>({
  video_provider: 'none',
  jitsi_server_url: '',
  daily_api_key: '',
  daily_domain: '',
})

onMounted(async () => {
  await store.fetchSettings()
  if (store.settings) {
    form.value.video_provider = store.settings.video_provider
    form.value.jitsi_server_url = store.settings.jitsi_server_url ?? ''
    form.value.daily_api_key = store.settings.daily_api_key ?? ''
    form.value.daily_domain = store.settings.daily_domain ?? ''
  }
})

async function handleSave() {
  saving.value = true
  error.value = ''
  success.value = false
  try {
    const payload: Record<string, string> = { video_provider: form.value.video_provider }
    if (form.value.video_provider === 'jitsi') {
      payload.jitsi_server_url = form.value.jitsi_server_url
    } else if (form.value.video_provider === 'daily') {
      payload.daily_api_key = form.value.daily_api_key
      payload.daily_domain = form.value.daily_domain
    }
    await store.updateSettings(payload)
    success.value = true
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
    const errors = err.response?.data?.errors
    error.value = errors
      ? Object.values(errors).flat().join(' ')
      : (err.response?.data?.message ?? 'Erro ao salvar configurações.')
  } finally {
    saving.value = false
  }
}
</script>
