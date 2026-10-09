<template>
  <div class="max-w-3xl">
    <h2 class="text-xl font-semibold text-gray-800 mb-6">Minha agenda</h2>

    <div class="grid gap-8">
      <!-- Horários recorrentes -->
      <section class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-base font-semibold text-gray-700">Horários de atendimento</h3>
          <button
            @click="showScheduleForm = true"
            class="text-sm bg-blue-600 text-white px-3 py-1.5 rounded-md hover:bg-blue-700"
          >
            + Adicionar
          </button>
        </div>

        <div v-if="store.loading" class="text-sm text-gray-400">Carregando...</div>

        <div v-else-if="store.schedules.length === 0" class="text-sm text-gray-400">
          Nenhum horário cadastrado.
        </div>

        <ul v-else class="divide-y divide-gray-100">
          <li
            v-for="s in store.schedules"
            :key="s.id"
            class="py-3 flex items-center justify-between"
          >
            <div>
              <span class="font-medium text-sm text-gray-800">{{ DAY_NAMES[s.day_of_week] }}</span>
              <span class="text-sm text-gray-500 ml-2">
                {{ s.start_time.slice(0, 5) }} – {{ s.end_time.slice(0, 5) }}
                ({{ s.slot_duration_minutes }} min)
              </span>
              <span
                v-if="!s.is_active"
                class="ml-2 text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded"
              >
                inativo
              </span>
            </div>
            <div class="flex gap-3">
              <button
                @click="toggleSchedule(s)"
                class="text-xs text-blue-600 hover:underline"
              >
                {{ s.is_active ? 'Desativar' : 'Ativar' }}
              </button>
              <button
                @click="removeSchedule(s.id)"
                class="text-xs text-red-600 hover:underline"
              >
                Remover
              </button>
            </div>
          </li>
        </ul>
      </section>

      <!-- Bloqueios -->
      <section class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-base font-semibold text-gray-700">Bloqueios</h3>
          <button
            @click="showBlockForm = true"
            class="text-sm bg-blue-600 text-white px-3 py-1.5 rounded-md hover:bg-blue-700"
          >
            + Bloquear data
          </button>
        </div>

        <div v-if="store.blocks.length === 0" class="text-sm text-gray-400">
          Nenhum bloqueio cadastrado.
        </div>

        <ul v-else class="divide-y divide-gray-100">
          <li
            v-for="b in store.blocks"
            :key="b.id"
            class="py-3 flex items-center justify-between"
          >
            <div>
              <span class="font-medium text-sm text-gray-800">{{ formatDate(b.block_date) }}</span>
              <span class="text-sm text-gray-500 ml-2">
                <template v-if="b.is_all_day">Dia inteiro</template>
                <template v-else>{{ b.block_start?.slice(0, 5) }} – {{ b.block_end?.slice(0, 5) }}</template>
              </span>
              <span v-if="b.reason" class="ml-2 text-xs text-gray-400">{{ b.reason }}</span>
            </div>
            <button
              @click="removeBlock(b.id)"
              class="text-xs text-red-600 hover:underline"
            >
              Remover
            </button>
          </li>
        </ul>
      </section>
    </div>

    <!-- Modal: novo horário -->
    <div v-if="showScheduleForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-sm shadow-xl">
        <h3 class="text-base font-semibold mb-4">Novo horário</h3>
        <form @submit.prevent="submitSchedule" class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700">Dia da semana</label>
            <select v-model.number="scheduleForm.day_of_week" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option v-for="(name, idx) in DAY_NAMES" :key="idx" :value="idx">{{ name }}</option>
            </select>
          </div>
          <div class="flex gap-3">
            <div class="flex-1">
              <label class="block text-sm font-medium text-gray-700">Início</label>
              <input v-model="scheduleForm.start_time" type="time" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div class="flex-1">
              <label class="block text-sm font-medium text-gray-700">Fim</label>
              <input v-model="scheduleForm.end_time" type="time" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Duração do slot (min)</label>
            <select v-model.number="scheduleForm.slot_duration_minutes" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option :value="15">15 min</option>
              <option :value="20">20 min</option>
              <option :value="30">30 min</option>
              <option :value="45">45 min</option>
              <option :value="60">60 min</option>
            </select>
          </div>
          <p v-if="scheduleError" class="text-sm text-red-600">{{ scheduleError }}</p>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showScheduleForm = false; scheduleError = ''" class="px-4 py-2 text-sm border rounded-md hover:bg-gray-50">Cancelar</button>
            <button type="submit" :disabled="scheduleLoading" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
              {{ scheduleLoading ? 'Salvando...' : 'Salvar' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: novo bloqueio -->
    <div v-if="showBlockForm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-sm shadow-xl">
        <h3 class="text-base font-semibold mb-4">Bloquear data</h3>
        <form @submit.prevent="submitBlock" class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700">Data</label>
            <input v-model="blockForm.block_date" type="date" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div class="flex items-center gap-2">
            <input v-model="blockForm.allDay" type="checkbox" id="allDay" class="rounded" />
            <label for="allDay" class="text-sm text-gray-700">Dia inteiro</label>
          </div>
          <div v-if="!blockForm.allDay" class="flex gap-3">
            <div class="flex-1">
              <label class="block text-sm font-medium text-gray-700">Início</label>
              <input v-model="blockForm.block_start" type="time" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div class="flex-1">
              <label class="block text-sm font-medium text-gray-700">Fim</label>
              <input v-model="blockForm.block_end" type="time" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Motivo <span class="text-gray-400 font-normal">(opcional)</span></label>
            <input v-model="blockForm.reason" type="text" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <p v-if="blockError" class="text-sm text-red-600">{{ blockError }}</p>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="showBlockForm = false; blockError = ''" class="px-4 py-2 text-sm border rounded-md hover:bg-gray-50">Cancelar</button>
            <button type="submit" :disabled="blockLoading" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
              {{ blockLoading ? 'Salvando...' : 'Salvar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAvailabilityStore } from '@/stores/availability'
import type { DoctorSchedule } from '@/types/availability'
import { DAY_NAMES } from '@/types/availability'

const store = useAvailabilityStore()
const route = useRoute()

const doctorId = route.params.doctorId as string

// ─── Schedule form state ──────────────────────────────────────────────────────
const showScheduleForm = ref(false)
const scheduleLoading = ref(false)
const scheduleError = ref('')
const scheduleForm = ref({
  day_of_week: 1,
  start_time: '08:00',
  end_time: '12:00',
  slot_duration_minutes: 30,
})

// ─── Block form state ─────────────────────────────────────────────────────────
const showBlockForm = ref(false)
const blockLoading = ref(false)
const blockError = ref('')
const blockForm = ref({
  block_date: '',
  allDay: true,
  block_start: '',
  block_end: '',
  reason: '',
})

// ─── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(async () => {
  await Promise.all([
    store.fetchSchedules(doctorId),
    store.fetchBlocks(doctorId),
  ])
})

// ─── Handlers ─────────────────────────────────────────────────────────────────
async function submitSchedule() {
  scheduleLoading.value = true
  scheduleError.value = ''
  try {
    await store.createSchedule(doctorId, scheduleForm.value)
    showScheduleForm.value = false
    scheduleForm.value = { day_of_week: 1, start_time: '08:00', end_time: '12:00', slot_duration_minutes: 30 }
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } }
    scheduleError.value = err.response?.data?.message ?? 'Erro ao salvar horário.'
  } finally {
    scheduleLoading.value = false
  }
}

