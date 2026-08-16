// Composable de la entidad Tipos de habitación.
// Concentra el estado de la pantalla (lista, filtro, paginación, loading/saving)
// y las acciones CRUD. La vista solo consume lo que aquí se expone.

import { ref, reactive, watch } from 'vue'
import { roomTypesApi } from '@/api/roomTypes'

export function useRoomTypes() {
  // ─── Estado ─────────────────────────────────────
  const roomTypes = ref([])
  const loading = ref(false)
  const saving = ref(false)
  const errors = ref({}) // errores de validación (422) por campo
  const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 })

  // Filtros reactivos de la lista
  const filtros = reactive({
    search: '',
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

      const { data } = await roomTypesApi.listar(params)
      roomTypes.value = data.data
      meta.value = data.meta
    } finally {
      loading.value = false
    }
  }

  async function crear(payload) {
    saving.value = true
    errors.value = {}
    try {
      await roomTypesApi.crear(payload)
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
      await roomTypesApi.actualizar(id, payload)
      await cargar()
    } catch (e) {
      if (e.response?.status === 422) errors.value = e.response.data.errors || {}
      throw e
    } finally {
      saving.value = false
    }
  }

  async function eliminar(id) {
    saving.value = true
    try {
      await roomTypesApi.eliminar(id)
      // Si era el último de la página, retrocede una (el watch dispara cargar)
      if (roomTypes.value.length === 1 && filtros.page > 1) {
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

  // Tamaño de página: respuesta inmediata.
  watch(
    () => filtros.per_page,
    () => {
      filtros.page = 1
      cargar()
    },
  )

  // Cambio de página.
  watch(() => filtros.page, cargar)

  return {
    roomTypes,
    loading,
    saving,
    errors,
    meta,
    filtros,
    cargar,
    crear,
    actualizar,
    eliminar,
    resetErrors,
  }
}
