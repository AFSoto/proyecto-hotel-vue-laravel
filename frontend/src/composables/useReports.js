// Composable del dashboard de reportes.
// Trae el resumen de indicadores para un rango de fechas.

import { reactive, ref } from 'vue'
import { reportsApi } from '@/api/reports'

export function useReports() {
  const summary = ref(null)
  const loading = ref(false)

  // Rango de fechas (vacío = el backend usa el mes actual)
  const filtros = reactive({ from: '', to: '' })

  async function cargar() {
    loading.value = true
    try {
      const params = {}
      if (filtros.from) params.from = filtros.from
      if (filtros.to) params.to = filtros.to

      const { data } = await reportsApi.summary(params)
      summary.value = data.data

      // En la primera carga, sincroniza los inputs con el rango efectivo
      if (!filtros.from) filtros.from = summary.value.range.from
      if (!filtros.to) filtros.to = summary.value.range.to
    } finally {
      loading.value = false
    }
  }

  return { summary, loading, filtros, cargar }
}
