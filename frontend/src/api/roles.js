// Módulo de endpoints de Roles (catálogo de solo lectura).

import api from '@/lib/axios'

export const rolesApi = {
  // GET /roles — lista de roles (admin, receptionist)
  listar() {
    return api.get('/roles')
  },
}
