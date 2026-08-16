// Módulo de endpoints de Usuarios.
// Único lugar donde viven las URLs de este dominio; todo pasa por la
// instancia de axios (con sus interceptores de token/refresh).

import api from '@/lib/axios'

export const usersApi = {
  // GET /users — listado paginado (filtros: search, role_id, is_active, page, per_page)
  listar(params = {}) {
    return api.get('/users', { params })
  },

  // GET /users/{id}
  obtener(id) {
    return api.get(`/users/${id}`)
  },

  // POST /users — requiere password + password_confirmation
  crear(data) {
    return api.post('/users', data)
  },

  // PUT /users/{id} — solo name, email, role_id (el backend no acepta password/estado aquí)
  actualizar(id, data) {
    return api.put(`/users/${id}`, data)
  },

  // DELETE /users/{id} — desactiva (is_active=false) + soft delete
  eliminar(id) {
    return api.delete(`/users/${id}`)
  },
}
