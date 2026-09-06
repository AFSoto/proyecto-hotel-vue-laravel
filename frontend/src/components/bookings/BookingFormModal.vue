<template>
  <AppModal
    :open="open"
    :title="mode === 'editar' ? 'Editar reserva' : 'Nueva reserva'"
    size="lg"
    @close="$emit('close')"
  >
    <div class="space-y-4">
      <!-- Huésped -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Huésped</label>
        <select v-model="form.guest_id" :class="selectClass">
          <option value="" disabled>Selecciona un huésped</option>
          <option v-for="g in guests" :key="g.id" :value="g.id">
            {{ g.full_name }} — {{ g.document_number }}
          </option>
        </select>
        <p v-if="fieldError('guest_id')" class="mt-1 text-sm text-red-500">
          {{ fieldError('guest_id') }}
        </p>
      </div>

      <!-- Fechas -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Entrada</label>
          <input v-model="form.check_in_date" type="date" :min="todayStr" :class="selectClass" />
          <p v-if="fieldError('check_in_date')" class="mt-1 text-sm text-red-500">
            {{ fieldError('check_in_date') }}
          </p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Salida</label>
          <input
            v-model="form.check_out_date"
            type="date"
            :min="form.check_in_date || todayStr"
            :class="selectClass"
          />
          <p v-if="fieldError('check_out_date')" class="mt-1 text-sm text-red-500">
            {{ fieldError('check_out_date') }}
          </p>
        </div>
      </div>

      <!-- Filtro opcional por tipo (solo afecta la búsqueda de disponibilidad) -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Filtrar por tipo (opcional)</label>
        <select v-model="form.room_type_id" :class="selectClass">
          <option value="">Todos los tipos</option>
          <option v-for="t in roomTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
        </select>
      </div>

      <!-- Habitación disponible -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Habitación</label>
        <select
          v-model="form.room_id"
          :class="selectClass"
          :disabled="!form.check_in_date || !form.check_out_date || loadingRooms"
        >
          <option value="" disabled>
            {{ hintHabitacion }}
          </option>
          <option v-for="r in roomOptions" :key="r.id" :value="r.id">
            Hab. {{ r.number }} · {{ r.room_type?.name }} · {{ formatMoney(r.room_type?.base_price) }}
            {{ r._actual ? '(actual)' : '' }}
          </option>
        </select>
        <p v-if="fieldError('room_id')" class="mt-1 text-sm text-red-500">
          {{ fieldError('room_id') }}
        </p>
      </div>

      <!-- Notas -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Notas (opcional)</label>
        <textarea
          v-model="form.notes"
          rows="2"
          :class="selectClass"
          placeholder="Observaciones de la reserva..."
        />
      </div>

      <!-- Vista previa del total (solo informativa; el total real lo congela el backend) -->
      <div
        v-if="nights > 0 && totalPreview > 0"
        class="rounded-lg bg-blue-50 border border-blue-100 px-4 py-3 text-sm text-blue-900 flex items-center justify-between"
      >
        <span>{{ nights }} noche{{ nights === 1 ? '' : 's' }} × tarifa base</span>
        <span class="font-semibold">≈ {{ formatMoney(totalPreview) }}</span>
      </div>
    </div>

    <template #footer>
      <div class="flex items-center justify-end gap-3">
        <AppButton variant="secondary" :disabled="saving" @click="$emit('close')">Cancelar</AppButton>
        <AppButton :loading="saving" @click="submit">
          {{ mode === 'editar' ? 'Guardar cambios' : 'Crear reserva' }}
        </AppButton>
      </div>
    </template>
  </AppModal>
</template>

<script setup>
import { computed, reactive, watch } from 'vue'
import AppModal from '@/components/common/AppModal.vue'
import AppButton from '@/components/common/AppButton.vue'
import { useRoomAvailability } from '@/composables/useRoomAvailability'

const props = defineProps({
  open: { type: Boolean, default: false },
  mode: { type: String, default: 'crear' }, // 'crear' | 'editar'
  saving: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) },
  booking: { type: Object, default: null },
  guests: { type: Array, default: () => [] },
  roomTypes: { type: Array, default: () => [] },
})

