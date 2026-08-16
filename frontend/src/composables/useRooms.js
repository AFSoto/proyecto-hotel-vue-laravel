// Composable de la entidad Habitaciones.
// Concentra el estado de la pantalla (lista, filtros, paginación, loading/saving)
// y las acciones CRUD. La vista solo consume lo que aquí se expone.

import { ref, reactive, watch } from 'vue'
import { roomsApi } from '@/api/rooms'
import { roomTypesApi } from '@/api/roomTypes'

export function useRooms() {
  // ─── Estado ─────────────────────────────────────
  const rooms = ref([])
  const roomTypes = ref([]) // para el filtro por tipo y el <select> del formulario
  const loading = ref(false)
  const saving = ref(false)
  const errors = ref({}) // errores de validación (422) por campo
  const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 })

  // Filtros reactivos de la lista
  const filtros = reactive({
    search: '',
    status: '',
    room_type_id: '',
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
      if (filtros.room_type_id) params.room_type_id = filtros.room_type_id

      const { data } = await roomsApi.listar(params)
      rooms.value = data.data
      meta.value = data.meta
    } finally {
      loading.value = false
    }
  }

  // Carga los tipos para poblar los selects. Si el rol no puede listarlos
  // (p. ej. permisos), degradamos a lista vacía sin romper la pantalla.
  async function cargarTipos() {
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
      await roomsApi.crear(payload)
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
      await roomsApi.actualizar(id, payload)
      await cargar()
    } catch (e) {
      if (e.response?.status === 422) errors.value = e.response.data.errors || {}
      throw e
    } finally {
      saving.value = false
    }
  }

  async function cambiarEstado(id, status) {
    saving.value = true
    try {
      await roomsApi.cambiarEstado(id, status)
      await cargar()
    } finally {
      saving.value = false
    }
  }

  async function eliminar(id) {
    saving.value = true
    try {
      await roomsApi.eliminar(id)
      // Si era el último de la página, retrocede una (el watch dispara cargar)
      if (rooms.value.length === 1 && filtros.page > 1) {
        filtros.page -= 1
      } else {
        await cargar()
      }
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

  // Filtros discretos y tamaño de página: respuesta inmediata.
  watch([() => filtros.status, () => filtros.room_type_id, () => filtros.per_page], () => {
    filtros.page = 1
    cargar()
  })

  // Cambio de página.
  watch(() => filtros.page, cargar)

  return {
    rooms,
    roomTypes,
    loading,
    saving,
    errors,
    meta,
    filtros,
    cargar,
    cargarTipos,
    crear,
    actualizar,
    cambiarEstado,
    eliminar,
    resetErrors,
  }
}
