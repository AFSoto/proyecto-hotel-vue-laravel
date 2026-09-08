// Módulo de endpoints de Reportes.
// Único lugar con las URLs de este dominio; pasa por la instancia de axios.

import api from '@/lib/axios'

export const reportsApi = {
  // GET /reports/summary — indicadores del dashboard (params opcionales: from, to)
  summary(params = {}) {
    return api.get('/reports/summary', { params })
  },

  // GET /reports/occupancy — tablero de ocupación (params: from, to)
  occupancy(params = {}) {
    return api.get('/reports/occupancy', { params })
  },
}
