<template>
  <div class="space-y-6">
    <!-- Encabezado + navegación -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Calendario de ocupación</h1>
        <p class="text-sm text-gray-500">{{ rango.from }} → {{ rango.to }}</p>
      </div>

      <div class="flex items-center gap-2">
        <AppButton size="sm" variant="ghost" @click="anterior">
          <ChevronLeft class="w-4 h-4" />
        </AppButton>
        <AppButton size="sm" variant="secondary" @click="hoy">Hoy</AppButton>
        <AppButton size="sm" variant="ghost" @click="siguiente">
          <ChevronRight class="w-4 h-4" />
        </AppButton>
      </div>
    </div>

    <!-- Leyenda -->
    <div class="flex flex-wrap gap-4 text-xs text-gray-500">
      <span class="flex items-center gap-1"><i class="w-3 h-3 rounded bg-blue-500" /> Confirmada</span>
      <span class="flex items-center gap-1"><i class="w-3 h-3 rounded bg-purple-500" /> Con check-in</span>
      <span class="flex items-center gap-1"><i class="w-3 h-3 rounded bg-gray-400" /> Finalizada</span>
    </div>

    <p v-if="loading && !rooms.length" class="text-sm text-gray-400">Cargando tablero...</p>

    <!-- Tablero -->
    <div v-else class="overflow-x-auto border border-gray-200 rounded-xl bg-white">
      <div class="min-w-max">
        <!-- Cabecera de días -->
        <div class="flex border-b border-gray-200 bg-gray-50">
          <div class="w-44 shrink-0 sticky left-0 z-20 bg-gray-50 px-4 py-2 text-xs font-semibold text-gray-500">
            Habitación
          </div>
          <div class="grid flex-1" :style="trackStyle">
            <div
              v-for="d in dias"
              :key="'h' + d"
              class="px-1 py-2 text-center border-l border-gray-100"
              :class="d === hoyStr ? 'bg-blue-50' : esFinde(d) ? 'bg-gray-100/60' : ''"
            >
              <div class="text-[11px] uppercase text-gray-400">{{ diaSemana(d) }}</div>
              <div class="text-sm font-medium text-gray-700">{{ diaNumero(d) }}</div>
            </div>
          </div>
        </div>

        <!-- Filas por habitación -->
        <div
          v-for="fila in filas"
          :key="fila.room.id"
          class="flex border-b border-gray-100 last:border-b-0"
        >
          <!-- Etiqueta (columna fija) -->
          <div class="w-44 shrink-0 sticky left-0 z-10 bg-white px-4 py-2 border-r border-gray-100">
            <div class="text-sm font-semibold text-gray-800">Hab. {{ fila.room.number }}</div>
            <div class="text-xs text-gray-400">
              {{ fila.room.room_type?.name ?? '—' }}
              <span v-if="fila.room.status === 'maintenance'" class="text-amber-600">· mant.</span>
            </div>
          </div>

          <!-- Pista de días con las barras -->
          <div class="grid flex-1 relative" :style="trackStyle">
            <!-- Celdas base (líneas / hoy / finde) -->
            <div
              v-for="(d, i) in dias"
              :key="'c' + fila.room.id + d"
              class="h-11 border-l border-gray-100"
              :class="d === hoyStr ? 'bg-blue-50/50' : esFinde(d) ? 'bg-gray-50' : ''"
              :style="{ gridColumn: `${i + 1} / span 1`, gridRow: 1 }"
            />
            <!-- Barras de reservas -->
            <div
              v-for="seg in fila.segmentos"
              :key="seg.id"
              class="h-7 self-center mx-0.5 rounded px-2 text-xs text-white truncate flex items-center shadow-sm cursor-default"
              :class="seg.color"
              :style="{ gridColumn: `${seg.start} / span ${seg.span}`, gridRow: 1, zIndex: 10 }"
              :title="seg.title"
            >
              {{ seg.label }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

import { useOccupancy } from '@/composables/useOccupancy'
import AppButton from '@/components/common/AppButton.vue'

const { rooms, bookings, dias, rango, loading, cargar, anterior, siguiente, hoy } = useOccupancy()

const SEMANA = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb']
const COLORS = {
  confirmed: 'bg-blue-500',
  checked_in: 'bg-purple-500',
  checked_out: 'bg-gray-400',
}

const hoyStr = (() => {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
})()

const trackStyle = computed(() => ({
  gridTemplateColumns: `repeat(${dias.value.length}, minmax(46px, 1fr))`,
  alignItems: 'center',
}))

// ── Helpers de fecha ─────────────────────────────
const parse = (s) => new Date(s + 'T00:00:00')
const diff = (a, b) => Math.round((parse(b) - parse(a)) / 86400000)
const addDays = (s, n) => {
  const d = parse(s)
  d.setDate(d.getDate() + n)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}
const diaSemana = (s) => SEMANA[parse(s).getDay()]
const diaNumero = (s) => parse(s).getDate()
const esFinde = (s) => [0, 6].includes(parse(s).getDay())

// ── Filas con segmentos posicionados ─────────────
const filas = computed(() => {
  const from = rango.from
  const finExclusivo = addDays(dias.value[dias.value.length - 1], 1) // día después del último visible

  return rooms.value.map((room) => {
    const segmentos = bookings.value
      .filter((b) => b.room_id === room.id)
      .map((b) => {
        // recorta la reserva [entrada, salida) a la ventana visible
        const visIni = b.check_in_date > from ? b.check_in_date : from
        const visFin = b.check_out_date < finExclusivo ? b.check_out_date : finExclusivo
        const start = diff(from, visIni) + 1
        const span = diff(visIni, visFin)
        return {
          id: b.id,
          start,
          span,
          color: COLORS[b.status] ?? 'bg-gray-400',
          label: b.guest_name ?? 'Reserva',
          title: `${b.guest_name ?? 'Reserva'} · ${b.check_in_date} → ${b.check_out_date}`,
        }
      })
      .filter((s) => s.span > 0)

    return { room, segmentos }
  })
})

onMounted(cargar)
</script>
