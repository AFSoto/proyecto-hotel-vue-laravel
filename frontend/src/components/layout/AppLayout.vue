<template>
  <!-- Layout principal de la aplicación -->
  <div class="min-h-screen bg-gray-50">
    <!-- Sidebar con soporte para colapsado (desktop) y apertura en mobile -->
    <AppSidebar
      :collapsed="sidebarCollapsed"
      :mobile-open="mobileOpen"
      @toggle-collapse="sidebarCollapsed = !sidebarCollapsed"
      @close-mobile="mobileOpen = false"
    />

    <!-- Contenedor del contenido principal -->
    <div
      class="transition-all duration-300"
      :class="sidebarCollapsed ? 'lg:pl-[72px]' : 'lg:pl-60'"
    >
      <!-- Header superior -->
      <AppHeader @toggle-sidebar="mobileOpen = !mobileOpen" />

      <!-- Área donde se renderizan las vistas según la ruta -->
      <main class="p-6">
        <router-view />
      </main>
    </div>

    <!-- Contenedor global de notificaciones (toasts) -->
    <AppToast />
  </div>
</template>

<script setup>
// Importa ref para manejar estado reactivo
import { ref } from 'vue'

// Componentes de layout
import AppSidebar from '@/components/layout/AppSidebar.vue'
import AppHeader from '@/components/layout/AppHeader.vue'

// Notificaciones globales
import AppToast from '@/components/common/AppToast.vue'

/**
 * Estado del sidebar en desktop (colapsado o expandido)
 */
const sidebarCollapsed = ref(false)

/**
 * Estado del sidebar en mobile (abierto o cerrado)
 */
const mobileOpen = ref(false)
</script>
