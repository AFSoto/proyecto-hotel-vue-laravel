<template>
  <div class="space-y-6">
    <!-- Encabezado + rango de fechas -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
        <p class="text-sm text-gray-500">Indicadores del hotel</p>
      </div>

      <div class="flex items-end gap-2">
        <div>
          <label class="block text-xs text-gray-500 mb-1">Desde</label>
          <input v-model="filtros.from" type="date" :class="selectClass" @change="cargar" />
        </div>
        <span class="pb-2 text-gray-400">→</span>
        <div>
          <label class="block text-xs text-gray-500 mb-1">Hasta</label>
          <input v-model="filtros.to" type="date" :class="selectClass" @change="cargar" />
        </div>
      </div>
    </div>

    <p v-if="loading && !summary" class="text-sm text-gray-400">Cargando indicadores...</p>

    <template v-if="summary">
      <!-- KPIs -->
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <StatCard
          label="Ingresos"
          :value="formatMoney(summary.revenue)"
          :icon="DollarSign"
          color-bg="bg-green-50"
          color-text="text-green-600"
        />
        <StatCard
          label="Noches vendidas"
          :value="summary.nights_sold"
          :icon="Moon"
          color-bg="bg-indigo-50"
          color-text="text-indigo-600"
        />
        <StatCard
          label="Reservas"
          :value="summary.bookings.total"
          :icon="CalendarCheck"
          color-bg="bg-blue-50"
          color-text="text-blue-600"
        />
        <StatCard
          label="Llegadas hoy"
          :value="summary.today.arrivals"
          :icon="LogIn"
          color-bg="bg-emerald-50"
          color-text="text-emerald-600"
        />
        <StatCard
          label="Salidas hoy"
          :value="summary.today.departures"
          :icon="LogOut"
          color-bg="bg-amber-50"
          color-text="text-amber-600"
        />
      </div>

      <!-- Gráficos -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h2 class="font-semibold text-gray-800 mb-1">Reservas por estado</h2>
          <p class="text-xs text-gray-400 mb-4">
            Rango {{ summary.range.from }} → {{ summary.range.to }}
          </p>
          <AppChart :data="bookingsChart" />
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h2 class="font-semibold text-gray-800 mb-1">Habitaciones por estado</h2>
          <p class="text-xs text-gray-400 mb-4">Total: {{ summary.rooms.total }}</p>
          <AppChart :data="roomsChart" />
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { DollarSign, Moon, CalendarCheck, LogIn, LogOut } from 'lucide-vue-next'

import { useReports } from '@/composables/useReports'
import { BOOKING_STATUS_LABELS } from '@/constants/bookings'

import StatCard from '@/components/dashboard/StatCard.vue'
import AppChart from '@/components/dashboard/AppChart.vue'

const { summary, loading, filtros, cargar } = useReports()

const selectClass =
  'rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none transition-colors bg-white focus:border-[#1A2B4A] focus:ring-2 focus:ring-blue-100'

const formatMoney = (v) =>
  `$ ${Number(v ?? 0).toLocaleString('es-CO', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`

// Colores alineados con AppBadge
const BOOKING_COLORS = {
  confirmed: '#3b82f6',
  checked_in: '#a855f7',
  checked_out: '#9ca3af',
  cancelled: '#d1d5db',
}

const bookingsChart = computed(() => {
  const bs = summary.value.bookings.by_status
  const keys = ['confirmed', 'checked_in', 'checked_out', 'cancelled']
  return {
    labels: keys.map((k) => BOOKING_STATUS_LABELS[k]),
    datasets: [
      {
        data: keys.map((k) => bs[k] ?? 0),
        backgroundColor: keys.map((k) => BOOKING_COLORS[k]),
        borderWidth: 0,
      },
    ],
  }
})

const roomsChart = computed(() => {
  const r = summary.value.rooms
  return {
    labels: ['Disponibles', 'Ocupadas', 'Mantenimiento'],
    datasets: [
      {
        data: [r.available, r.occupied, r.maintenance],
        backgroundColor: ['#22c55e', '#ef4444', '#f59e0b'],
        borderWidth: 0,
      },
    ],
  }
})

onMounted(cargar)
</script>
