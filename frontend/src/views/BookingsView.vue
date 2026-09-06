<template>
  <div class="space-y-6">
    <!-- Encabezado -->
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Reservas</h1>
        <p class="text-sm text-gray-500">Gestión de reservas del hotel</p>
      </div>

      <!-- Admin y recepción pueden crear reservas -->
      <AppButton @click="abrirCrear">
        <Plus class="w-4 h-4" />
        Nueva reserva
      </AppButton>
    </div>

    <!-- Filtros -->
    <div class="flex flex-col lg:flex-row gap-3">
      <div class="flex-1">
        <AppInput v-model="filtros.search" placeholder="Buscar por huésped o documento..." />
      </div>

      <select v-model="filtros.status" :class="selectClass">
        <option value="">Todos los estados</option>
        <option v-for="s in BOOKING_STATUSES" :key="s.value" :value="s.value">{{ s.label }}</option>
      </select>

      <!-- Rango de fechas: aplica cuando se llenan ambos -->
      <div class="flex items-center gap-2">
        <input v-model="filtros.from" type="date" :class="selectClass" title="Desde" />
        <span class="text-gray-400 text-sm">→</span>
        <input v-model="filtros.to" type="date" :class="selectClass" title="Hasta" />
      </div>
    </div>

    <!-- Tabla -->
    <AppTable
      :columns="columns"
      :rows="bookings"
      :loading="loading"
      :pagination="meta"
      @page-change="filtros.page = $event"
    >
      <!-- Huésped -->
      <template #cell-guest="{ row }">
        <div class="leading-tight">
          <div class="font-medium text-gray-800">{{ row.guest?.full_name ?? '—' }}</div>
          <div class="text-xs text-gray-400 uppercase">{{ row.guest?.document_number ?? '' }}</div>
        </div>
      </template>

      <!-- Habitación -->
      <template #cell-room="{ row }">
        <div class="leading-tight">
          <div>{{ row.room?.number ?? '—' }}</div>
          <div class="text-xs text-gray-400">{{ row.room?.room_type?.name ?? '' }}</div>
        </div>
      </template>

      <!-- Fechas -->
      <template #cell-dates="{ row }">
        {{ row.check_in_date }} <span class="text-gray-400">→</span> {{ row.check_out_date }}
      </template>

      <!-- Estado (badge) -->
      <template #cell-status="{ row }">
        <AppBadge :variant="row.status">{{ statusLabel(row.status) }}</AppBadge>
      </template>

      <!-- Total -->
      <template #cell-total="{ row }">
        {{ formatMoney(row.total_price) }}
      </template>

      <!-- Acciones -->
      <template #cell-actions="{ row }">
        <div class="flex gap-2">
          <!-- Solo se edita/cancela desde 'confirmed' -->
          <template v-if="row.status === 'confirmed'">
            <AppButton size="sm" variant="ghost" @click="abrirEditar(row)">Editar</AppButton>
            <AppButton size="sm" variant="danger" @click="pedirCancelar(row)">Cancelar</AppButton>
          </template>
          <span v-else class="text-xs text-gray-400">—</span>
        </div>
      </template>
    </AppTable>

    <!-- Alta / edición de reserva -->
    <BookingFormModal
      :open="formOpen"
      :mode="formMode"
      :saving="saving"
      :errors="errors"
      :booking="seleccionada"
      :guests="guests"
      :room-types="roomTypes"
      @close="formOpen = false"
      @submit="guardar"
    />

    <!-- Confirmación de cancelación -->
    <ConfirmDialog
      :open="confirmOpen"
      title="Cancelar reserva"
      :message="mensajeCancelar"
      confirm-label="Cancelar reserva"
      danger
      :loading="saving"
      @confirm="confirmarCancelar"
      @cancel="confirmOpen = false"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { Plus } from 'lucide-vue-next'

import { useBookings } from '@/composables/useBookings'
import { useToast } from '@/composables/useToast'
import { BOOKING_STATUSES, BOOKING_STATUS_LABELS } from '@/constants/bookings'

import AppTable from '@/components/common/AppTable.vue'
import AppBadge from '@/components/common/AppBadge.vue'
import AppButton from '@/components/common/AppButton.vue'
import AppInput from '@/components/common/AppInput.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import BookingFormModal from '@/components/bookings/BookingFormModal.vue'

const toast = useToast()

const {
  bookings,
  guests,
  roomTypes,
  loading,
  saving,
  errors,
  meta,
  filtros,
  cargar,
  cargarGuests,
  cargarRoomTypes,
  crear,
  actualizar,
  cancelar,
  resetErrors,
} = useBookings()

const selectClass =
  'rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none transition-colors bg-white focus:border-[#1A2B4A] focus:ring-2 focus:ring-blue-100'

const columns = [
  { key: 'guest', label: 'Huésped' },
  { key: 'room', label: 'Habitación', width: '140px' },
  { key: 'dates', label: 'Fechas', width: '210px' },
  { key: 'status', label: 'Estado', width: '130px' },
  { key: 'total', label: 'Total', width: '120px' },
  { key: 'actions', label: 'Acciones', width: '130px' },
]

const statusLabel = (s) => BOOKING_STATUS_LABELS[s] ?? s

const formatMoney = (v) =>
  `$ ${Number(v).toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`

// ─── Crear / Editar ─────────────────────────────
const formOpen = ref(false)
const formMode = ref('crear')

function abrirCrear() {
  seleccionada.value = null
  formMode.value = 'crear'
  resetErrors()
  formOpen.value = true
}

function abrirEditar(booking) {
  seleccionada.value = booking
  formMode.value = 'editar'
  resetErrors()
  formOpen.value = true
}

async function guardar(payload) {
  try {
    if (formMode.value === 'editar') {
      await actualizar(seleccionada.value.id, payload)
      toast.success('Reserva actualizada.')
    } else {
      await crear(payload)
      toast.success('Reserva creada.')
    }
    formOpen.value = false
  } catch (e) {
    // 422 → se muestra por campo en el modal; 409 (mantenimiento/solape) → toast
    if (e.response?.status === 409) {
      toast.error(e.response?.data?.message || 'La habitación no está disponible.')
    } else if (e.response?.status !== 422) {
      toast.error(e.response?.data?.message || 'Ocurrió un error.')
    }
  }
}

// ─── Cancelación ────────────────────────────────
const confirmOpen = ref(false)
const seleccionada = ref(null)

const mensajeCancelar = computed(() => {
  const b = seleccionada.value
  if (!b) return ''
  return `¿Cancelar la reserva de ${b.guest?.full_name ?? 'este huésped'} en la habitación ${b.room?.number ?? ''}? Esta acción no se puede deshacer.`
})

function pedirCancelar(booking) {
  seleccionada.value = booking
  confirmOpen.value = true
}

async function confirmarCancelar() {
  try {
    await cancelar(seleccionada.value.id)
    toast.success('Reserva cancelada.')
    confirmOpen.value = false
  } catch (e) {
    // Regla de negocio: solo se cancela desde 'confirmed' (409)
    toast.error(e.response?.data?.message || 'No se pudo cancelar la reserva.')
    confirmOpen.value = false
  }
}

// Carga inicial: lista + catálogos para el formulario
onMounted(() => {
  cargar()
  cargarGuests()
  cargarRoomTypes()
})
</script>
