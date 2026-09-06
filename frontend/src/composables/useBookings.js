// Composable de la entidad Reservas.
// Concentra el estado de la pantalla (lista, filtros, paginación, loading/saving)
// y las acciones. La vista solo consume lo que aquí se expone.

import { ref, reactive, watch } from 'vue'
import { bookingsApi } from '@/api/bookings'
import { guestsApi } from '@/api/guests'
import { roomTypesApi } from '@/api/roomTypes'

export function useBookings() {
  // ─── Estado ─────────────────────────────────────
  const bookings = ref([])
  const guests = ref([]) // para el <select> de huésped del formulario
  const roomTypes = ref([]) // para el filtro de tipo dentro del formulario
  const loading = ref(false)
  const saving = ref(false)
  const errors = ref({}) // errores de validación (422) por campo
  const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 })

  // Filtros reactivos de la lista
  const filtros = reactive({
    search: '', // por huésped (nombre o documento)
    status: '',
    from: '', // rango de fechas: el backend lo aplica solo si vienen ambos
    to: '',
    per_page: 15,
    page: 1,
  })

  let debounceTimer = null

  // ─── Acciones ───────────────────────────────────

  // Carga la lista respetando la envoltura del backend: { data:[...], meta:{...} }
  async function cargar() {
    loading.value = true
    try {
      const params = { per_page: filtros.per_page, page: filtros.page }
      if (filtros.search) params.search = filtros.search
      if (filtros.status) params.status = filtros.status
      // El rango solo tiene sentido con ambos extremos (así lo filtra el backend)
      if (filtros.from && filtros.to) {
        params.from = filtros.from
        params.to = filtros.to
      }

      const { data } = await bookingsApi.listar(params)
      bookings.value = data.data
      meta.value = data.meta
    } finally {
      loading.value = false
    }
  }

  // Catálogos para los selects del formulario. Degradan a lista vacía sin romper.
  async function cargarGuests() {
    try {
      const { data } = await guestsApi.listar({ per_page: 100 })
      guests.value = data.data
    } catch {
      guests.value = []
    }
  }

  async function cargarRoomTypes() {
    try {
      const { data } = await roomTypesApi.listar({ per_page: 100 })
      roomTypes.value = data.data
    } catch {
      roomTypes.value = []
    }
  }

  async function crear(payload) {
    saving.value = true
    errors.value = {}
    try {
      await bookingsApi.crear(payload)
      filtros.page = 1
      await cargar()
    } catch (e) {
      if (e.response?.status === 422) errors.value = e.response.data.errors || {}
      throw e
    } finally {
      saving.value = false
    }
  }

  async function actualizar(id, payload) {
    saving.value = true
    errors.value = {}
    try {
      await bookingsApi.actualizar(id, payload)
      await cargar()
    } catch (e) {
      if (e.response?.status === 422) errors.value = e.response.data.errors || {}
      throw e
    } finally {
      saving.value = false
    }
  }

  // Cancelar (cambio de estado). El 409 se propaga para que la vista lo muestre.
  async function cancelar(id) {
    saving.value = true
    try {
      await bookingsApi.cancelar(id)
      await cargar()
    } finally {
      saving.value = false
    }
  }

  function resetErrors() {
    errors.value = {}
  }

  // ─── Reactividad de filtros ─────────────────────

  // Búsqueda con debounce: no golpea la API en cada tecla.
  watch(
    () => filtros.search,
    () => {
      clearTimeout(debounceTimer)
      debounceTimer = setTimeout(() => {
        filtros.page = 1
        cargar()
      }, 400)
    },
  )

  // Filtros discretos, rango y tamaño de página: respuesta inmediata.
  watch(
    [() => filtros.status, () => filtros.from, () => filtros.to, () => filtros.per_page],
    () => {
      filtros.page = 1
      cargar()
    },
  )

  // Cambio de página.
  watch(() => filtros.page, cargar)

  return {
    bookings,
    guests,
    roomTypes,
    loading,
    saving,
    errors,
    meta,
    filtros,
    cargar,
    cargarGuests,
    cargarRoomTypes,
    crear,
    actualizar,
    cancelar,
    resetErrors,
  }
}
