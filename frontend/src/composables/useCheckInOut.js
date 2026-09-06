// Composable del panel de Check-in / Check-out.
// Trae las llegadas y salidas pendientes "de hoy" y ejecuta los movimientos.

import { ref } from 'vue'
import { bookingsApi } from '@/api/bookings'

export function useCheckInOut() {
  const arrivals = ref([]) // confirmadas cuya entrada ya llegó (check_in_date <= hoy)
  const departures = ref([]) // con check-in hecho cuya salida ya llegó (check_out_date <= hoy)
  const loading = ref(false)
  const saving = ref(false)

  // Fecha de hoy (local) en formato YYYY-MM-DD
  function hoy() {
    const d = new Date()
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
  }

  // Orden por fecha ascendente (lo más antiguo/urgente primero)
  const porFecha = (campo) => (a, b) => (a[campo] < b[campo] ? -1 : a[campo] > b[campo] ? 1 : 0)

  async function cargar() {
    loading.value = true
    try {
      const today = hoy()
      const [a, d] = await Promise.all([
        bookingsApi.listar({ status: 'confirmed', check_in_to: today, per_page: 100 }),
        bookingsApi.listar({ status: 'checked_in', check_out_to: today, per_page: 100 }),
      ])
      arrivals.value = [...a.data.data].sort(porFecha('check_in_date'))
      departures.value = [...d.data.data].sort(porFecha('check_out_date'))
    } finally {
      loading.value = false
    }
  }

  // Los 409 se propagan para que la vista los muestre como toast.
  async function hacerCheckIn(id) {
    saving.value = true
    try {
      await bookingsApi.checkIn(id)
      await cargar()
    } finally {
      saving.value = false
    }
  }

  async function hacerCheckOut(id) {
    saving.value = true
    try {
      await bookingsApi.checkOut(id)
      await cargar()
    } finally {
      saving.value = false
    }
  }

  return { arrivals, departures, loading, saving, cargar, hacerCheckIn, hacerCheckOut }
}
