<template>
  <AppModal
    :open="open"
    :title="mode === 'editar' ? 'Editar habitación' : 'Nueva habitación'"
    size="md"
    @close="$emit('close')"
  >
    <div class="space-y-4">
      <!-- Número -->
      <AppInput
        v-model="form.number"
        label="Número"
        placeholder="Ej: 101"
        :error="fieldError('number')"
      />

      <!-- Piso -->
      <AppInput
        v-model="form.floor"
        label="Piso"
        type="number"
        placeholder="1"
        :error="fieldError('floor')"
      />

      <!-- Tipo de habitación -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de habitación</label>
        <select v-model="form.room_type_id" :class="selectClass">
          <option value="" disabled>Selecciona un tipo</option>
          <option v-for="t in roomTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
        </select>
        <p v-if="fieldError('room_type_id')" class="mt-1 text-sm text-red-500">
          {{ fieldError('room_type_id') }}
        </p>
      </div>

      <!-- Estado -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
        <select v-model="form.status" :class="selectClass">
          <option v-for="s in ROOM_STATUSES" :key="s.value" :value="s.value">{{ s.label }}</option>
        </select>
        <p v-if="fieldError('status')" class="mt-1 text-sm text-red-500">
          {{ fieldError('status') }}
        </p>
      </div>

      <!-- Notas -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
        <textarea
          v-model="form.notes"
          rows="3"
          placeholder="Observaciones (opcional)"
          :class="selectClass"
        />
      </div>
    </div>

    <template #footer>
      <div class="flex items-center justify-end gap-3">
        <AppButton variant="secondary" :disabled="saving" @click="$emit('close')">
          Cancelar
        </AppButton>
        <AppButton :loading="saving" @click="submit">
          {{ mode === 'editar' ? 'Guardar cambios' : 'Crear habitación' }}
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
import { ROOM_STATUSES } from '@/constants/rooms'

const props = defineProps({
  open: { type: Boolean, default: false },
  mode: { type: String, default: 'crear' }, // 'crear' | 'editar'
  saving: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) },
  room: { type: Object, default: null },
  roomTypes: { type: Array, default: () => [] },
})

const emit = defineEmits(['close', 'submit'])

// Clases compartidas para selects y textarea (mismo estilo que AppInput)
const selectClass =
  'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none transition-colors bg-white focus:border-[#1A2B4A] focus:ring-2 focus:ring-blue-100'

// Estado inicial del formulario (permite resetear)
const estadoInicial = () => ({
  number: '',
  floor: 1,
  room_type_id: '',
  status: 'available',
  notes: '',
})

const form = reactive(estadoInicial())

// Devuelve el primer mensaje de error del backend para un campo
function fieldError(field) {
  const e = props.errors?.[field]
  return Array.isArray(e) ? (e[0] ?? '') : (e ?? '')
}

// Al abrir: rellena en modo editar, resetea en modo crear
watch(
  () => props.open,
  (open) => {
    if (!open) return
    if (props.mode === 'editar' && props.room) {
      Object.assign(form, {
        number: props.room.number,
        floor: props.room.floor,
        room_type_id: props.room.room_type_id,
        status: props.room.status,
        notes: props.room.notes ?? '',
      })
    } else {
      Object.assign(form, estadoInicial())
    }
  },
)

function submit() {
  emit('submit', {
    number: form.number,
    floor: Number(form.floor),
    room_type_id: form.room_type_id,
    status: form.status,
    notes: form.notes || null,
  })
}
</script>