const emit = defineEmits(['close', 'submit'])

const { availableRooms, loadingRooms, buscar, reset } = useRoomAvailability()

const selectClass =
  'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none transition-colors bg-white focus:border-[#1A2B4A] focus:ring-2 focus:ring-blue-100 disabled:bg-gray-50 disabled:text-gray-400'

// Fecha de hoy (local) para el atributo min de los inputs
const hoy = new Date()
const todayStr = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}-${String(hoy.getDate()).padStart(2, '0')}`

const estadoInicial = () => ({
  guest_id: '',
  check_in_date: '',
  check_out_date: '',
  room_type_id: '',
  room_id: '',
  notes: '',
})

const form = reactive(estadoInicial())

function fieldError(field) {
  const e = props.errors?.[field]
  return Array.isArray(e) ? (e[0] ?? '') : (e ?? '')
}

const formatMoney = (v) =>
  `$ ${Number(v ?? 0).toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`

// Opciones de habitación: las disponibles + (al editar) la habitación actual,
// que /rooms/available excluye por estar ocupada por esta misma reserva.
const roomOptions = computed(() => {
  const list = [...availableRooms.value]
  if (props.mode === 'editar' && props.booking?.room) {
    const cur = props.booking.room
    if (!list.some((r) => r.id === cur.id)) {
      list.unshift({ ...cur, _actual: true })
    }
  }
  return list
})

const hintHabitacion = computed(() => {
  if (!form.check_in_date || !form.check_out_date) return 'Elige las fechas primero'
  if (loadingRooms.value) return 'Buscando disponibilidad...'
  if (roomOptions.value.length === 0) return 'Sin habitaciones disponibles en ese rango'
  return 'Selecciona una habitación'
})

const nights = computed(() => {
  if (!form.check_in_date || !form.check_out_date) return 0
  const a = new Date(form.check_in_date)
  const b = new Date(form.check_out_date)
  const d = Math.round((b - a) / 86400000)
  return d > 0 ? d : 0
})

const totalPreview = computed(() => {
  const room = roomOptions.value.find((r) => r.id === Number(form.room_id))
  const base = Number(room?.room_type?.base_price ?? 0)
  return nights.value > 0 ? base * nights.value : 0
})

// Al abrir: rellenar (editar) o resetear (crear)
watch(
  () => props.open,
  async (open) => {
    if (!open) return
    if (props.mode === 'editar' && props.booking) {
      Object.assign(form, {
        guest_id: props.booking.guest_id ?? '',
        check_in_date: props.booking.check_in_date ?? '',
        check_out_date: props.booking.check_out_date ?? '',
        room_type_id: '',
        room_id: props.booking.room_id ?? '',
        notes: props.booking.notes ?? '',
      })
      // Buscar disponibilidad para las fechas actuales (la habitación propia se reinyecta)
      await buscar({
        check_in_date: form.check_in_date,
        check_out_date: form.check_out_date,
      })
    } else {
      Object.assign(form, estadoInicial())
      reset()
    }
  },
)

// Al cambiar entrada: si la salida quedó igual o antes, se limpia
watch(
  () => form.check_in_date,
  () => {
    if (form.check_out_date && form.check_out_date <= form.check_in_date) {
      form.check_out_date = ''
    }
  },
)

// Al cambiar fechas o tipo: re-consultar disponibilidad y limpiar selección inválida
watch([() => form.check_in_date, () => form.check_out_date, () => form.room_type_id], async () => {
  await buscar({
    check_in_date: form.check_in_date,
    check_out_date: form.check_out_date,
    room_type_id: form.room_type_id,
  })
  if (form.room_id && !roomOptions.value.some((r) => r.id === Number(form.room_id))) {
    form.room_id = ''
  }
})

function submit() {
  emit('submit', {
    guest_id: form.guest_id,
    room_id: form.room_id,
    check_in_date: form.check_in_date,
    check_out_date: form.check_out_date,
    notes: form.notes,
  })
}
</script>
