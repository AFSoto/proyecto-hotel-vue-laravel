<template>
  <div class="space-y-6">
    <!-- Encabezado -->
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Tipos de habitación</h1>
        <p class="text-sm text-gray-500">Categorías y precios base del hotel</p>
      </div>

      <AppButton @click="abrirCrear">
        <Plus class="w-4 h-4" />
        Nuevo tipo
      </AppButton>
    </div>

    <!-- Filtro -->
    <div class="flex">
      <div class="flex-1 sm:max-w-xs">
        <AppInput v-model="filtros.search" placeholder="Buscar por nombre..." />
      </div>
    </div>

    <!-- Tabla -->
    <AppTable
      :columns="columns"
      :rows="roomTypes"
      :loading="loading"
      :pagination="meta"
      @page-change="filtros.page = $event"
    >
      <!-- Descripción (recortada) -->
      <template #cell-description="{ row }">
        <span class="text-gray-500">{{ truncar(row.description) }}</span>
      </template>

      <!-- Precio base -->
      <template #cell-base_price="{ row }">
        <span class="font-medium text-gray-800">{{ formatoPrecio(row.base_price) }}</span>
      </template>

      <!-- Nº de habitaciones -->
      <template #cell-rooms_count="{ row }">
        <AppBadge variant="info">{{ row.rooms_count ?? 0 }}</AppBadge>
      </template>

      <!-- Acciones -->
      <template #cell-actions="{ row }">
        <div class="flex gap-2">
          <AppButton size="sm" variant="ghost" @click="abrirEditar(row)">Editar</AppButton>
          <AppButton size="sm" variant="danger" @click="pedirEliminar(row)">Eliminar</AppButton>
        </div>
      </template>
    </AppTable>

    <!-- Modales -->
    <RoomTypeFormModal
      :open="formOpen"
      :mode="formMode"
      :saving="saving"
      :errors="errors"
      :room-type="seleccionado"
      @close="formOpen = false"
      @submit="guardar"
    />

    <ConfirmDialog
      :open="confirmOpen"
      title="Eliminar tipo"
      :message="`¿Eliminar el tipo '${seleccionado?.name}'? No se podrá si tiene habitaciones asociadas.`"
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

import { useRoomTypes } from '@/composables/useRoomTypes'
import { useToast } from '@/composables/useToast'

import AppTable from '@/components/common/AppTable.vue'
import AppBadge from '@/components/common/AppBadge.vue'
import AppButton from '@/components/common/AppButton.vue'
import AppInput from '@/components/common/AppInput.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import RoomTypeFormModal from '@/components/roomTypes/RoomTypeFormModal.vue'

const toast = useToast()

const {
  roomTypes,
  loading,
  saving,
  errors,
  meta,
  filtros,
  cargar,
  crear,
  actualizar,
  eliminar,
  resetErrors,
} = useRoomTypes()

const columns = [
  { key: 'name', label: 'Nombre', width: '180px' },
  { key: 'description', label: 'Descripción' },
  { key: 'base_price', label: 'Precio base', width: '140px' },
  { key: 'rooms_count', label: 'Habitaciones', width: '130px' },
  { key: 'actions', label: 'Acciones', width: '200px' },
]

// Recorta descripciones largas para la tabla
const truncar = (t) => (!t ? '—' : t.length > 60 ? t.slice(0, 60) + '…' : t)

// Formato simple de precio
const formatoPrecio = (v) => '$ ' + Number(v).toFixed(2)

// ─── Estado de UI ───────────────────────────────
const formOpen = ref(false)
const formMode = ref('crear')
const confirmOpen = ref(false)
const seleccionado = ref(null)

// ─── Crear / Editar ─────────────────────────────
function abrirCrear() {
  seleccionado.value = null
  formMode.value = 'crear'
  resetErrors()
  formOpen.value = true
}

function abrirEditar(rt) {
  seleccionado.value = rt
  formMode.value = 'editar'
  resetErrors()
  formOpen.value = true
}

async function guardar(payload) {
  try {
    if (formMode.value === 'editar') {
      await actualizar(seleccionado.value.id, payload)
      toast.success('Tipo actualizado.')
    } else {
      await crear(payload)
      toast.success('Tipo creado.')
    }
    formOpen.value = false
  } catch (e) {
    // Los 422 se muestran por campo en el modal; el resto como toast
    if (e.response?.status !== 422) {
      toast.error(e.response?.data?.message || 'Ocurrió un error.')
    }
  }
}

// ─── Eliminar ───────────────────────────────────
function pedirEliminar(rt) {
  seleccionado.value = rt
  confirmOpen.value = true
}

async function confirmarEliminar() {
  try {
    await eliminar(seleccionado.value.id)
    toast.success('Tipo eliminado.')
    confirmOpen.value = false
  } catch (e) {
    // Regla de negocio: 409 si el tipo tiene habitaciones asociadas
    toast.error(e.response?.data?.message || 'No se pudo eliminar.')
    confirmOpen.value = false
  }
}

onMounted(cargar)
</script>
