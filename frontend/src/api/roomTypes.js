// Módulo de endpoints de Tipos de habitación.
// Único lugar donde viven las URLs de este dominio; todo pasa por la
// instancia de axios (con sus interceptores de token/refresh).

import api from '@/lib/axios'

export const roomTypesApi = {
  // GET /room-types — listado paginado (acepta filtro: search, page, per_page).
  // Se pide un per_page alto cuando se usa solo para poblar selects.
  listar(params = {}) {
    return api.get('/room-types', { params })
  },

  // GET /room-types/{id}
  obtener(id) {
    return api.get(`/room-types/${id}`)
  },

  // POST /room-types
  crear(data) {
    return api.post('/room-types', data)
  },

  // PUT /room-types/{id}
  actualizar(id, data) {
    return api.put(`/room-types/${id}`, data)
  },

  // DELETE /room-types/{id} — el backend responde 409 si el tipo tiene habitaciones
  eliminar(id) {
    return api.delete(`/room-types/${id}`)
  },
}
