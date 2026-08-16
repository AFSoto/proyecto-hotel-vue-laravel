<template>
  <AppModal
    :open="open"
    :title="mode === 'editar' ? 'Editar usuario' : 'Nuevo usuario'"
    size="md"
    @close="$emit('close')"
  >
    <div class="space-y-4">
      <!-- Nombre -->
      <AppInput
        v-model="form.name"
        label="Nombre"
        placeholder="Ej: María Recepción"
        :error="fieldError('name')"
      />

      <!-- Email -->
      <AppInput
        v-model="form.email"
        label="Email"
        type="email"
        placeholder="correo@hotel.com"
        :error="fieldError('email')"
      />

      <!-- Contraseña (solo al crear; el backend no permite cambiarla en update) -->
      <template v-if="mode === 'crear'">
        <AppInput
          v-model="form.password"
          label="Contraseña"
          type="password"
          placeholder="Mínimo 8 caracteres"
          :error="fieldError('password')"
        />
        <AppInput
          v-model="form.password_confirmation"
          label="Confirmar contraseña"
          type="password"
          placeholder="Repite la contraseña"
        />
      </template>

      <!-- Rol -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Rol</label>
        <select v-model="form.role_id" :class="selectClass">
          <option value="" disabled>Selecciona un rol</option>
          <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
        </select>
        <p v-if="fieldError('role_id')" class="mt-1 text-sm text-red-500">
          {{ fieldError('role_id') }}
        </p>
      </div>
    </div>

    <template #footer>
      <div class="flex items-center justify-end gap-3">
        <AppButton variant="secondary" :disabled="saving" @click="$emit('close')">
          Cancelar
        </AppButton>
        <AppButton :loading="saving" @click="submit">
          {{ mode === 'editar' ? 'Guardar cambios' : 'Crear usuario' }}
        </AppButton>
      </div>
    </template>
  </AppModal>
</template>

<script setup>
import { reactive, watch } from 'vue'
import AppModal from '@/components/common/AppModal.vue'
import AppInput from '@/components/common/AppInput.vue'
import AppButton from '@/components/common/AppButton.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  mode: { type: String, default: 'crear' }, // 'crear' | 'editar'
  saving: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) },
  user: { type: Object, default: null },
  roles: { type: Array, default: () => [] },
})

const emit = defineEmits(['close', 'submit'])

const selectClass =
  'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none transition-colors bg-white focus:border-[#1A2B4A] focus:ring-2 focus:ring-blue-100'

const estadoInicial = () => ({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role_id: '',
})

const form = reactive(estadoInicial())

function fieldError(field) {
  const e = props.errors?.[field]
  return Array.isArray(e) ? (e[0] ?? '') : (e ?? '')
}

// Al abrir: rellena en modo editar, resetea en modo crear
watch(
  () => props.open,
  (open) => {
    if (!open) return
    if (props.mode === 'editar' && props.user) {
      Object.assign(form, {
        name: props.user.name,
        email: props.user.email,
        password: '',
        password_confirmation: '',
        role_id: props.user.role.id,
      })
    } else {
      Object.assign(form, estadoInicial())
    }
  },
)

function submit() {
  if (props.mode === 'editar') {
    // El update solo acepta estos campos
    emit('submit', {
      name: form.name,
      email: form.email,
      role_id: form.role_id,
    })
  } else {
    emit('submit', {
      name: form.name,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation,
      role_id: form.role_id,
    })
  }
}
</script>
