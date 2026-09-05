// Módulo de endpoints de Huéspedes.
// Único lugar donde viven las URLs de este dominio; todo pasa por la
// instancia de axios (con sus interceptores de token/refresh).

import api from '@/lib/axios'

export const guestsApi = {
  // GET /guests — listado paginado (filtros: search, page, per_page)
  listar(params = {}) {
    return api.get('/guests', { params })
  },

  // GET /guests/{id}
  obtener(id) {
    return api.get(`/guests/${id}`)
  },

  // POST /guests — si el documento pertenece a uno eliminado, el backend lo restaura
  crear(data) {
    return api.post('/guests', data)
  },

  // PUT /guests/{id}
  actualizar(id, data) {
    return api.put(`/guests/${id}`, data)
  },

  // DELETE /guests/{id} — soft delete (solo admin; 409 si tiene reservas activas)
  eliminar(id) {
    return api.delete(`/guests/${id}`)
  },
}
