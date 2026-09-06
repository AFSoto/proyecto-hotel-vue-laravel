// Módulo de endpoints de Reservas.
// Único lugar donde viven las URLs de este dominio; todo pasa por la
// instancia de axios (con sus interceptores de token/refresh).

import api from '@/lib/axios'

export const bookingsApi = {
  // GET /bookings — listado paginado (filtros: status, room_id, guest_id, search, from, to, page, per_page)
  listar(params = {}) {
    return api.get('/bookings', { params })
  },

  // GET /bookings/{id}
  obtener(id) {
    return api.get(`/bookings/${id}`)
  },

  // POST /bookings — crea la reserva (el total lo congela el backend)
  crear(data) {
    return api.post('/bookings', data)
  },

  // PUT /bookings/{id} — solo editable si está confirmada
  actualizar(id, data) {
    return api.put(`/bookings/${id}`, data)
  },

  // PATCH /bookings/{id}/cancel — cancelar (solo desde confirmada; 409 si no)
  cancelar(id) {
    return api.patch(`/bookings/${id}/cancel`)
  },

  // PATCH /bookings/{id}/check-in — registrar entrada (confirmada → checked_in)
  checkIn(id) {
    return api.patch(`/bookings/${id}/check-in`)
  },

  // PATCH /bookings/{id}/check-out — registrar salida (checked_in → checked_out)
  checkOut(id) {
    return api.patch(`/bookings/${id}/check-out`)
  },
}
