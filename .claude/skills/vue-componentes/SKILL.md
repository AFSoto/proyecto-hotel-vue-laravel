---
name: vue-componentes
description: >-
  Metodología de arquitectura de componentes para SPAs en Vue 3 (Composition
  API con <script setup>): la vista queda delgada y la lógica vive en
  composables; el estado global en stores tipo Pinia; las llamadas HTTP
  centralizadas en un módulo de endpoints sobre una instancia de axios con
  interceptores; y los componentes se separan en admin/public/shared. Úsala
  SIEMPRE que trabajes en el frontend Vue — al crear o modificar una vista, un
  componente, un modal/formulario, un composable, un store o el cliente de API
  — aunque el usuario solo diga "hazme la vista de X", "crea el modal de Y",
  "necesito un formulario para Z" o "conecta esta pantalla con el backend". Su
  objetivo es que el código nuevo respete la misma separación de
  responsabilidades, patrones de estado y convenciones en vez de meter fetch y
  lógica dentro del componente.
---

# Arquitectura de componentes en SPAs Vue

Esta skill captura una forma de trabajar, no una versión concreta de Vue,
Pinia ni Tailwind. La idea de fondo: **el componente es la cara; la lógica vive
fuera de él**. Una vista debe leerse casi como HTML declarativo; el "cómo"
(cargar, filtrar, paginar, guardar) se delega a composables y stores.

Cuando entres a un proyecto que ya sigue este patrón, **imítalo**: abre una
vista o un modal existente y calca su estructura. No mezcles estilos distintos.

## Las capas del frontend

```
Vista (route-level)
  └─ usa un composable  → estado + acciones de una entidad (lista, filtros, CRUD)
        └─ usa el módulo de endpoints → funciones que llaman a axios
              └─ axios (interceptores: token, refresh, errores)
Componentes reutilizables (modales, filas, inputs) ← reciben props / emiten eventos
Store (Pinia)   → estado GLOBAL y transversal (sesión, notificaciones, toasts)
```

Responsabilidades:

- **Vista / componente (`<script setup>`)** — presentación e interacción.
  Declara props/emits, arma el formulario, muestra estado. Consume lo que el
  composable expone. No hace `fetch` directo ni sabe rutas de API.
- **Composable (`useCosa`)** — encapsula el estado de una pantalla o entidad:
  la lista, los filtros reactivos, la paginación, `loading`/`saving` y las
  acciones (`cargar`, `crear`, `actualizar`, `cambiarEstado`). Devuelve refs y
  funciones. Es lo que mantiene la vista delgada.
- **Store (Pinia)** — estado *global* que sobrevive entre vistas: sesión/token,
  notificaciones en vivo, toasts, UI. No metas aquí el estado local de una
  pantalla; eso es un composable.
- **Módulo de endpoints** — objetos por dominio (`cosasApi`) con un método por
  operación (`listar`, `obtener`, `crear`, `actualizar`, `cambiarEstado`,
  `eliminar`). Es el único lugar con las URLs.
- **axios (instancia única)** — inyecta el token en cada request y renueva en
  401; centraliza el manejo de errores. Los componentes nunca lo tocan directo.

Si dudas dónde poner algo: **¿estado de una sola pantalla? → composable.
¿estado global entre pantallas? → store. ¿una URL de API? → módulo de
endpoints. ¿presentación? → componente.**

## Organización de carpetas

```
src/
├── api/           axios.js · endpoints.js · (echo/websocket)
├── stores/        Pinia: auth, notificaciones, toast, ui...
├── composables/   useCosa.js  (uno por entidad/pantalla)
├── components/
│   ├── admin/     componentes del panel
│   ├── public/    componentes del sitio público
│   └── shared/    reutilizables (AppModal, FieldError, PaginationBar...)
├── views/         componentes a nivel de ruta
├── layouts/       PublicLayout, AdminLayout
├── constants/     opciones y catálogos fijos (TIPOS_PERSONA...)
├── utils/         helpers puros (fechas, números)
└── router/        rutas + guardas de navegación
```

## Patrón: composable de una entidad

El corazón del enfoque. La vista casi no tiene JS porque todo esto vive aquí:

```js
export function useCosas() {
  const cosas = ref([]);
  const loading = ref(false);
  const saving = ref(false);
  const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 });

  const filtros = reactive({ busqueda: "", estado: "", per_page: 15, page: 1 });
  let debounceTimer = null;

  async function cargar() {
    loading.value = true;
    try {
      const params = { per_page: filtros.per_page, page: filtros.page };
      if (filtros.busqueda) params.busqueda = filtros.busqueda;
      if (filtros.estado) params.estado = filtros.estado;
      const { data } = await cosasApi.listar(params);
      const paginator = data.data;       // respeta la envoltura del backend
      cosas.value = paginator.data;
      meta.value = paginator.meta;
    } finally {
      loading.value = false;
    }
  }

  async function crear(payload) {
    saving.value = true;
    try { await cosasApi.crear(payload); filtros.page = 1; await cargar(); }
    finally { saving.value = false; }
  }

  // La búsqueda con debounce no golpea la API en cada tecla.
  watch(() => filtros.busqueda, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => { filtros.page = 1; cargar(); }, 400);
  });
  // Filtros discretos y paginación: respuesta inmediata.
  watch([() => filtros.estado, () => filtros.per_page], () => { filtros.page = 1; cargar(); });
  watch(() => filtros.page, cargar);

  return { cosas, loading, saving, meta, filtros, cargar, crear };
}
```

