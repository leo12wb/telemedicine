<template>
  <div>
    <h2 class="text-xl font-semibold text-gray-800 mb-6">
      Bem-vindo, {{ authStore.user?.name }}!
    </h2>

    <div v-if="loading" class="text-sm text-gray-400">Carregando...</div>

    <!-- ─── Admin ─────────────────────────────────────────────────── -->
    <template v-else-if="authStore.user?.role === 'admin' && adminData">
      <!-- Totais -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <p class="text-3xl font-bold text-blue-600">{{ adminData.totals.doctors }}</p>
          <p class="text-xs text-gray-500 mt-1">Médicos</p>
          <p class="text-xs text-green-600">{{ adminData.totals.doctors_active }} ativos</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <p class="text-3xl font-bold text-blue-600">{{ adminData.totals.patients }}</p>
          <p class="text-xs text-gray-500 mt-1">Pacientes</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <p class="text-3xl font-bold text-blue-600">{{ adminData.totals.users }}</p>
          <p class="text-xs text-gray-500 mt-1">Usuários</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <p class="text-3xl font-bold text-blue-600">{{ totalAppointments }}</p>
          <p class="text-xs text-gray-500 mt-1">Consultas (total)</p>
        </div>
      </div>

      <!-- Consultas por status -->
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-8">
        <div
          v-for="(count, status) in adminData.appointments_by_status"
          :key="status"
          class="bg-white rounded-lg shadow px-4 py-3 text-center"
        >
          <p class="text-2xl font-bold text-gray-800">{{ count }}</p>
          <p class="text-xs mt-1" :class="statusColor(status as string)">{{ statusLabel(status as string) }}</p>
        </div>
      </div>

      <!-- Consultas de hoje -->
      <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-base font-semibold text-gray-700 mb-3">Consultas de hoje</h3>
        <AppointmentList :appointments="adminData.today_appointments" />
      </div>

      <!-- Próximas consultas -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-base font-semibold text-gray-700 mb-3">Próximas agendadas</h3>
        <AppointmentList :appointments="adminData.upcoming_appointments" show-doctor show-patient />
      </div>
    </template>

    <!-- ─── Médico ─────────────────────────────────────────────────── -->
    <template v-else-if="authStore.user?.role === 'medico' && doctorData">
      <!-- Alertas -->
      <div v-if="doctorData.absent_this_week > 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6 flex items-center gap-3">
        <span class="text-yellow-700 font-semibold text-sm">
          ⚠ {{ doctorData.absent_this_week }} paciente{{ doctorData.absent_this_week !== 1 ? 's' : '' }} ausente{{ doctorData.absent_this_week !== 1 ? 's' : '' }} esta semana.
        </span>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-8">
        <div
          v-for="(count, status) in doctorData.week_summary"
          :key="status"
          class="bg-white rounded-lg shadow px-4 py-3 text-center"
        >
          <p class="text-2xl font-bold text-gray-800">{{ count }}</p>
          <p class="text-xs mt-1" :class="statusColor(status as string)">{{ statusLabel(status as string) }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-base font-semibold text-gray-700 mb-3">Agenda de hoje</h3>
          <AppointmentList :appointments="doctorData.today_appointments" show-patient />
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-base font-semibold text-gray-700 mb-3">Próximas consultas</h3>
          <AppointmentList :appointments="doctorData.upcoming_appointments" show-patient />
        </div>
      </div>
    </template>

    <!-- ─── Paciente ───────────────────────────────────────────────── -->
    <template v-else-if="authStore.user?.role === 'paciente' && patientData">
      <div class="flex items-center justify-between mb-6">
        <div class="bg-white rounded-lg shadow px-6 py-4">
          <p class="text-3xl font-bold text-blue-600">{{ patientData.total_concluded }}</p>
          <p class="text-xs text-gray-500 mt-1">Consultas concluídas</p>
        </div>
        <RouterLink
          to="/appointments"
          class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700"
        >
          Agendar consulta
        </RouterLink>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-base font-semibold text-gray-700 mb-3">Próximas consultas</h3>
          <AppointmentList :appointments="patientData.upcoming_appointments" show-doctor />
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-base font-semibold text-gray-700 mb-3">Histórico recente</h3>
          <AppointmentList :appointments="patientData.recent_history" show-doctor />
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, defineComponent, h } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { dashboardService, type AdminDashboard, type DoctorDashboard, type PatientDashboard, type AppointmentSummary } from '@/services/dashboard'
import { STATUS_COLORS } from '@/types/appointment'
import type { AppointmentStatus } from '@/types/appointment'

