// Módulo de endpoints de Tipos de habitación.
// Por ahora solo se usa el listado, para poblar los <select> de la pantalla
// de habitaciones (formulario y filtro por tipo).

import api from '@/lib/axios'

export const roomTypesApi = {
  // GET /room-types — listado (paginado en el backend; pedimos un per_page alto
  // porque son pocos tipos y los queremos todos para el filtro/formulario)
  listar(params = {}) {
    return api.get('/room-types', { params })
  },
}
