<template>
  <!-- Sidebar principal -->
  <aside
    :class="[
      // Clases base
      'fixed inset-y-0 left-0 z-40 bg-[#1A2B4A] flex flex-col transition-all duration-300',

      // Comportamiento en mobile
      mobileOpen ? 'translate-x-0' : '-translate-x-full',

      // En desktop siempre visible
      'lg:translate-x-0',

      // Ancho según estado colapsado
      collapsed ? 'lg:w-[72px]' : 'lg:w-60'
    ]"
    :style="{ width: mobileOpen ? '240px' : undefined }"
  >
    <!-- Header del sidebar (logo + toggle) -->
    <div
      class="h-16 flex items-center border-b border-white/10"
      :class="collapsed ? 'justify-center px-2' : 'justify-between px-5'"
    >
      <!-- Logo -->
      <router-link to="/" class="flex items-center gap-3 min-w-0">
        <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center flex-shrink-0">
          <Building2 class="w-4 h-4 text-white" />
        </div>

        <!-- Texto del logo (solo si no está colapsado) -->
        <Transition
          enter-active-class="transition duration-200"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="transition duration-150"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <span v-if="!collapsed" class="text-lg font-bold text-white whitespace-nowrap">
            HotelOS
          </span>
        </Transition>
      </router-link>

      <!-- Botón para colapsar (solo desktop) -->
      <button
        @click="$emit('toggle-collapse')"
        class="hidden lg:flex w-7 h-7 items-center justify-center rounded-md text-blue-200 hover:bg-white/10 hover:text-white transition-colors cursor-pointer"
        :class="collapsed ? 'absolute -right-3 top-5 bg-[#1A2B4A] border border-white/20 rounded-full w-6 h-6 shadow-md' : ''"
      >
        <PanelLeftClose v-if="!collapsed" class="w-4 h-4" />
        <PanelLeftOpen v-else class="w-3.5 h-3.5" />
      </button>
    </div>

    <!-- Navegación -->
    <nav
      class="flex-1 py-4 overflow-y-auto"
      :class="collapsed ? 'px-2' : 'px-3'"
    >
      <template v-for="(group, index) in filteredMenuGroups" :key="index">

        <!-- Label del grupo -->
        <Transition
          enter-active-class="transition duration-200"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="transition duration-150"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <p
            v-if="group.label && !collapsed"
            class="text-xs font-semibold text-blue-300/50 uppercase tracking-wider px-3 mt-4 mb-2"
          >
            {{ group.label }}
          </p>
        </Transition>

        <!-- Separador cuando está colapsado -->
        <div
          v-if="group.label && collapsed"
          class="my-3 mx-2 border-t border-white/10"
        />

        <!-- Items del menú -->
        <div
          class="relative group"
          v-for="item in group.items"
          :key="item.to"
        >
          <router-link
            :to="item.to"
            @click="$emit('close-mobile')"
            :class="[
              'flex items-center rounded-lg text-sm font-medium transition-colors',
              collapsed ? 'justify-center px-2 py-2.5' : 'gap-3 px-3 py-2.5',
              isActive(item.to)
                ? 'bg-white/15 text-white'
                : 'text-blue-200 hover:bg-white/10 hover:text-white'
            ]"
          >
            <!-- Icono -->
            <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />

            <!-- Texto (solo si no está colapsado) -->
            <Transition
              enter-active-class="transition duration-200"
              enter-from-class="opacity-0"
              enter-to-class="opacity-100"
              leave-active-class="transition duration-150"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0"
            >
              <span v-if="!collapsed" class="whitespace-nowrap">
                {{ item.label }}
              </span>
            </Transition>
          </router-link>

          <!-- Tooltip cuando está colapsado -->
          <div
            v-if="collapsed"
            class="absolute left-full top-1/2 -translate-y-1/2 ml-2 px-2.5 py-1.5 bg-gray-900 text-white text-xs font-medium rounded-md whitespace-nowrap opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-150 z-50 pointer-events-none"
          >
            {{ item.label }}
            <div class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-gray-900" />
          </div>
        </div>
      </template>
    </nav>

    <!-- Sección usuario -->
    <div
      class="border-t border-white/10 p-4"
      :class="collapsed ? 'px-2' : ''"
    >
      <!-- Usuario expandido -->
      <div v-if="!collapsed" class="flex items-center gap-3 mb-3">
        <div class="w-9 h-9 bg-white/10 rounded-full flex items-center justify-center flex-shrink-0">
          <span class="text-sm font-semibold text-white">
            {{ userInitials }}
          </span>
        </div>

        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-white truncate">
            {{ authStore.user?.name }}
          </p>
          <p class="text-xs text-blue-300/60 truncate">
            {{ authStore.user?.email }}
          </p>
        </div>
      </div>

      <!-- Botón logout -->
      <div class="relative group">
        <button
          @click="handleLogout"
          :class="[
            'flex items-center w-full text-sm text-blue-200 hover:bg-white/10 hover:text-white rounded-lg transition-colors cursor-pointer',
            collapsed ? 'justify-center px-2 py-2.5' : 'gap-2 px-3 py-2'
          ]"
        >
          <LogOut class="w-4 h-4 flex-shrink-0" />
          <span v-if="!collapsed">Cerrar sesión</span>
        </button>

        <!-- Tooltip logout -->
        <div
          v-if="collapsed"
          class="absolute left-full top-1/2 -translate-y-1/2 ml-2 px-2.5 py-1.5 bg-gray-900 text-white text-xs font-medium rounded-md whitespace-nowrap opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-150 z-50 pointer-events-none"
        >
          Cerrar sesión
          <div class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-gray-900" />
        </div>
      </div>

      <!-- Avatar en modo colapsado -->
      <div v-if="collapsed" class="relative group mt-2">
        <div class="w-9 h-9 bg-white/10 rounded-full flex items-center justify-center mx-auto">
          <span class="text-sm font-semibold text-white">
            {{ userInitials }}
          </span>
        </div>

        <div
          class="absolute left-full top-1/2 -translate-y-1/2 ml-2 px-2.5 py-1.5 bg-gray-900 text-white text-xs font-medium rounded-md whitespace-nowrap opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-150 z-50 pointer-events-none"
        >
          {{ authStore.user?.name }}
          <div class="absolute right-full top-1/2 -translate-y-1/2 border-4 border-transparent border-r-gray-900" />
        </div>
      </div>
    </div>
  </aside>

  <!-- Overlay en mobile -->
  <Transition
    enter-active-class="transition duration-200"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-200"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="mobileOpen"
      class="fixed inset-0 bg-black/50 z-30 lg:hidden"
      @click="$emit('close-mobile')"
    />
  </Transition>
