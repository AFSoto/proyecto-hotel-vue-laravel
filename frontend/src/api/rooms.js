// Módulo de endpoints de Habitaciones.
// Único lugar donde viven las URLs de este dominio; todo pasa por la
// instancia de axios (con sus interceptores de token/refresh).

import api from '@/lib/axios'

export const roomsApi = {
  // GET /rooms — listado paginado (acepta filtros: search, status, room_type_id, page, per_page)
  listar(params = {}) {
    return api.get('/rooms', { params })
  },

  // GET /rooms/available — habitaciones libres en un rango (params: check_in_date, check_out_date, room_type_id?)
  disponibles(params = {}) {
    return api.get('/rooms/available', { params })
  },

  // GET /rooms/{id}
  obtener(id) {
    return api.get(`/rooms/${id}`)
  },

  // POST /rooms
  crear(data) {
    return api.post('/rooms', data)
  },

  // PUT /rooms/{id}
  actualizar(id, data) {
    return api.put(`/rooms/${id}`, data)
  },

  // PATCH /rooms/{id}/status — cambio de estado (recepción)
  cambiarEstado(id, status) {
    return api.patch(`/rooms/${id}/status`, { status })
  },

  // DELETE /rooms/{id} — soft delete
  eliminar(id) {
    return api.delete(`/rooms/${id}`)
  },
}
