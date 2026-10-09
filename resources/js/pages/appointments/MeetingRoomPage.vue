<template>
  <div class="flex flex-col h-screen bg-gray-900">
    <!-- Top bar -->
    <div class="flex items-center justify-between px-4 py-2 bg-gray-800 text-white text-sm shrink-0">
      <div class="flex items-center gap-3">
        <RouterLink to="/appointments" class="text-gray-400 hover:text-white">← Voltar</RouterLink>
        <span class="text-gray-300">|</span>
        <span class="text-gray-200">{{ roomLabel }}</span>
      </div>
      <span class="text-gray-400">{{ displayName }}</span>
    </div>

    <!-- Loading / Error -->
    <div v-if="loading" class="flex-1 flex items-center justify-center text-white text-sm">Carregando...</div>
    <div v-else-if="fatalError" class="flex-1 flex flex-col items-center justify-center text-white gap-4">
      <p class="text-red-400">{{ fatalError }}</p>
      <RouterLink to="/appointments" class="px-4 py-2 bg-gray-700 rounded hover:bg-gray-600 text-sm">Voltar</RouterLink>
    </div>

    <!-- Main layout: iframe + sidebar -->
    <div v-else class="flex-1 flex overflow-hidden">
      <!-- Jitsi iframe -->
      <iframe
        :src="iframeSrc"
        allow="camera; microphone; fullscreen; display-capture; autoplay"
        class="flex-1 border-0 h-full"
      />

      <!-- Sidebar -->
      <div class="w-80 bg-white flex flex-col border-l border-gray-200 shrink-0 overflow-hidden">
        <!-- Doctor card -->
        <div class="p-4 border-b border-gray-100 flex items-start gap-3">
          <!-- Photo or initials -->
          <img
            v-if="appointment?.doctor?.photo_url"
            :src="appointment.doctor.photo_url"
            class="w-12 h-12 rounded-full object-cover border border-gray-200 shrink-0"
          />
          <div
            v-else
            class="w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold text-sm shrink-0"
          >
            {{ doctorInitials }}
          </div>
          <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-900 truncate">{{ appointment?.doctor?.name ?? '—' }}</p>
            <p class="text-xs text-gray-500">CRM {{ appointment?.doctor?.crm }}/{{ appointment?.doctor?.crm_uf }}</p>
            <p class="text-xs text-gray-500 mt-0.5">{{ timeRange }}</p>
            <p class="text-xs text-gray-400 mt-0.5">Protocolo: {{ protocol }}</p>
          </div>
        </div>

        <!-- Tabs -->
        <div class="flex border-b border-gray-200 shrink-0">
          <button
            @click="activeTab = 'chat'"
            :class="['flex-1 py-2 text-sm font-medium', activeTab === 'chat' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700']"
          >Chat</button>
          <button
            @click="activeTab = 'attachments'; loadAttachments()"
            :class="['flex-1 py-2 text-sm font-medium', activeTab === 'attachments' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700']"
          >Anexos</button>
        </div>

        <!-- Chat tab -->
        <div v-if="activeTab === 'chat'" class="flex flex-col flex-1 overflow-hidden">
          <div ref="messagesEl" class="flex-1 overflow-y-auto p-3 space-y-2">
            <div v-if="messages.length === 0" class="text-xs text-gray-400 text-center pt-4">Nenhuma mensagem ainda.</div>
            <div
              v-for="m in messages"
              :key="m.id"
              :class="['max-w-[85%] rounded-lg px-3 py-2', m.user_id === authStore.user?.id ? 'ml-auto bg-blue-600 text-white' : 'bg-gray-100 text-gray-800']"
            >
              <p class="text-xs font-medium opacity-70 mb-0.5">{{ m.user_name }}</p>
              <p class="text-sm break-words">{{ m.body }}</p>
              <p class="text-xs opacity-50 mt-0.5 text-right">{{ formatTime(m.created_at) }}</p>
            </div>
          </div>
          <div class="p-3 border-t border-gray-100">
            <div class="flex gap-2">
              <input
                v-model="newMessage"
                @keydown.enter.prevent="sendMessage"
                type="text"
                placeholder="Digite uma mensagem..."
                class="flex-1 text-sm border border-gray-300 rounded-md px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <button
                @click="sendMessage"
                :disabled="!newMessage.trim()"
                class="px-3 py-1.5 bg-blue-600 text-white rounded-md text-sm disabled:opacity-40 hover:bg-blue-700"
              >
                Enviar
              </button>
            </div>
          </div>
        </div>

        <!-- Attachments tab -->
        <div v-if="activeTab === 'attachments'" class="flex flex-col flex-1 overflow-hidden">
          <div class="flex-1 overflow-y-auto p-3 space-y-2">
            <div v-if="attachments.length === 0" class="text-xs text-gray-400 text-center pt-4">Nenhum arquivo enviado.</div>
            <div
              v-for="a in attachments"
              :key="a.id"
              class="flex items-center gap-2 p-2 rounded-lg bg-gray-50 border border-gray-200"
            >
              <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-gray-800 truncate">{{ a.original_name }}</p>
                <p class="text-xs text-gray-400">{{ a.user_name }} · {{ formatSize(a.size) }}</p>
              </div>
              <button @click="downloadFile(a)" class="text-xs text-blue-600 hover:underline shrink-0">Baixar</button>
            </div>
          </div>
          <div class="p-3 border-t border-gray-100">
            <label class="block">
              <span class="sr-only">Enviar arquivo</span>
              <input
                type="file"
                accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                @change="uploadFile"
                class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:border file:rounded file:text-xs file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100"
              />
            </label>
            <p v-if="attachError" class="mt-1 text-xs text-red-500">{{ attachError }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { appointmentService } from '@/services/appointment'
import { appointmentChatService, type Message, type Attachment } from '@/services/appointmentChat'
import type { Appointment } from '@/types/appointment'

const route = useRoute()
const authStore = useAuthStore()

const loading = ref(true)
const fatalError = ref('')
const iframeSrc = ref('')
const roomLabel = ref('Consulta')
const appointment = ref<Appointment | null>(null)
const activeTab = ref<'chat' | 'attachments'>('chat')

const messages = ref<Message[]>([])
const newMessage = ref('')
const messagesEl = ref<HTMLElement | null>(null)

const attachments = ref<Attachment[]>([])
const attachError = ref('')

let pollTimer: ReturnType<typeof setInterval> | null = null

const user = authStore.user
const displayName = user
  ? user.role === 'medico' ? `Dr(a). ${user.name}` : user.name
  : 'Participante'

const doctorInitials = computed(() => {
  const name = appointment.value?.doctor?.name ?? ''
  const parts = name.split(' ').filter(Boolean)
  if (parts.length === 0) return '?'
  if (parts.length === 1) return parts[0][0].toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})

const protocol = computed(() => {
  const id = appointment.value?.id ?? ''
  return id.replace(/-/g, '').slice(-8).toUpperCase()
})

const timeRange = computed(() => {
  if (!appointment.value) return ''
  const [h, m] = appointment.value.scheduled_time.split(':').map(Number)
  const start = new Date(0, 0, 0, h, m)
  const end = new Date(0, 0, 0, h, m + (appointment.value.duration_minutes ?? 30))
  const fmt = (d: Date) => `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
  return `${fmt(start)} – ${fmt(end)}`
})

const appointmentId = route.params.appointmentId as string

onMounted(async () => {
  try {
    const [appt, meeting] = await Promise.all([
      appointmentService.get(appointmentId),
      appointmentService.getMeeting(appointmentId),
    ])
    appointment.value = appt
    const parsed = new URL(meeting.url)
    roomLabel.value = parsed.pathname.slice(1)
    iframeSrc.value = `${meeting.url}#userInfo.displayName=${encodeURIComponent(displayName)}&config.prejoinPageEnabled=false`

    await loadMessages()
    pollTimer = setInterval(loadMessages, 5000)
  } catch (e: unknown) {
    const err = e as { response?: { status?: number; data?: { message?: string } } }
    if (err.response?.status === 404) {
      fatalError.value = 'Videoconferência não disponível para esta consulta.'
    } else if (err.response?.status === 503) {
      fatalError.value = 'Serviço de videoconferência temporariamente indisponível.'
    } else {
      fatalError.value = 'Não foi possível carregar a sala.'
    }
  } finally {
    loading.value = false
  }
})

