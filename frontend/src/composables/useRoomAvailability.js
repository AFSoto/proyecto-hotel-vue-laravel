// Composable para consultar habitaciones disponibles en un rango de fechas.
// Lo usa el formulario de reserva: al elegir fechas (y opcionalmente tipo)
// pide al backend las habitaciones libres, sin tocar axios directamente.

import { ref } from 'vue'
import { roomsApi } from '@/api/rooms'

export function useRoomAvailability() {
  const availableRooms = ref([])
  const loadingRooms = ref(false)

  // Busca disponibilidad. Sin ambas fechas no hay nada que consultar.
  async function buscar({ check_in_date, check_out_date, room_type_id } = {}) {
    if (!check_in_date || !check_out_date) {
      availableRooms.value = []
      return
    }

    loadingRooms.value = true
    try {
      const params = { check_in_date, check_out_date }
      if (room_type_id) params.room_type_id = room_type_id

      const { data } = await roomsApi.disponibles(params)
      availableRooms.value = data.data
    } catch {
      // Fechas inválidas u otro error → sin opciones, sin romper el formulario
      availableRooms.value = []
    } finally {
      loadingRooms.value = false
    }
  }

  function reset() {
    availableRooms.value = []
    loadingRooms.value = false
  }

  return { availableRooms, loadingRooms, buscar, reset }
}
