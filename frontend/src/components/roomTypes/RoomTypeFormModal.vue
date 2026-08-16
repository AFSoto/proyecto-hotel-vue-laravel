<template>
  <AppModal
    :open="open"
    :title="mode === 'editar' ? 'Editar tipo' : 'Nuevo tipo de habitación'"
    size="md"
    @close="$emit('close')"
  >
    <div class="space-y-4">
      <!-- Nombre -->
      <AppInput
        v-model="form.name"
        label="Nombre"
        placeholder="Ej: Suite"
        :error="fieldError('name')"
      />

      <!-- Precio base -->
      <AppInput
        v-model="form.base_price"
        label="Precio base"
        type="number"
        placeholder="0.00"
        :error="fieldError('base_price')"
      />

      <!-- Descripción -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
        <textarea
          v-model="form.description"
          rows="3"
          placeholder="Descripción del tipo (opcional)"
          :class="textareaClass"
        />
        <p v-if="fieldError('description')" class="mt-1 text-sm text-red-500">
          {{ fieldError('description') }}
        </p>
      </div>
    </div>

    <template #footer>
      <div class="flex items-center justify-end gap-3">
        <AppButton variant="secondary" :disabled="saving" @click="$emit('close')">
          Cancelar
        </AppButton>
        <AppButton :loading="saving" @click="submit">
          {{ mode === 'editar' ? 'Guardar cambios' : 'Crear tipo' }}
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
  roomType: { type: Object, default: null },
})

const emit = defineEmits(['close', 'submit'])

// Mismo estilo que AppInput, para el textarea
const textareaClass =
  'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none transition-colors bg-white focus:border-[#1A2B4A] focus:ring-2 focus:ring-blue-100'

const estadoInicial = () => ({
  name: '',
  base_price: '',
  description: '',
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
    if (props.mode === 'editar' && props.roomType) {
      Object.assign(form, {
        name: props.roomType.name,
        base_price: props.roomType.base_price,
        description: props.roomType.description ?? '',
      })
    } else {
      Object.assign(form, estadoInicial())
    }
  },
)

function submit() {
  emit('submit', {
    name: form.name,
    base_price: Number(form.base_price),
    description: form.description || null,
  })
}
</script>
