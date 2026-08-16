// Composable de la entidad Usuarios.
// Concentra el estado de la pantalla (lista, filtros, paginación, loading/saving)
// y las acciones CRUD. La vista solo consume lo que aquí se expone.

import { ref, reactive, watch } from 'vue'
import { usersApi } from '@/api/users'
import { rolesApi } from '@/api/roles'

export function useUsers() {
  // ─── Estado ─────────────────────────────────────
  const users = ref([])
  const roles = ref([]) // para el filtro y el <select> del formulario
  const loading = ref(false)
  const saving = ref(false)
  const errors = ref({}) // errores de validación (422) por campo
  const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 })

  // Filtros reactivos de la lista
  const filtros = reactive({
    search: '',
    role_id: '',
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
      if (filtros.role_id) params.role_id = filtros.role_id

      const { data } = await usersApi.listar(params)
      users.value = data.data
      meta.value = data.meta
    } finally {
      loading.value = false
    }
  }

  // Carga los roles para poblar filtro y selector.
  async function cargarRoles() {
    try {
      const { data } = await rolesApi.listar()
      roles.value = data.data
    } catch {
      roles.value = []
    }
  }

  async function crear(payload) {
    saving.value = true
    errors.value = {}
    try {
      await usersApi.crear(payload)
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
      await usersApi.actualizar(id, payload)
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
      await usersApi.eliminar(id)
      // Si era el último de la página, retrocede una (el watch dispara cargar)
      if (users.value.length === 1 && filtros.page > 1) {
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

  // Filtro por rol y tamaño de página: respuesta inmediata.
  watch([() => filtros.role_id, () => filtros.per_page], () => {
    filtros.page = 1
    cargar()
  })

  // Cambio de página.
  watch(() => filtros.page, cargar)

  return {
    users,
    roles,
    loading,
    saving,
    errors,
    meta,
    filtros,
    cargar,
    cargarRoles,
    crear,
    actualizar,
    eliminar,
    resetErrors,
  }
}