## Patrón: módulo de endpoints

Un objeto por dominio, un método por operación, y toda la URL concentrada aquí:

```js
export const cosasApi = {
  listar(params = {})     { return api.get("cosas", { params }); },
  obtener(id)             { return api.get(`cosas/${id}`); },
  crear(data)             { return api.post("cosas", data); },
  actualizar(id, data)    { return api.put(`cosas/${id}`, data); },
  cambiarEstado(id, estado) { return api.patch(`cosas/${id}/estado`, { estado }); },
  eliminar(id)            { return api.delete(`cosas/${id}`); },
};
```

Casos especiales que ya tienen su forma: multipart va con
`headers: { "Content-Type": "multipart/form-data" }`; descargas autenticadas
(Excel/PDF) van con `responseType: "blob"`; sub-recursos anidados se agrupan
como objeto dentro del padre (`cosasApi.hijos.listar(id)`).

## Patrón: store Pinia (estilo setup)

Para estado global. Estructura en tres bloques — state (`ref`), getters
(`computed`), actions (funciones) — y `return` de lo que se expone:

```js
export const useAuthStore = defineStore("auth", () => {
  const user = ref(null);
  const token = ref(localStorage.getItem("token") || null);

  const isAuthenticated = computed(() => !!token.value && !!user.value);

  async function login(email, password) { /* ... */ }
  function clearAuth() { /* ... */ }

  return { user, token, isAuthenticated, login, clearAuth };
});
```

## Patrón: componente de formulario/modal

`<script setup>`, props tipadas con defaults, `defineEmits`, un formulario
`reactive` construido desde una función `estadoInicial()` (para poder
resetear), `computed` para derivados y `watch` para reaccionar:

```vue
<script setup>
import { reactive, computed, watch } from "vue";
import AppModal from "@/components/shared/AppModal.vue";
import FieldError from "@/components/shared/FieldError.vue";

const props = defineProps({
  open:   { type: Boolean, default: false },
  mode:   { type: String,  default: "crear" },   // 'crear' | 'editar'
  saving: { type: Boolean, default: false },
  errors: { type: Object,  default: () => ({}) },
});
const emit = defineEmits(["close", "submit"]);

const estadoInicial = () => ({ nombre: "", estado: "activo" });
const form = reactive(estadoInicial());
function resetForm() { Object.assign(form, estadoInicial()); }

// Errores de validación que vienen del backend (por campo).
const fieldError = (f) => {
  const e = props.errors?.[f];
  return Array.isArray(e) ? (e[0] ?? null) : (e ?? null);
};
</script>
```

Reglas de componente:
- **Comunicación**: datos bajan por **props**, eventos suben por **emits**. Un
  hijo no muta el estado del padre; lo pide con un evento.
- **Errores de validación**: se muestran por campo con un `FieldError` +
  helper `fieldError()` (o un composable `useFormErrors`). Los errores llegan
  del backend, no se duplica la validación.
- **Estilos**: usa el sistema del proyecto (Tailwind u otro) y reutiliza los
  componentes `shared/` antes de crear uno nuevo.
- **Constantes** (opciones de selects, catálogos fijos) van en `constants/`,
  no hardcodeadas en el template.

## Receta: nueva pantalla conectada al backend

1. Agrega el módulo `cosasApi` en el archivo de endpoints (una función por
   operación).
2. Crea `useCosas()` en `composables/` con estado, filtros, paginación y
   acciones.
3. Crea la vista en `views/`: consume el composable, renderiza lista + filtros
   + estados de carga, y monta los modales.
4. Crea los componentes hijos en `components/` (fila, modal de formulario) con
   props/emits; reutiliza los de `shared/`.
5. Registra la ruta y, si aplica, protégela con la guarda de navegación.
6. Tests con el runner del proyecto: monta el componente, stubea los hijos y
   mockea el módulo de API.

## Anti-patrones a evitar

- `fetch`/axios directo dentro de un componente. → va al módulo de endpoints,
  vía composable.
- Lógica de lista/filtros/paginación dentro de la vista. → composable.
- Estado local de una pantalla metido en un store global. → composable.
- URLs de API repartidas por los componentes. → centralízalas en endpoints.
- Un hijo mutando props del padre. → emite un evento.
- Reimplementar un modal/input que ya existe en `shared/`.

## Checklist antes de dar por terminado

- [ ] La vista está delgada: consume un composable, casi sin lógica propia.
- [ ] Toda llamada HTTP pasa por el módulo de endpoints.
- [ ] Estado global solo en stores; estado de pantalla en composables.
- [ ] Props tipadas y comunicación por props/emits (sin mutar props).
- [ ] Errores de validación mostrados por campo desde la respuesta del backend.
- [ ] Reutilizados los componentes `shared/` disponibles.
- [ ] Ruta registrada y protegida si corresponde.
- [ ] Test que monta el componente y mockea la API.