onBeforeUnmount(() => {
  if (pollTimer) clearInterval(pollTimer)
})

async function loadMessages() {
  try {
    const prev = messages.value.length
    messages.value = await appointmentChatService.getMessages(appointmentId)
    if (messages.value.length !== prev) scrollToBottom()
  } catch { /* silent */ }
}

async function sendMessage() {
  const body = newMessage.value.trim()
  if (!body) return
  newMessage.value = ''
  const msg = await appointmentChatService.sendMessage(appointmentId, body)
  messages.value.push(msg)
  scrollToBottom()
}

async function loadAttachments() {
  attachments.value = await appointmentChatService.getAttachments(appointmentId)
}

async function uploadFile(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return
  attachError.value = ''
  try {
    const att = await appointmentChatService.uploadAttachment(appointmentId, file)
    attachments.value.push(att)
  } catch {
    attachError.value = 'Erro ao enviar arquivo. Verifique o tamanho (máx. 10 MB).'
  } finally {
    input.value = ''
  }
}

async function downloadFile(a: { id: string; original_name: string }) {
  const token = localStorage.getItem('auth_token')
  const url = appointmentChatService.downloadUrl(appointmentId, a.id)
  const response = await fetch(url, {
    headers: { Authorization: `Bearer ${token}` },
  })
  if (!response.ok) return
  const blob = await response.blob()
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = a.original_name
  link.click()
  URL.revokeObjectURL(link.href)
}

function scrollToBottom() {
  nextTick(() => {
    if (messagesEl.value) messagesEl.value.scrollTop = messagesEl.value.scrollHeight
  })
}

function formatTime(iso: string): string {
  return new Date(iso).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
}

function formatSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}
</script>
