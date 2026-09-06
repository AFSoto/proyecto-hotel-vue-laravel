<template>
  <div class="space-y-6">
    <!-- Encabezado -->
    <div>
      <h1 class="text-2xl font-bold text-gray-800">Check-in / Check-out</h1>
      <p class="text-sm text-gray-500">Movimientos de recepción pendientes para hoy</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- ── Llegadas ─────────────────────────────── -->
      <section class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <header class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 bg-gray-50">
          <LogIn class="w-4 h-4 text-green-600" />
          <h2 class="font-semibold text-gray-800">Llegadas de hoy</h2>
          <span class="ml-auto text-xs text-gray-400">{{ arrivals.length }}</span>
        </header>

        <div class="divide-y divide-gray-100">
          <p v-if="loading" class="px-5 py-6 text-sm text-gray-400">Cargando...</p>
          <p v-else-if="!arrivals.length" class="px-5 py-6 text-sm text-gray-400">
            No hay llegadas pendientes.
          </p>

          <div v-for="b in arrivals" :key="b.id" class="flex items-center gap-3 px-5 py-3">
            <div class="min-w-0 flex-1">
              <div class="font-medium text-gray-800 truncate">{{ b.guest?.full_name ?? '—' }}</div>
              <div class="text-xs text-gray-500">
                Hab. {{ b.room?.number ?? '—' }} · entrada {{ b.check_in_date }}
              </div>
            </div>
            <AppButton size="sm" :loading="saving" @click="onCheckIn(b)">Check-in</AppButton>
          </div>
        </div>
      </section>

      <!-- ── Salidas ──────────────────────────────── -->
      <section class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <header class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 bg-gray-50">
          <LogOut class="w-4 h-4 text-blue-600" />
          <h2 class="font-semibold text-gray-800">Salidas de hoy</h2>
          <span class="ml-auto text-xs text-gray-400">{{ departures.length }}</span>
        </header>

        <div class="divide-y divide-gray-100">
          <p v-if="loading" class="px-5 py-6 text-sm text-gray-400">Cargando...</p>
          <p v-else-if="!departures.length" class="px-5 py-6 text-sm text-gray-400">
            No hay salidas pendientes.
          </p>

          <div v-for="b in departures" :key="b.id" class="flex items-center gap-3 px-5 py-3">
            <div class="min-w-0 flex-1">
              <div class="font-medium text-gray-800 truncate">{{ b.guest?.full_name ?? '—' }}</div>
              <div class="text-xs text-gray-500">
                Hab. {{ b.room?.number ?? '—' }} · salida {{ b.check_out_date }}
              </div>
            </div>
            <AppButton size="sm" variant="secondary" :loading="saving" @click="onCheckOut(b)">
              Check-out
            </AppButton>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { LogIn, LogOut } from 'lucide-vue-next'

import { useCheckInOut } from '@/composables/useCheckInOut'
import { useToast } from '@/composables/useToast'

import AppButton from '@/components/common/AppButton.vue'

const toast = useToast()

const { arrivals, departures, loading, saving, cargar, hacerCheckIn, hacerCheckOut } = useCheckInOut()

async function onCheckIn(booking) {
  try {
    await hacerCheckIn(booking.id)
    toast.success(`Check-in de ${booking.guest?.full_name ?? 'huésped'} registrado.`)
  } catch (e) {
    toast.error(e.response?.data?.message || 'No se pudo registrar el check-in.')
  }
}

async function onCheckOut(booking) {
  try {
    await hacerCheckOut(booking.id)
    toast.success(`Check-out de ${booking.guest?.full_name ?? 'huésped'} registrado.`)
  } catch (e) {
    toast.error(e.response?.data?.message || 'No se pudo registrar el check-out.')
  }
}

onMounted(() => {
  cargar()
})
</script>