const authStore = useAuthStore()
const loading   = ref(true)
const adminData   = ref<AdminDashboard | null>(null)
const doctorData  = ref<DoctorDashboard | null>(null)
const patientData = ref<PatientDashboard | null>(null)

onMounted(async () => {
  try {
    const data = await dashboardService.get()
    const role = authStore.user?.role
    if (role === 'admin')    adminData.value   = data as AdminDashboard
    if (role === 'medico')   doctorData.value  = data as DoctorDashboard
    if (role === 'paciente') patientData.value = data as PatientDashboard
  } finally {
    loading.value = false
  }
})

const totalAppointments = computed(() => {
  if (!adminData.value) return 0
  return Object.values(adminData.value.appointments_by_status).reduce((sum, v) => sum + v, 0)
})

const STATUS_LABELS: Record<string, string> = {
  agendada:        'Agendada',
  em_andamento:    'Em andamento',
  concluida:       'Concluída',
  cancelada:       'Cancelada',
  paciente_ausente:'Pct. ausente',
}

function statusLabel(status: string): string {
  return STATUS_LABELS[status] ?? status
}

function statusColor(status: string): string {
  return STATUS_COLORS[status as AppointmentStatus] ?? 'text-gray-600'
}

// ─── Mini-componente inline para lista de consultas ───────────────────────────
const AppointmentList = defineComponent({
  props: {
    appointments: { type: Array as () => AppointmentSummary[], required: true },
    showDoctor: Boolean,
    showPatient: Boolean,
  },
  setup(props) {
    const router = useRouter()

    function formatDate(d: string) {
      return new Date(d + 'T00:00:00').toLocaleDateString('pt-BR')
    }

    function handleClick(a: AppointmentSummary) {
      if (a.status === 'agendada' || a.status === 'em_andamento') {
        router.push({ name: 'meeting-room', params: { appointmentId: a.id } })
      } else {
        router.push({ name: 'appointments' })
      }
    }

    return () => {
      if (props.appointments.length === 0) {
        return h('p', { class: 'text-sm text-gray-400' }, 'Nenhuma consulta.')
      }
      return h('ul', { class: 'divide-y divide-gray-100' },
        props.appointments.map((a) => {
          const isActive = a.status === 'agendada' || a.status === 'em_andamento'
          return h('li', {
            key: a.id,
            class: ['py-2 flex items-center justify-between text-sm rounded px-1 -mx-1 transition-colors',
              isActive ? 'cursor-pointer hover:bg-blue-50' : 'cursor-pointer hover:bg-gray-50',
            ].join(' '),
            onClick: () => handleClick(a),
          }, [
            h('div', [
              h('span', { class: 'font-medium text-gray-800' }, formatDate(a.scheduled_date)),
              h('span', { class: 'ml-2 text-gray-500' }, a.scheduled_time.slice(0, 5)),
              props.showDoctor && a.doctor_name
                ? h('span', { class: 'ml-2 text-blue-600' }, a.doctor_name)
                : null,
              props.showPatient && a.patient_name
                ? h('span', { class: 'ml-2 text-green-600' }, a.patient_name)
                : null,
              isActive
                ? h('span', { class: 'ml-2 text-xs text-indigo-500 font-medium' }, '→ Entrar')
                : null,
            ]),
            h('span', {
              class: [STATUS_COLORS[a.status as AppointmentStatus], 'px-2 py-0.5 rounded text-xs font-medium'].join(' '),
            }, STATUS_LABELS[a.status] ?? a.status),
          ])
        })
      )
    }
  },
})
</script>
