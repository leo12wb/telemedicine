<template>
  <div class="max-w-2xl">
    <h2 class="text-xl font-semibold text-gray-800 mb-6">Buscar disponibilidade</h2>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Médico</label>
          <select
            v-model="selectedDoctorId"
            class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Selecione um médico</option>
            <option v-for="d in doctorStore.doctors" :key="d.id" :value="d.id">
              {{ d.user.name }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Data</label>
          <input
            v-model="selectedDate"
            type="date"
            :min="today"
            class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div class="flex items-end">
          <button
            @click="search"
            :disabled="!selectedDoctorId || !selectedDate || store.loading"
            class="w-full bg-blue-600 text-white py-2 px-4 rounded-md text-sm hover:bg-blue-700 disabled:opacity-50"
          >
            {{ store.loading ? 'Buscando...' : 'Buscar' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Resultados -->
    <div v-if="searched">
      <div v-if="store.slots.length === 0" class="text-sm text-gray-500 text-center py-8">
        Nenhum horário disponível para a data selecionada.
      </div>

      <div v-else>
        <p class="text-sm text-gray-600 mb-3">
          {{ store.slots.length }} horário{{ store.slots.length !== 1 ? 's' : '' }} disponível{{ store.slots.length !== 1 ? 'is' : '' }}
        </p>
        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
          <button
            v-for="slot in store.slots"
            :key="slot.time"
            @click="selectSlot(slot.time)"
            :class="[
              'py-2 px-3 rounded-md text-sm border text-center transition-colors',
              selectedSlot === slot.time
                ? 'bg-blue-600 text-white border-blue-600'
                : 'bg-white text-gray-800 border-gray-300 hover:border-blue-400 hover:bg-blue-50',
            ]"
          >
            {{ slot.time }}
          </button>
        </div>

        <div v-if="selectedSlot" class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <p class="text-sm text-blue-800">
            Horário selecionado: <strong>{{ selectedSlot }}</strong> em <strong>{{ formatDate(selectedDate) }}</strong>
          </p>
          <p class="text-xs text-blue-600 mt-1">
            Para confirmar o agendamento, acesse a tela de consultas.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAvailabilityStore } from '@/stores/availability'
import { useDoctorStore } from '@/stores/doctor'

const store = useAvailabilityStore()
const doctorStore = useDoctorStore()

const selectedDoctorId = ref('')
const selectedDate = ref('')
const selectedSlot = ref('')
const searched = ref(false)
const today = new Date().toISOString().split('T')[0]

onMounted(async () => {
  if (doctorStore.doctors.length === 0) {
    await doctorStore.fetchDoctors()
  }
})

async function search() {
  if (!selectedDoctorId.value || !selectedDate.value) return
  selectedSlot.value = ''
  searched.value = true
  await store.fetchSlots(selectedDoctorId.value, selectedDate.value)
}

function selectSlot(time: string) {
  selectedSlot.value = selectedSlot.value === time ? '' : time
}

function formatDate(dateStr: string): string {
  return new Date(dateStr + 'T00:00:00').toLocaleDateString('pt-BR', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  })
}
</script>
