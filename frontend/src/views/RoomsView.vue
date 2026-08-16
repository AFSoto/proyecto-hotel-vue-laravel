<template>
  <div class="space-y-6">
    <!-- Encabezado -->
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Habitaciones</h1>
        <p class="text-sm text-gray-500">Gestión de habitaciones del hotel</p>
      </div>

      <!-- Solo admin puede crear -->
      <AppButton v-if="auth.isAdmin" @click="abrirCrear">
        <Plus class="w-4 h-4" />
        Nueva habitación
      </AppButton>
    </div>

    <!-- Filtros -->
    <div class="flex flex-col sm:flex-row gap-3">
      <div class="flex-1">
        <AppInput v-model="filtros.search" placeholder="Buscar por número..." />
      </div>

      <select v-model="filtros.status" :class="selectClass">
        <option value="">Todos los estados</option>
        <option v-for="s in ROOM_STATUSES" :key="s.value" :value="s.value">{{ s.label }}</option>
      </select>

      <select v-model="filtros.room_type_id" :class="selectClass">
        <option value="">Todos los tipos</option>
        <option v-for="t in roomTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
      </select>
    </div>

    <!-- Tabla -->
    <AppTable
      :columns="columns"
      :rows="rooms"
      :loading="loading"
      :pagination="meta"
      @page-change="filtros.page = $event"
    >
      <!-- Tipo -->
      <template #cell-room_type="{ row }">
        {{ row.room_type?.name ?? '—' }}
      </template>

      <!-- Estado (badge) -->
      <template #cell-status="{ row }">
        <AppBadge :variant="row.status">{{ statusLabel(row.status) }}</AppBadge>
      </template>

      <!-- Acciones -->
      <template #cell-actions="{ row }">
        <div class="flex gap-2">
          <AppButton size="sm" variant="ghost" @click="abrirEstado(row)">Estado</AppButton>
          <AppButton v-if="auth.isAdmin" size="sm" variant="ghost" @click="abrirEditar(row)">
            Editar
          </AppButton>
          <AppButton v-if="auth.isAdmin" size="sm" variant="danger" @click="pedirEliminar(row)">
            Eliminar
          </AppButton>
        </div>
      </template>
    </AppTable>

    <!-- Modales -->
    <RoomFormModal
      :open="formOpen"
      :mode="formMode"
      :saving="saving"
      :errors="errors"
      :room="seleccionada"
      :room-types="roomTypes"
      @close="formOpen = false"
      @submit="guardar"
    />

    <RoomStatusModal
      :open="statusOpen"
      :room="seleccionada"
      :saving="saving"
      @close="statusOpen = false"
      @submit="guardarEstado"
    />

    <ConfirmDialog
      :open="confirmOpen"
      title="Eliminar habitación"
      :message="`¿Eliminar la habitación ${seleccionada?.number}? Podrás recuperarla (soft delete).`"
      confirm-label="Eliminar"
      danger
      :loading="saving"
      @confirm="confirmarEliminar"
      @cancel="confirmOpen = false"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { Plus } from 'lucide-vue-next'

import { useAuthStore } from '@/stores/auth'
import { useRooms } from '@/composables/useRooms'
import { useToast } from '@/composables/useToast'
import { ROOM_STATUSES, ROOM_STATUS_LABELS } from '@/constants/rooms'

import AppTable from '@/components/common/AppTable.vue'
import AppBadge from '@/components/common/AppBadge.vue'
import AppButton from '@/components/common/AppButton.vue'
import AppInput from '@/components/common/AppInput.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import RoomFormModal from '@/components/rooms/RoomFormModal.vue'
import RoomStatusModal from '@/components/rooms/RoomStatusModal.vue'

const auth = useAuthStore()
const toast = useToast()

const {
  rooms,
  roomTypes,
  loading,
  saving,
  errors,
  meta,
  filtros,
  cargar,
  cargarTipos,
  crear,
  actualizar,
  cambiarEstado,
  eliminar,
  resetErrors,
} = useRooms()

const selectClass =
  'rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none transition-colors bg-white focus:border-[#1A2B4A] focus:ring-2 focus:ring-blue-100'

const columns = [
  { key: 'number', label: 'Número', width: '120px' },
  { key: 'floor', label: 'Piso', width: '80px' },
  { key: 'room_type', label: 'Tipo' },
  { key: 'status', label: 'Estado', width: '140px' },
  { key: 'actions', label: 'Acciones', width: '240px' },
]

const statusLabel = (s) => ROOM_STATUS_LABELS[s] ?? s

// ─── Estado de UI ───────────────────────────────
const formOpen = ref(false)
const formMode = ref('crear')
const statusOpen = ref(false)
const confirmOpen = ref(false)
const seleccionada = ref(null)

// ─── Crear / Editar ─────────────────────────────
function abrirCrear() {
  seleccionada.value = null
  formMode.value = 'crear'
  resetErrors()
  formOpen.value = true
}

function abrirEditar(room) {
  seleccionada.value = room
  formMode.value = 'editar'
  resetErrors()
  formOpen.value = true
}

async function guardar(payload) {
  try {
    if (formMode.value === 'editar') {
      await actualizar(seleccionada.value.id, payload)
      toast.success('Habitación actualizada.')
    } else {
      await crear(payload)
      toast.success('Habitación creada.')
    }
    formOpen.value = false
  } catch (e) {
    // Los 422 se muestran por campo dentro del modal; el resto como toast
    if (e.response?.status !== 422) {
      toast.error(e.response?.data?.message || 'Ocurrió un error.')
    }
  }
}

// ─── Cambiar estado ─────────────────────────────
function abrirEstado(room) {
  seleccionada.value = room
  statusOpen.value = true
}

async function guardarEstado(status) {
  try {
    await cambiarEstado(seleccionada.value.id, status)
    toast.success('Estado actualizado.')
    statusOpen.value = false
  } catch (e) {
    toast.error(e.response?.data?.message || 'No se pudo cambiar el estado.')
  }
}

// ─── Eliminar ───────────────────────────────────
function pedirEliminar(room) {
  seleccionada.value = room
  confirmOpen.value = true
}

async function confirmarEliminar() {
  try {
    await eliminar(seleccionada.value.id)
    toast.success('Habitación eliminada.')
    confirmOpen.value = false
  } catch (e) {
    // Regla de negocio: no se puede borrar una habitación ocupada (409)
    toast.error(e.response?.data?.message || 'No se pudo eliminar.')
    confirmOpen.value = false
  }
}

// Carga inicial
onMounted(() => {
  cargar()
  cargarTipos()
})
</script>