</template>

<script setup>
// Importa utilidades reactivas
import { computed } from 'vue'

// Permite acceder a la ruta actual
import { useRoute } from 'vue-router'

// Store de autenticación
import { useAuthStore } from '@/stores/auth'

// Iconos
import {
  Building2,
  LogOut,
  LayoutDashboard,
  BedDouble,
  Layers,
  CalendarCheck,
  CalendarDays,
  DoorOpen,
  Users,
  UserRound,
  History,
  PanelLeftClose,
  PanelLeftOpen
} from 'lucide-vue-next'

/**
 * Props del componente
 */
defineProps({
  collapsed: {
    type: Boolean,
    default: false
  },
  mobileOpen: {
    type: Boolean,
    default: false
  }
})

/**
 * Eventos emitidos
 */
defineEmits(['toggle-collapse', 'close-mobile'])

// Ruta actual
const route = useRoute()

// Store
const authStore = useAuthStore()

/**
 * Iniciales del usuario
 */
const userInitials = computed(() => {
  const name = authStore.user?.name || ''

  return name
    .split(' ')
    .map(w => w[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

/**
 * Verifica si una ruta está activa
 */
function isActive(path) {
  return route.path === path
}

/**
 * Logout
 */
async function handleLogout() {
  await authStore.logout()
}

/**
 * Definición del menú con roles
 */
const menuGroups = [
  {
    label: null,
    items: [
      { to: '/app/dashboard', label: 'Dashboard', icon: LayoutDashboard, roles: ['admin', 'receptionist'] }
    ]
  },
  {
    label: 'Operaciones',
    items: [
      { to: '/app/rooms', label: 'Habitaciones', icon: BedDouble, roles: ['admin', 'receptionist'] },
      { to: '/app/bookings', label: 'Reservas', icon: CalendarCheck, roles: ['admin', 'receptionist'] },
      { to: '/app/calendar', label: 'Calendario', icon: CalendarDays, roles: ['admin', 'receptionist'] },
      { to: '/app/guests', label: 'Huéspedes', icon: UserRound, roles: ['admin', 'receptionist'] },
      { to: '/app/check-in-out', label: 'Check-in / out', icon: DoorOpen, roles: ['admin', 'receptionist'] }
    ]
  },
  {
    label: 'Administración',
    items: [
      { to: '/app/room-types', label: 'Tipos de habitación', icon: Layers, roles: ['admin'] },
      { to: '/app/users', label: 'Usuarios', icon: Users, roles: ['admin'] },
      { to: '/app/stays', label: 'Historial de estancias', icon: History, roles: ['admin'] }
    ]
  }
]

/**
 * Filtra el menú según el rol del usuario
 */
const filteredMenuGroups = computed(() => {
  const role = authStore.userRole

  return menuGroups
    .map(group => ({
      ...group,
      items: group.items.filter(item => item.roles.includes(role))
    }))
    .filter(group => group.items.length > 0)
})
</script>
