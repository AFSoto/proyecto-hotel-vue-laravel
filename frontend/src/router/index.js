// Importa funciones para crear el router
import { createRouter, createWebHistory } from 'vue-router'

// Store de autenticación
import { useAuthStore } from '@/stores/auth'

// =========================
// VISTAS PÚBLICAS
// =========================

// Página pública inicial
import HomeView from '@/views/HomeView.vue'

// Página de login
import LoginView from '@/views/auth/LoginView.vue'

// =========================
// LAYOUT PRINCIPAL
// =========================

// Layout que envuelve las vistas protegidas
import AppLayout from '@/components/layout/AppLayout.vue'

// =========================
// VISTAS INTERNAS
// =========================


/**
 * Definición de rutas
 */
const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView,
    meta: { requiresAuth: false } // ruta pública
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { requiresAuth: false } // ruta pública
  },

  // =========================
  // RUTAS PROTEGIDAS (APP)
  // =========================
  {
    path: '/app',
    component: AppLayout,
    meta: { requiresAuth: true }, // requiere autenticación
    children: [
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => {
          const auth = useAuthStore()
          return auth.isAdmin || auth.isReceptionist
            ? import('@/views/DashboardView.vue')
            : import('@/views/auth/LoginView.vue')
        },
        meta: { requiresAuth: true, title: 'Dashboard' }
      },
      {
        path: 'rooms',
        name: 'rooms',
        // Lazy loading (import dinámico)
        component: () => import('@/views/RoomsView.vue'),
        meta: { requiresAuth: true, title: 'Habitaciones' }
      },
      {
        path: 'bookings',
        name: 'bookings',
        component: () => import('@/views/DashboardView.vue'), // placeholder
        meta: { requiresAuth: true, title: 'Reservas' }
      },
      {
        path: 'check-in-out',
        name: 'check-in-out',
        component: () => import('@/views/DashboardView.vue'), // placeholder
        meta: { requiresAuth: true, title: 'Check-in / Check-out' }
      },
      {
        path: 'room-types',
        name: 'room-types',
        component: () => import('@/views/DashboardView.vue'), // placeholder
        meta: {
          requiresAuth: true,
          role: 'admin', // solo admin
          title: 'Tipos de habitación'
        }
      },
      {
        path: 'users',
        name: 'users',
        component: () => import('@/views/DashboardView.vue'), // placeholder
        meta: {
          requiresAuth: true,
          role: 'admin',
          title: 'Usuarios'
        }
      },
      {
        path: 'stays',
        name: 'stays',
        component: () => import('@/views/DashboardView.vue'), // placeholder
        meta: {
          requiresAuth: true,
          role: 'admin',
          title: 'Historial de estancias'
        }
      }
    ]
  },

  // =========================
  // 404
  // =========================
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    redirect: '/'
  }
]

/**
 * Creación del router
 */
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

/**
 * Guard global de navegación
 */
router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore()

  // =========================
  // RUTAS PÚBLICAS
  // =========================
  if (!to.meta.requiresAuth) {

    // Si ya está logueado y entra a login → redirigir
    if (to.name === 'login' && auth.isAuthenticated) {
      return next('/app/dashboard')
    }

    return next()
  }

  // =========================
  // REQUIERE AUTENTICACIÓN
  // =========================

  // Si no hay token → login
  if (!auth.isAuthenticated) {
    return next('/login')
  }

  // Si hay token pero no usuario → obtener datos
  if (!auth.user) {
    await auth.fetchMe()

    // Si sigue sin usuario → token inválido
    if (!auth.user) {
      return next('/login')
    }
  }

  // =========================
  // VALIDACIÓN DE ROL
  // =========================
  if (to.meta.role && to.meta.role !== auth.userRole) {
    return next('/app/dashboard')
  }

  // Permitir navegación
  next()
})

// Exporta el router
export default router
