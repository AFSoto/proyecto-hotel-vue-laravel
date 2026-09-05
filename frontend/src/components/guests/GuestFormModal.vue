<template>
  <AppModal
    :open="open"
    :title="mode === 'editar' ? 'Editar huésped' : 'Nuevo huésped'"
    size="md"
    @close="$emit('close')"
  >
    <div class="space-y-4">
      <!-- Nombre completo -->
      <AppInput
        v-model="form.full_name"
        label="Nombre completo"
        placeholder="Ej: María Fernanda López"
        :error="fieldError('full_name')"
      />

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Tipo de documento -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de documento</label>
          <select v-model="form.document_type" :class="selectClass">
            <option v-for="t in DOCUMENT_TYPES" :key="t.value" :value="t.value">{{ t.label }}</option>
          </select>
          <p v-if="fieldError('document_type')" class="mt-1 text-sm text-red-500">
            {{ fieldError('document_type') }}
          </p>
        </div>

        <!-- Número de documento -->
        <AppInput
          v-model="form.document_number"
          label="Número de documento"
          placeholder="Ej: 1090123456"
          :error="fieldError('document_number')"
        />
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Email -->
        <AppInput
          v-model="form.email"
          label="Email (opcional)"
          type="email"
          placeholder="correo@ejemplo.com"
          :error="fieldError('email')"
        />

        <!-- Teléfono -->
        <AppInput
          v-model="form.phone"
          label="Teléfono (opcional)"
          placeholder="Ej: 3001112233"
          :error="fieldError('phone')"
        />
      </div>
    </div>

    <template #footer>
      <div class="flex items-center justify-end gap-3">
        <AppButton variant="secondary" :disabled="saving" @click="$emit('close')">
          Cancelar
        </AppButton>
        <AppButton :loading="saving" @click="submit">
          {{ mode === 'editar' ? 'Guardar cambios' : 'Crear huésped' }}
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
import { DOCUMENT_TYPES } from '@/constants/guests'

const props = defineProps({
  open: { type: Boolean, default: false },
  mode: { type: String, default: 'crear' }, // 'crear' | 'editar'
  saving: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) },
  guest: { type: Object, default: null },
})

const emit = defineEmits(['close', 'submit'])

const selectClass =
  'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none transition-colors bg-white focus:border-[#1A2B4A] focus:ring-2 focus:ring-blue-100'

const estadoInicial = () => ({
  full_name: '',
  document_type: 'cc',
  document_number: '',
  email: '',
  phone: '',
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
    if (props.mode === 'editar' && props.guest) {
      Object.assign(form, {
        full_name: props.guest.full_name ?? '',
        document_type: props.guest.document_type ?? 'cc',
        document_number: props.guest.document_number ?? '',
        email: props.guest.email ?? '',
        phone: props.guest.phone ?? '',
      })
    } else {
      Object.assign(form, estadoInicial())
    }
  },
)

function submit() {
  // El backend convierte cadenas vacías en null (email/phone opcionales)
  emit('submit', {
    full_name: form.full_name,
    document_type: form.document_type,
    document_number: form.document_number,
    email: form.email,
    phone: form.phone,
  })
}
</script>