async function toggleSchedule(schedule: DoctorSchedule) {
  await store.updateSchedule(doctorId, schedule.id, { is_active: !schedule.is_active })
}

async function removeSchedule(scheduleId: string) {
  if (!confirm('Remover este horário?')) return
  await store.removeSchedule(doctorId, scheduleId)
}

async function submitBlock() {
  blockLoading.value = true
  blockError.value = ''
  try {
    await store.createBlock(doctorId, {
      block_date: blockForm.value.block_date,
      block_start: blockForm.value.allDay ? null : blockForm.value.block_start || null,
      block_end: blockForm.value.allDay ? null : blockForm.value.block_end || null,
      reason: blockForm.value.reason || null,
    })
    showBlockForm.value = false
    blockForm.value = { block_date: '', allDay: true, block_start: '', block_end: '', reason: '' }
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } }
    blockError.value = err.response?.data?.message ?? 'Erro ao criar bloqueio.'
  } finally {
    blockLoading.value = false
  }
}

async function removeBlock(blockId: string) {
  if (!confirm('Remover este bloqueio?')) return
  await store.removeBlock(doctorId, blockId)
}

function formatDate(dateStr: string): string {
  return new Date(dateStr + 'T00:00:00').toLocaleDateString('pt-BR')
}
</script>
