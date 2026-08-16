<template>
  <AppModal :open="open" title="Cambiar estado" size="sm" @close="$emit('close')">
    <div class="space-y-4">
      <p class="text-sm text-gray-600">
        Habitación <strong class="text-gray-800">{{ room?.number }}</strong>
      </p>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nuevo estado</label>
        <select v-model="status" :class="selectClass">
          <option v-for="s in ROOM_STATUSES" :key="s.value" :value="s.value">{{ s.label }}</option>
        </select>
      </div>
    </div>

    <template #footer>
      <div class="flex items-center justify-end gap-3">
        <AppButton variant="secondary" :disabled="saving" @click="$emit('close')">
          Cancelar
        </AppButton>
        <AppButton :loading="saving" @click="$emit('submit', status)"> Guardar </AppButton>
      </div>
    </template>
  </AppModal>
</template>

<script setup>
import { ref, watch } from 'vue'
import AppModal from '@/components/common/AppModal.vue'
import AppButton from '@/components/common/AppButton.vue'
import { ROOM_STATUSES } from '@/constants/rooms'

const props = defineProps({
  open: { type: Boolean, default: false },
  room: { type: Object, default: null },
  saving: { type: Boolean, default: false },
})

defineEmits(['close', 'submit'])

const selectClass =
  'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none transition-colors bg-white focus:border-[#1A2B4A] focus:ring-2 focus:ring-blue-100'

// Estado seleccionado; se sincroniza con la habitación al abrir
const status = ref('available')

watch(
  () => props.room,
  (r) => {
    if (r) status.value = r.status
  },
  { immediate: true },
)
</script>
