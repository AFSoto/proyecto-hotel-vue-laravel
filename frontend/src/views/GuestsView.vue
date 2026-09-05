<template>
  <div class="space-y-6">
    <!-- Encabezado -->
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Huéspedes</h1>
        <p class="text-sm text-gray-500">Gestión de huéspedes del hotel</p>
      </div>

      <!-- Admin y recepción pueden registrar huéspedes -->
      <AppButton @click="abrirCrear">
        <Plus class="w-4 h-4" />
        Nuevo huésped
      </AppButton>
    </div>

    <!-- Filtros -->
    <div class="flex flex-col sm:flex-row gap-3">
      <div class="flex-1">
        <AppInput v-model="filtros.search" placeholder="Buscar por nombre o documento..." />
      </div>
    </div>

    <!-- Tabla -->
    <AppTable
      :columns="columns"
      :rows="guests"
      :loading="loading"
      :pagination="meta"
      @page-change="filtros.page = $event"
    >
      <!-- Documento -->
      <template #cell-document="{ row }">
        <span class="uppercase text-gray-400 text-xs mr-1">{{ row.document_type }}</span>
        {{ row.document_number }}
      </template>

      <!-- Email -->
      <template #cell-email="{ row }">
        {{ row.email || '—' }}
      </template>

      <!-- Teléfono -->
      <template #cell-phone="{ row }">
        {{ row.phone || '—' }}
      </template>

      <!-- Acciones -->
      <template #cell-actions="{ row }">
        <div class="flex gap-2">
          <AppButton size="sm" variant="ghost" @click="abrirEditar(row)">Editar</AppButton>
          <AppButton v-if="auth.isAdmin" size="sm" variant="danger" @click="pedirEliminar(row)">
            Eliminar
          </AppButton>
        </div>
      </template>
    </AppTable>

    <!-- Modales -->
    <GuestFormModal
      :open="formOpen"
      :mode="formMode"
      :saving="saving"
      :errors="errors"
      :guest="seleccionado"
      @close="formOpen = false"
      @submit="guardar"
    />

    <ConfirmDialog
      :open="confirmOpen"
      title="Eliminar huésped"
      :message="`¿Eliminar al huésped ${seleccionado?.full_name}? Podrás recuperarlo (soft delete).`"
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
import { useGuests } from '@/composables/useGuests'
import { useToast } from '@/composables/useToast'

import AppTable from '@/components/common/AppTable.vue'
import AppButton from '@/components/common/AppButton.vue'
import AppInput from '@/components/common/AppInput.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import GuestFormModal from '@/components/guests/GuestFormModal.vue'

const auth = useAuthStore()
const toast = useToast()

const { guests, loading, saving, errors, meta, filtros, cargar, crear, actualizar, eliminar, resetErrors } =
  useGuests()

const columns = [
  { key: 'full_name', label: 'Nombre' },
  { key: 'document', label: 'Documento', width: '200px' },
  { key: 'email', label: 'Email' },
  { key: 'phone', label: 'Teléfono', width: '150px' },
  { key: 'actions', label: 'Acciones', width: '180px' },
]

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

function abrirEditar(guest) {
  seleccionado.value = guest
  formMode.value = 'editar'
  resetErrors()
  formOpen.value = true
}

async function guardar(payload) {
  try {
    if (formMode.value === 'editar') {
      await actualizar(seleccionado.value.id, payload)
      toast.success('Huésped actualizado.')
    } else {
      await crear(payload)
      toast.success('Huésped creado.')
    }
    formOpen.value = false
  } catch (e) {
    // Los 422 se muestran por campo dentro del modal; el resto como toast
    if (e.response?.status !== 422) {
      toast.error(e.response?.data?.message || 'Ocurrió un error.')
    }
  }
}

// ─── Eliminar ───────────────────────────────────
function pedirEliminar(guest) {
  seleccionado.value = guest
  confirmOpen.value = true
}

async function confirmarEliminar() {
  try {
    await eliminar(seleccionado.value.id)
    toast.success('Huésped eliminado.')
    confirmOpen.value = false
  } catch (e) {
    // Regla de negocio: no se puede borrar un huésped con reservas activas (409)
    toast.error(e.response?.data?.message || 'No se pudo eliminar.')
    confirmOpen.value = false
  }
}

// Carga inicial
onMounted(() => {
  cargar()
})
</script>
