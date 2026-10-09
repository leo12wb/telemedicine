<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-semibold text-gray-800">Consultas</h2>
      <button
        v-if="authStore.user?.role === 'paciente'"
        @click="showBookModal = true"
        class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700"
      >
        Agendar consulta
      </button>
    </div>

    <!-- Filtros -->
    <div class="flex flex-wrap gap-3 mb-4">
      <select
        v-model="filters.status"
        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        @change="fetchAppointments"
      >
        <option value="">Todos os status</option>
        <option value="agendada">Agendada</option>
        <option value="em_andamento">Em andamento</option>
        <option value="concluida">Concluída</option>
        <option value="cancelada">Cancelada</option>
        <option value="paciente_ausente">Paciente ausente</option>
      </select>
      <input
        v-model="filters.from"
        type="date"
        placeholder="De"
        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        @change="fetchAppointments"
      />
      <input
        v-model="filters.to"
        type="date"
        placeholder="Até"
        class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        @change="fetchAppointments"
      />
    </div>

    <!-- Tabela -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data / Hora</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Médico</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paciente</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="store.loading">
            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Carregando...</td>
          </tr>
          <tr v-else-if="store.appointments.length === 0">
            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Nenhuma consulta encontrada.</td>
          </tr>
          <tr v-for="a in store.appointments" :key="a.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm text-gray-800">
              <div class="font-medium">{{ formatDate(a.scheduled_date) }}</div>
              <div class="text-gray-500">{{ a.scheduled_time.slice(0, 5) }}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ a.doctor?.name ?? '—' }}</td>
            <td class="px-6 py-4 text-sm text-gray-600">{{ a.patient?.name ?? '—' }}</td>
            <td class="px-6 py-4 text-sm">
              <span :class="['px-2 py-1 rounded text-xs font-medium', STATUS_COLORS[a.status]]">
                {{ a.status_label }}
              </span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-2">
              <!-- Histórico: chat + anexos -->
              <button @click="openHistory(a)" class="text-gray-500 hover:underline">Histórico</button>
              <!-- Entrar na sala de videoconferência -->
              <button
                v-if="a.status === 'agendada' || a.status === 'em_andamento'"
                @click="handleJoinMeeting(a.id)"
                class="text-indigo-600 hover:underline"
              >Entrar na consulta</button>
              <!-- Médico: iniciar / encerrar -->
              <button
                v-if="authStore.user?.role === 'medico' && a.status === 'agendada'"
                @click="handleStart(a.id)"
                class="text-yellow-600 hover:underline"
              >Iniciar</button>
              <button
                v-if="authStore.user?.role === 'medico' && a.status === 'em_andamento'"
                @click="openFinish(a)"
                class="text-green-600 hover:underline"
              >Encerrar</button>
              <button
                v-if="authStore.user?.role === 'medico' && a.status === 'em_andamento'"
                @click="openNotes(a)"
                class="text-blue-600 hover:underline"
              >Anotações</button>
              <!-- Cancelar -->
              <button
                v-if="a.status === 'agendada'"
                @click="openCancel(a)"
                class="text-red-600 hover:underline"
              >Cancelar</button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="store.meta.last_page > 1" class="px-6 py-3 flex items-center justify-between border-t border-gray-200">
        <span class="text-sm text-gray-700">Total: {{ store.meta.total }}</span>
        <div class="flex gap-2">
          <button :disabled="store.meta.current_page === 1" @click="changePage(store.meta.current_page - 1)" class="px-3 py-1 text-sm border rounded disabled:opacity-50 hover:bg-gray-50">Anterior</button>
          <span class="px-3 py-1 text-sm">{{ store.meta.current_page }} / {{ store.meta.last_page }}</span>
          <button :disabled="store.meta.current_page === store.meta.last_page" @click="changePage(store.meta.current_page + 1)" class="px-3 py-1 text-sm border rounded disabled:opacity-50 hover:bg-gray-50">Próxima</button>
        </div>
      </div>
    </div>

    <!-- Modal: agendar (paciente) -->
    <div v-if="showBookModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl">
        <h3 class="text-base font-semibold mb-4">Agendar consulta</h3>
        <form @submit.prevent="handleBook" class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700">Médico</label>
            <select v-model="bookForm.doctor_id" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" @change="loadSlots">
              <option value="">Selecione</option>
              <option v-for="d in doctorStore.doctors" :key="d.id" :value="d.id">{{ d.user.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Data</label>
            <input v-model="bookForm.date" type="date" :min="today" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" @change="loadSlots" />
          </div>
          <div v-if="bookSlots.length > 0">
            <label class="block text-sm font-medium text-gray-700 mb-1">Horário</label>
            <div class="grid grid-cols-4 gap-1">
              <button
                v-for="slot in bookSlots"
                :key="slot.time"
                type="button"
                @click="bookForm.scheduled_time = slot.time"
                :class="['py-1.5 text-sm border rounded text-center', bookForm.scheduled_time === slot.time ? 'bg-blue-600 text-white border-blue-600' : 'hover:bg-gray-50']"
              >{{ slot.time }}</button>
            </div>
          </div>
          <div v-else-if="bookForm.doctor_id && bookForm.date && !slotsLoading" class="text-sm text-gray-400">
            Nenhum horário disponível para esta data.
          </div>
          <p v-if="bookError" class="text-sm text-red-600">{{ bookError }}</p>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showBookModal = false; bookError = ''" class="px-4 py-2 text-sm border rounded-md hover:bg-gray-50">Cancelar</button>
            <button type="submit" :disabled="bookLoading || !bookForm.scheduled_time" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
              {{ bookLoading ? 'Agendando...' : 'Confirmar' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: cancelar -->
    <div v-if="cancelTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-sm shadow-xl">
        <h3 class="text-base font-semibold mb-4">Cancelar consulta</h3>
        <div>
          <label class="block text-sm font-medium text-gray-700">Motivo <span class="text-gray-400 font-normal">(opcional)</span></label>
          <input v-model="cancelReason" type="text" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <p v-if="cancelError" class="mt-2 text-sm text-red-600">{{ cancelError }}</p>
        <div class="flex justify-end gap-3 mt-4">
          <button @click="cancelTarget = null; cancelError = ''" class="px-4 py-2 text-sm border rounded-md hover:bg-gray-50">Voltar</button>
          <button @click="handleCancel" :disabled="cancelLoading" class="px-4 py-2 text-sm bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50">
            {{ cancelLoading ? 'Cancelando...' : 'Confirmar cancelamento' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: encerrar consulta -->
    <div v-if="finishTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-sm shadow-xl">
        <h3 class="text-base font-semibold mb-4">Encerrar consulta</h3>
        <div class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700">Desfecho</label>
            <select v-model="finishOutcome" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="concluida">Concluída</option>
              <option value="paciente_ausente">Paciente ausente</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Anotações finais <span class="text-gray-400 font-normal">(opcional)</span></label>
            <textarea v-model="finishNotes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
        </div>
        <div class="flex justify-end gap-3 mt-4">
          <button @click="finishTarget = null" class="px-4 py-2 text-sm border rounded-md hover:bg-gray-50">Voltar</button>
          <button @click="handleFinish" :disabled="finishLoading" class="px-4 py-2 text-sm bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50">
            {{ finishLoading ? 'Encerrando...' : 'Encerrar' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: anotações -->
    <div v-if="notesTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-sm shadow-xl">
        <h3 class="text-base font-semibold mb-4">Anotações clínicas</h3>
        <textarea v-model="notesText" rows="5" class="block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        <div class="flex justify-end gap-3 mt-4">
          <button @click="notesTarget = null" class="px-4 py-2 text-sm border rounded-md hover:bg-gray-50">Cancelar</button>
          <button @click="handleNotes" :disabled="notesLoading" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
            {{ notesLoading ? 'Salvando...' : 'Salvar' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: histórico (chat + anexos) -->
    <div v-if="historyTarget" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-lg flex flex-col" style="height: 560px;">
        <!-- Cabeçalho -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
          <div>
            <p class="text-sm font-semibold text-gray-900">
              {{ historyTarget.doctor?.name ?? '—' }} · {{ formatDate(historyTarget.scheduled_date) }} {{ historyTarget.scheduled_time.slice(0,5) }}
            </p>
            <p class="text-xs text-gray-400">{{ historyTarget.status_label }}</p>
          </div>
          <button @click="historyTarget = null" class="text-gray-400 hover:text-gray-600 text-lg leading-none">✕</button>
        </div>

        <!-- Abas -->
        <div class="flex border-b border-gray-200 shrink-0">
          <button
            @click="historyTab = 'chat'"
            :class="['flex-1 py-2 text-sm font-medium', historyTab === 'chat' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700']"
          >Chat</button>
          <button
            @click="historyTab = 'attachments'; loadHistoryAttachments()"
            :class="['flex-1 py-2 text-sm font-medium', historyTab === 'attachments' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700']"
          >Anexos</button>
        </div>

        <!-- Chat -->
        <div v-if="historyTab === 'chat'" class="flex flex-col flex-1 overflow-hidden">
          <div class="flex-1 overflow-y-auto p-4 space-y-2">
            <div v-if="historyMessages.length === 0" class="text-xs text-gray-400 text-center pt-6">Nenhuma mensagem nesta consulta.</div>
            <div
              v-for="m in historyMessages"
              :key="m.id"
              :class="['max-w-[80%] rounded-lg px-3 py-2', m.user_id === authStore.user?.id ? 'ml-auto bg-blue-600 text-white' : 'bg-gray-100 text-gray-800']"
            >
              <p class="text-xs font-medium opacity-70 mb-0.5">{{ m.user_name }}</p>
              <p class="text-sm break-words">{{ m.body }}</p>
              <p class="text-xs opacity-50 mt-0.5 text-right">{{ formatTime(m.created_at) }}</p>
            </div>
          </div>
          <div class="p-3 border-t border-gray-100 shrink-0">
            <div class="flex gap-2">
              <input
                v-model="historyNewMessage"
                @keydown.enter.prevent="sendHistoryMessage"
                type="text"
                placeholder="Digite uma mensagem..."
                class="flex-1 text-sm border border-gray-300 rounded-md px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <button @click="sendHistoryMessage" :disabled="!historyNewMessage.trim()" class="px-3 py-1.5 bg-blue-600 text-white rounded-md text-sm disabled:opacity-40 hover:bg-blue-700">
                Enviar
              </button>
            </div>
          </div>
        </div>

        <!-- Anexos -->
        <div v-if="historyTab === 'attachments'" class="flex flex-col flex-1 overflow-hidden">
          <div class="flex-1 overflow-y-auto p-4 space-y-2">
            <div v-if="historyAttachments.length === 0" class="text-xs text-gray-400 text-center pt-6">Nenhum arquivo nesta consulta.</div>
            <div
              v-for="a in historyAttachments"
              :key="a.id"
              class="flex items-center gap-2 p-2 rounded-lg bg-gray-50 border border-gray-200"
            >
              <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-gray-800 truncate">{{ a.original_name }}</p>
                <p class="text-xs text-gray-400">{{ a.user_name }} · {{ formatSize(a.size) }}</p>
              </div>
              <button @click="downloadHistoryFile(a)" class="text-xs text-blue-600 hover:underline shrink-0">Baixar</button>
            </div>
          </div>
          <div class="p-3 border-t border-gray-100 shrink-0">
            <input type="file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" @change="uploadHistoryFile" class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:border file:rounded file:text-xs file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100" />
            <p v-if="historyAttachError" class="mt-1 text-xs text-red-500">{{ historyAttachError }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAppointmentStore } from '@/stores/appointment'
import { useAvailabilityStore } from '@/stores/availability'
import { useDoctorStore } from '@/stores/doctor'
import { useAuthStore } from '@/stores/auth'
import type { Appointment } from '@/types/appointment'
import { STATUS_COLORS } from '@/types/appointment'
import { appointmentService } from '@/services/appointment'
import { appointmentChatService, type Message, type Attachment } from '@/services/appointmentChat'

const router = useRouter()
const store = useAppointmentStore()
const availStore = useAvailabilityStore()
const doctorStore = useDoctorStore()
const authStore = useAuthStore()

const filters = ref({ status: '' as string, from: '', to: '', page: 1 })
const today = new Date().toISOString().split('T')[0]

// ─── Book modal ───────────────────────────────────────────────────────────────
const showBookModal = ref(false)
const bookLoading = ref(false)
const slotsLoading = ref(false)
const bookError = ref('')
const bookSlots = ref<{ time: string; schedule_id: string }[]>([])
const bookForm = ref({ doctor_id: '', date: '', scheduled_time: '' })

// ─── Cancel modal ─────────────────────────────────────────────────────────────
const cancelTarget = ref<Appointment | null>(null)
const cancelReason = ref('')
const cancelLoading = ref(false)
const cancelError = ref('')

// ─── Finish modal ─────────────────────────────────────────────────────────────
const finishTarget = ref<Appointment | null>(null)
const finishOutcome = ref<'concluida' | 'paciente_ausente'>('concluida')
const finishNotes = ref('')
const finishLoading = ref(false)

// ─── Notes modal ──────────────────────────────────────────────────────────────
const notesTarget = ref<Appointment | null>(null)
const notesText = ref('')
const notesLoading = ref(false)

// ─── History modal ────────────────────────────────────────────────────────────
const historyTarget = ref<Appointment | null>(null)
const historyTab = ref<'chat' | 'attachments'>('chat')
const historyMessages = ref<Message[]>([])
const historyNewMessage = ref('')
const historyAttachments = ref<Attachment[]>([])
const historyAttachError = ref('')

onMounted(async () => {
  await Promise.all([
    fetchAppointments(),
    doctorStore.doctors.length === 0 ? doctorStore.fetchDoctors() : Promise.resolve(),
  ])
})

async function fetchAppointments() {
  const params: Record<string, unknown> = { page: filters.value.page }
  if (filters.value.status) params.status = filters.value.status
  if (filters.value.from) params.from = filters.value.from
  if (filters.value.to) params.to = filters.value.to
  await store.fetchAppointments(params)
}

async function changePage(page: number) {
  filters.value.page = page
  await fetchAppointments()
}

async function loadSlots() {
  bookSlots.value = []
  bookForm.value.scheduled_time = ''
  if (!bookForm.value.doctor_id || !bookForm.value.date) return
  slotsLoading.value = true
  try {
    await availStore.fetchSlots(bookForm.value.doctor_id, bookForm.value.date)
    bookSlots.value = availStore.slots
  } finally {
    slotsLoading.value = false
  }
}

async function handleBook() {
  bookLoading.value = true
  bookError.value = ''
  try {
    await store.createAppointment({
      doctor_id: bookForm.value.doctor_id,
      scheduled_date: bookForm.value.date,
      scheduled_time: bookForm.value.scheduled_time,
    })
    showBookModal.value = false
    bookForm.value = { doctor_id: '', date: '', scheduled_time: '' }
    bookSlots.value = []
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
    const errors = err.response?.data?.errors
    bookError.value = errors
      ? Object.values(errors).flat().join(' ')
      : (err.response?.data?.message ?? 'Erro ao agendar.')
  } finally {
    bookLoading.value = false
  }
}

function openCancel(appt: Appointment) {
  cancelTarget.value = appt
  cancelReason.value = ''
  cancelError.value = ''
}

async function handleCancel() {
  if (!cancelTarget.value) return
  cancelLoading.value = true
  cancelError.value = ''
  try {
    await store.cancelAppointment(cancelTarget.value.id, cancelReason.value || undefined)
    cancelTarget.value = null
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
    const errors = err.response?.data?.errors
    cancelError.value = errors
      ? Object.values(errors).flat().join(' ')
      : (err.response?.data?.message ?? 'Erro ao cancelar.')
  } finally {
    cancelLoading.value = false
  }
}

function goToMeeting(id: string) {
  router.push({ name: 'meeting-room', params: { appointmentId: id } })
}

async function handleJoinMeeting(id: string) {
  goToMeeting(id)
}

async function handleStart(id: string) {
  await store.startAppointment(id)
  goToMeeting(id)
}

function openFinish(appt: Appointment) {
  finishTarget.value = appt
  finishOutcome.value = 'concluida'
  finishNotes.value = appt.notes ?? ''
}

async function handleFinish() {
  if (!finishTarget.value) return
  finishLoading.value = true
  try {
    await store.finishAppointment(finishTarget.value.id, finishOutcome.value, finishNotes.value || undefined)
    finishTarget.value = null
  } finally {
    finishLoading.value = false
  }
}

function openNotes(appt: Appointment) {
  notesTarget.value = appt
  notesText.value = appt.notes ?? ''
}

async function handleNotes() {
  if (!notesTarget.value) return
  notesLoading.value = true
  try {
    await store.updateNotes(notesTarget.value.id, notesText.value)
    notesTarget.value = null
  } finally {
    notesLoading.value = false
  }
}

async function openHistory(appt: Appointment) {
  historyTarget.value = appt
  historyTab.value = 'chat'
  historyMessages.value = []
  historyAttachments.value = []
  historyNewMessage.value = ''
  historyAttachError.value = ''
  historyMessages.value = await appointmentChatService.getMessages(appt.id)
}

async function sendHistoryMessage() {
  const body = historyNewMessage.value.trim()
  if (!body || !historyTarget.value) return
  historyNewMessage.value = ''
  const msg = await appointmentChatService.sendMessage(historyTarget.value.id, body)
  historyMessages.value.push(msg)
}

async function loadHistoryAttachments() {
  if (!historyTarget.value) return
  historyAttachments.value = await appointmentChatService.getAttachments(historyTarget.value.id)
}

async function uploadHistoryFile(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file || !historyTarget.value) return
  historyAttachError.value = ''
  try {
    const att = await appointmentChatService.uploadAttachment(historyTarget.value.id, file)
    historyAttachments.value.push(att)
  } catch {
    historyAttachError.value = 'Erro ao enviar arquivo. Verifique o tamanho (máx. 10 MB).'
  } finally {
    input.value = ''
  }
}

async function downloadHistoryFile(a: Attachment) {
  if (!historyTarget.value) return
  const token = localStorage.getItem('auth_token')
  const url = appointmentChatService.downloadUrl(historyTarget.value.id, a.id)
  const response = await fetch(url, { headers: { Authorization: `Bearer ${token}` } })
  if (!response.ok) return
  const blob = await response.blob()
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = a.original_name
  link.click()
  URL.revokeObjectURL(link.href)
}

function formatTime(iso: string): string {
  return new Date(iso).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
}

function formatSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function formatDate(dateStr: string): string {
  return new Date(dateStr + 'T00:00:00').toLocaleDateString('pt-BR')
}
</script>
