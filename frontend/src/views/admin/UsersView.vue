<template>
  <div class="space-y-6">
    <!-- Encabezado -->
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Usuarios</h1>
        <p class="text-sm text-gray-500">Empleados que operan el sistema</p>
      </div>

      <AppButton @click="abrirCrear">
        <Plus class="w-4 h-4" />
        Nuevo usuario
      </AppButton>
    </div>

    <!-- Filtros -->
    <div class="flex flex-col sm:flex-row gap-3">
      <div class="flex-1">
        <AppInput v-model="filtros.search" placeholder="Buscar por nombre o email..." />
      </div>

      <select v-model="filtros.role_id" :class="selectClass">
        <option value="">Todos los roles</option>
        <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
      </select>
    </div>

    <!-- Tabla -->
    <AppTable
      :columns="columns"
      :rows="users"
      :loading="loading"
      :pagination="meta"
      @page-change="filtros.page = $event"
    >
      <!-- Rol -->
      <template #cell-role="{ row }">
        <AppBadge :variant="row.role.slug">{{ row.role.name }}</AppBadge>
      </template>

      <!-- Estado -->
      <template #cell-status="{ row }">
        <AppBadge :variant="row.is_active ? 'active' : 'inactive'">
          {{ row.is_active ? 'Activo' : 'Inactivo' }}
        </AppBadge>
      </template>

      <!-- Acciones -->
      <template #cell-actions="{ row }">
        <div class="flex gap-2">
          <AppButton size="sm" variant="ghost" @click="abrirEditar(row)">Editar</AppButton>
          <AppButton size="sm" variant="danger" @click="pedirEliminar(row)">Desactivar</AppButton>
        </div>
      </template>
    </AppTable>

    <!-- Modales -->
    <UserFormModal
      :open="formOpen"
      :mode="formMode"
      :saving="saving"
      :errors="errors"
      :user="seleccionado"
      :roles="roles"
      @close="formOpen = false"
      @submit="guardar"
    />

    <ConfirmDialog
      :open="confirmOpen"
      title="Desactivar usuario"
      :message="`Se desactivará y ocultará a '${seleccionado?.name}'. ¿Continuar?`"
      confirm-label="Desactivar"
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

import { useUsers } from '@/composables/useUsers'
import { useToast } from '@/composables/useToast'

import AppTable from '@/components/common/AppTable.vue'
import AppBadge from '@/components/common/AppBadge.vue'
import AppButton from '@/components/common/AppButton.vue'
import AppInput from '@/components/common/AppInput.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import UserFormModal from '@/components/users/UserFormModal.vue'

const toast = useToast()

const {
  users,
  roles,
  loading,
  saving,
  errors,
  meta,
  filtros,
  cargar,
  cargarRoles,
  crear,
  actualizar,
  eliminar,
  resetErrors,
} = useUsers()

const selectClass =
  'rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none transition-colors bg-white focus:border-[#1A2B4A] focus:ring-2 focus:ring-blue-100'

const columns = [
  { key: 'name', label: 'Nombre' },
  { key: 'email', label: 'Email' },
  { key: 'role', label: 'Rol', width: '150px' },
  { key: 'status', label: 'Estado', width: '110px' },
  { key: 'actions', label: 'Acciones', width: '200px' },
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

function abrirEditar(user) {
  seleccionado.value = user
  formMode.value = 'editar'
  resetErrors()
  formOpen.value = true
}

async function guardar(payload) {
  try {
    if (formMode.value === 'editar') {
      await actualizar(seleccionado.value.id, payload)
      toast.success('Usuario actualizado.')
    } else {
      await crear(payload)
      toast.success('Usuario creado.')
    }
    formOpen.value = false
  } catch (e) {
    // Los 422 se muestran por campo en el modal; el resto como toast
    if (e.response?.status !== 422) {
      toast.error(e.response?.data?.message || 'Ocurrió un error.')
    }
  }
}

// ─── Desactivar ─────────────────────────────────
function pedirEliminar(user) {
  seleccionado.value = user
  confirmOpen.value = true
}

async function confirmarEliminar() {
  try {
    await eliminar(seleccionado.value.id)
    toast.success('Usuario desactivado.')
    confirmOpen.value = false
  } catch (e) {
    toast.error(e.response?.data?.message || 'No se pudo desactivar.')
    confirmOpen.value = false
  }
}

// Carga inicial
onMounted(() => {
  cargar()
  cargarRoles()
})
</script>
