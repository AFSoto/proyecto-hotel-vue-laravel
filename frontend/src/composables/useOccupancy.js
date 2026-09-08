// Composable del tablero de ocupación (calendario).
// Maneja la ventana de fechas, la lista de días y la navegación temporal.

import { computed, reactive, ref } from 'vue'
import { reportsApi } from '@/api/reports'

const DIAS = 14

// Fecha (Date) → 'YYYY-MM-DD' local
function toStr(d) {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

// 'YYYY-MM-DD' + n días → 'YYYY-MM-DD'
function addDays(str, n) {
  const d = new Date(str + 'T00:00:00')
  d.setDate(d.getDate() + n)
  return toStr(d)
}

export function useOccupancy() {
  const rooms = ref([])
  const bookings = ref([])
  const loading = ref(false)

  // Ventana [from, to] de DIAS días, empezando hoy
  const rango = reactive({ from: toStr(new Date()), to: '' })
  rango.to = addDays(rango.from, DIAS - 1)

  // Lista de días visibles
  const dias = computed(() => Array.from({ length: DIAS }, (_, i) => addDays(rango.from, i)))

  async function cargar() {
    loading.value = true
    try {
      const { data } = await reportsApi.occupancy({ from: rango.from, to: rango.to })
      rooms.value = data.data.rooms
      bookings.value = data.data.bookings
    } finally {
      loading.value = false
    }
  }

  function moverA(fromStr) {
    rango.from = fromStr
    rango.to = addDays(fromStr, DIAS - 1)
    cargar()
  }

  const anterior = () => moverA(addDays(rango.from, -DIAS))
  const siguiente = () => moverA(addDays(rango.from, DIAS))
  const hoy = () => moverA(toStr(new Date()))

  return { rooms, bookings, dias, rango, loading, cargar, anterior, siguiente, hoy }
}
