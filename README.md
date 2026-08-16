<div align="center">

# 🏨 HotelOS

### MVP — Sistema de Gestión Hotelera

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue](https://img.shields.io/badge/Vue-3-4FC08D?style=flat&logo=vue.js&logoColor=white)](https://vuejs.org)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)](https://mysql.com)
[![JWT](https://img.shields.io/badge/Auth-JWT-000000?style=flat&logo=jsonwebtokens&logoColor=white)](https://jwt.io)
[![Tailwind](https://img.shields.io/badge/Tailwind-4-06B6D4?style=flat&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)

Sistema de gestión para hoteles pequeños y medianos.  
Reservas · Habitaciones · Check-in/out · Facturación

</div>

---

## Tabla de contenidos

- [Descripción](#descripción)
- [Módulos](#módulos)
- [Stack tecnológico](#stack-tecnológico)
- [Arquitectura](#arquitectura)
- [Requisitos](#requisitos)
- [Docker (recomendado)](#docker-recomendado)
- [Instalación — Backend](#instalación--backend)
- [Instalación — Frontend](#instalación--frontend)
- [Variables de entorno](#variables-de-entorno)
- [Base de datos](#base-de-datos)
- [Autenticación JWT](#autenticación-jwt)
- [Endpoints de la API](#endpoints-de-la-api)
- [Roles y permisos](#roles-y-permisos)
- [Flujo de trabajo Git](#flujo-de-trabajo-git)
- [Conventional Commits](#conventional-commits)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Equipo](#equipo)

---

## Descripción

HotelOS es un sistema de gestión hotelera construido como proyecto de práctica
full-stack. Cubre las operaciones esenciales de un hotel: control de habitaciones,
reservas, check-in/check-out y facturación básica.

Arquitectura desacoplada: el **Backend** expone una API REST y el **Frontend**
la consume como SPA. Nunca comparten código ni servidor.

---

## Módulos

| # | Módulo | Estado |
|---|--------|--------|
| 1 | Autenticación y roles (JWT) | ✅ Sprint 1 |
| 2 | Gestión de habitaciones | 🔄 Sprint 2 |
| 3 | Reservas | ⏳ Sprint 3 |
| 4 | Check-in / Check-out / Facturación | ⏳ Sprint 4 |

---

## Stack tecnológico

### Backend
| Tecnología | Versión | Uso |
|---|---|---|
| PHP | 8.2+ | Lenguaje base |
| Laravel | 11 | Framework principal |
| MySQL | 8.0 | Base de datos |
| tymon/jwt-auth | 2.x | Autenticación con tokens JWT |
| Laravel Resources | — | Transformación de respuestas JSON |
| Form Requests | — | Validación de entradas |

### Frontend
| Tecnología | Versión | Uso |
|---|---|---|
| Vue | 3 | Framework principal (Composition API) |
| Vite | 5 | Build tool y dev server |
| Pinia | 2 | Manejo de estado global |
| Vue Router | 4 | Navegación y guards de ruta |
| Axios | 1.x | Cliente HTTP con interceptores |
| Tailwind CSS | 3 | Estilos utilitarios |
| Lucide Vue | — | Íconos |

---

## Arquitectura
┌─────────────────────────────────────────┐
│           Frontend (Vue 3 SPA)          │
│         localhost:5173                  │
│   Pinia · Vue Router · Axios            │
└────────────────┬────────────────────────┘
│ HTTP / JSON
│ Authorization: Bearer {token}
┌────────────────▼────────────────────────┐
│         Backend (Laravel 11)            │
│         localhost:8000/api              │
│   JWT Auth · Resources · Policies       │
└────────────────┬────────────────────────┘
│ Eloquent ORM
┌────────────────▼────────────────────────┐
│           MySQL 8 Database              │
│   11 tablas · Soft deletes · Audit log  │
└─────────────────────────────────────────┘

---

## Requisitos

Antes de instalar asegúrate de tener:

- PHP >= 8.2
- Composer >= 2.x
- Node.js >= 20.x
- npm >= 10.x
- MySQL >= 8.0
- Git

---

## Docker (recomendado)

La forma más rápida de levantar **toda** la plataforma (frontend + backend + MySQL +
phpMyAdmin) sin instalar PHP, Node ni MySQL en tu máquina. Cada servicio corre en su
propio contenedor con su versión fijada, así que no choca con otras apps (p. ej. otra
versión de PHP en XAMPP).

### Requisito

- **Docker Desktop** (en Windows, con backend **WSL2** activado). Verifica con:
  ```bash
  docker --version && docker compose version
  ```

### Servicios y puertos

| Servicio | URL / acceso | Descripción |
|---|---|---|
| Frontend (Vue/Vite) | http://localhost:5173 | SPA con hot-reload |
| Backend (Laravel API) | http://localhost:8000/api | API REST + JWT |
| phpMyAdmin | http://localhost:8080 | GUI de la BD — login `root` / `1234567` |
| MySQL | `localhost:3308` | Para GUIs externas (DBeaver, etc.). Puerto 3308 para no chocar con el MySQL de XAMPP |

### Primer arranque (clon nuevo)

```bash
# Desde la raíz del repositorio

# 1. Construir imágenes y levantar todos los servicios
docker compose up -d --build

# 2. (Si no tienes backend/.env todavía) crear el .env y sus llaves
docker compose run --rm backend cp .env.example .env
docker compose run --rm backend php artisan key:generate
docker compose run --rm backend php artisan jwt:secret
#    Asegúrate de que en backend/.env: SESSION_DRIVER=file y CACHE_STORE=file

# 3. Instalar dependencias del backend (llena el volumen de vendor)
docker compose run --rm backend composer install

# 4. Migrar y sembrar datos de prueba
docker compose run --rm backend php artisan migrate --seed

# 5. Reiniciar el backend ya con las dependencias listas
docker compose restart backend
```

Luego abre **http://localhost:5173** y entra con `admin@hotel.com` / `password`.

> La config de base de datos para Docker (`DB_HOST=db`, `DB_PORT=3306`, credenciales)
> la inyecta `docker-compose.yml` como variables de entorno; **no** hace falta editar tu
> `.env`, que sigue sirviendo para correr en el host con XAMPP (`127.0.0.1`).

### Arranque diario

```bash
docker compose up -d      # levantar todo (imágenes ya construidas)
docker compose down       # apagar (los datos de MySQL persisten en el volumen db_data)
```

### Comandos útiles

```bash
docker compose logs -f backend            # ver logs en vivo (backend | frontend | db)
docker compose exec backend php artisan … # artisan dentro del contenedor
docker compose exec backend composer …    # composer dentro del contenedor
docker compose exec frontend npm …        # npm dentro del contenedor
docker compose build backend              # reconstruir tras cambiar Dockerfile o deps
docker compose down -v                    # ⚠️ apagar y BORRAR datos/volúmenes (reset total)
```

### Notas / gotchas

- **`--no-reload` en el backend**: sin esta bandera, `php artisan serve` (con su
  auto-reload) elimina del proceso servidor las variables de entorno que le inyecta
  Docker (`DB_HOST`, etc.) y la app terminaría leyendo el `.env` local. Ya está fijada
  en `backend/Dockerfile`.
- **Hot-reload en Windows**: se activa `CHOKIDAR_USEPOLLING=true` en el frontend porque
  los eventos de archivo de NTFS no llegan bien al contenedor Linux.
- **`npm install` (no `npm ci`)** en el frontend: el `package-lock.json` del repo está
  desincronizado con `package.json`. Al regenerar el lock (`npm install` + commit) se
  puede volver a `npm ci` para builds reproducibles.

Si prefieres correr **sin Docker**, sigue las secciones de instalación manual a continuación.

---

## Instalación — Backend

### 1. Clonar el repositorio

```bash
git clone https://github.com/tu-usuario/hotelOS.git
cd hotelOS/backend
```

### 2. Instalar dependencias

```bash
composer install
```

### 3. Configurar variables de entorno

```bash
cp .env.example .env
```

Editar `.env` con tus datos (ver sección [Variables de entorno](#variables-de-entorno)).

### 4. Generar keys

```bash
php artisan key:generate
php artisan jwt:secret
```

### 5. Crear la base de datos

```sql
CREATE DATABASE hotelOS CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 6. Ejecutar migraciones y seeders

```bash
# Solo migraciones
php artisan migrate

# Migraciones + datos de prueba
php artisan migrate --seed
```

### 7. Levantar el servidor

```bash
php artisan serve
```

API disponible en `http://localhost:8000/api`

---

## Instalación — Frontend

### 1. Ir a la carpeta del frontend

```bash
cd hotelOS/frontend
```

### 2. Instalar dependencias

```bash
npm install
```

### 3. Configurar variables de entorno

```bash
cp .env.example .env
```

Editar `.env`:

```env
VITE_API_URL=http://localhost:8000/api
```

### 4. Levantar el servidor de desarrollo

```bash
npm run dev
```

App disponible en `http://localhost:5173`

---

## Variables de entorno

### Backend (.env)

```env
APP_NAME=HotelOS
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hotelOS
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=        # generado con php artisan jwt:secret
JWT_TTL=60         # minutos que dura el token
JWT_REFRESH_TTL=20160  # minutos para refrescar (2 semanas)
```

### Frontend (.env)

```env
VITE_API_URL=http://localhost:8000/api
```

---

## Base de datos

El proyecto usa **11 tablas**:
roles                 → catálogo de roles del sistema
users                 → empleados que operan el sistema
room_types            → categorías de habitación con precio base
rooms                 → habitaciones físicas del hotel
guests                → huéspedes (no tienen acceso al sistema)
bookings              → reservas vinculando huésped + habitación + fechas
check_ins             → registro de entrada del huésped
check_outs            → registro de salida + cálculo de total
invoices              → factura generada automáticamente al hacer check-out
audit_logs            → historial inmutable de todas las operaciones críticas
personal_access_tokens → tabla interna de Laravel (no usada directamente)

Todas las tablas operativas incluyen `created_at`, `updated_at` y `deleted_at`
(soft delete). Las tablas `check_ins`, `check_outs` e `invoices` son inmutables
— no tienen soft delete.

---

## Autenticación JWT

El sistema usa **JSON Web Tokens** mediante el paquete `tymon/jwt-auth`.

### Flujo de autenticación

POST /api/auth/login  →  retorna { token, data: usuario }
Todas las requests siguientes llevan el header:
Authorization: Bearer {token}
El token dura 60 minutos (configurable en JWT_TTL)
Antes de expirar, el Frontend llama automáticamente a:
POST /api/auth/refresh  →  retorna nuevo token
POST /api/auth/logout   →  invalida el token actual


### Usuarios de prueba

| Email | Contraseña | Rol |
|---|---|---|
| admin@hotel.com | password | Administrador |
| recep@hotel.com | password | Recepcionista |

---

## Endpoints de la API

### Auth
POST   /api/auth/login      Iniciar sesión
POST   /api/auth/logout     Cerrar sesión        [auth]
GET    /api/auth/me         Usuario autenticado  [auth]
POST   /api/auth/refresh    Refrescar token      [auth]

### Usuarios
GET    /api/users           Listar usuarios      [auth][admin]
POST   /api/users           Crear usuario        [auth][admin]
GET    /api/users/{id}      Ver usuario          [auth][admin]
PUT    /api/users/{id}      Editar usuario       [auth][admin]
DELETE /api/users/{id}      Desactivar usuario   [auth][admin]

### Tipos de habitación
GET    /api/room-types      Listar tipos         [auth]
POST   /api/room-types      Crear tipo           [auth][admin]
GET    /api/room-types/{id} Ver tipo             [auth]
PUT    /api/room-types/{id} Editar tipo          [auth][admin]
DELETE /api/room-types/{id} Eliminar tipo        [auth][admin]

### Habitaciones
GET    /api/rooms                    Listar habitaciones     [auth]
POST   /api/rooms                    Crear habitación        [auth][admin]
GET    /api/rooms/{id}               Ver habitación          [auth]
PUT    /api/rooms/{id}               Editar habitación       [auth][admin]
DELETE /api/rooms/{id}               Eliminar habitación     [auth][admin]
PATCH  /api/rooms/{id}/status        Cambiar estado          [auth]
GET    /api/rooms/available          Ver disponibilidad      [auth]

### Reservas
GET    /api/bookings           Listar reservas      [auth]
POST   /api/bookings           Crear reserva        [auth]
GET    /api/bookings/{id}      Ver reserva          [auth]
PUT    /api/bookings/{id}      Editar reserva       [auth]
PATCH  /api/bookings/{id}/cancel  Cancelar          [auth]

### Check-in / Check-out
POST   /api/check-ins          Registrar check-in   [auth]
POST   /api/check-outs         Registrar check-out  [auth]

### Facturación e historial
GET    /api/invoices           Listar facturas      [auth][admin]
GET    /api/invoices/{id}      Ver factura          [auth][admin]
GET    /api/stays              Historial estancias  [auth][admin]
GET    /api/dashboard/stats    Métricas del día     [auth]

### Formato de respuesta

Todas las respuestas siguen este contrato:

```json
// Éxito
{
  "data": { ... },
  "message": "Operación exitosa"
}

// Error de validación (422)
{
  "message": "Los datos proporcionados no son válidos.",
  "errors": {
    "email": ["El email ya está en uso."]
  }
}

// Error general (401, 403, 404)
{
  "message": "No autorizado."
}
```

---

## Roles y permisos

| Acción | Admin | Recepcionista |
|--------|:-----:|:-------------:|
| Ver habitaciones | ✅ | ✅ |
| Crear / editar habitaciones | ✅ | ❌ |
| Cambiar estado de habitación | ✅ | ✅ |
| Ver tipos de habitación | ✅ | ✅ |
| Crear / editar tipos | ✅ | ❌ |
| Ver reservas | ✅ | ✅ |
| Crear / cancelar reservas | ✅ | ✅ |
| Hacer check-in / check-out | ✅ | ✅ |
| Ver historial de estancias | ✅ | ❌ |
| Ver facturas | ✅ | ❌ |
| Gestionar usuarios del sistema | ✅ | ❌ |
| Ver dashboard con métricas | ✅ | ✅ (versión reducida) |

---

## Flujo de trabajo Git

Este proyecto usa **Git Flow**:
main          →  código en producción (nunca push directo)
develop       →  rama de integración (base de todo el trabajo)
feature/be-*  →  tareas de Backend
feature/fe-*  →  tareas de Frontend
release/*     →  preparación de entrega
hotfix/*      →  correcciones urgentes en producción

### Flujo para una tarea nueva

```bash
# 1. Siempre partir desde develop actualizado
git checkout develop
git pull origin develop

# 2. Crear la rama de la tarea
git checkout -b feature/be-auth-controller

# 3. Trabajar y hacer commits frecuentes
git commit -m "feat(auth): implement jwt login endpoint"

# 4. Subir la rama
git push origin feature/be-auth-controller

# 5. Abrir Pull Request hacia develop en GitHub
# 6. Esperar code review y aprobación
# 7. Merge Squash → develop y borrar la rama
```

### Reglas

- Nunca hacer push directo a `main` ni a `develop`
- Nunca mergear tu propio Pull Request
- Un PR = una tarea
- El Backend publica los endpoints en Postman antes de que el Frontend los consuma

---

## Conventional Commits

Todos los commits siguen el estándar **Conventional Commits**:
<tipo>(<alcance>): <descripción en minúsculas>

| Tipo | Cuándo usarlo |
|------|---------------|
| `feat` | Nueva funcionalidad |
| `fix` | Corrección de bug |
| `chore` | Dependencias, configuración |
| `refactor` | Mejora de código sin cambiar funcionalidad |
| `docs` | Solo documentación |
| `test` | Agregar o modificar pruebas |
| `style` | Formato, espacios (no lógica) |

### Alcances por módulo
`auth` · `rooms` · `bookings` · `checkin` · `invoice` · `router` · `store` · `ui`

### Ejemplos

```bash
feat(auth): implement jwt login with role validation
fix(bookings): prevent overlapping reservations on same room
feat(rooms): add availability endpoint by date range
chore: install tymon jwt-auth package
refactor(store): extract auth logic to pinia store
docs: add postman collection to repository
```

---

## Estructura del proyecto
hotelOS/
├── backend/                        # Laravel 11
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   └── Api/            # Todos los controladores de la API
│   │   │   ├── Requests/           # Form Requests (validación)
│   │   │   ├── Resources/          # API Resources (transformación JSON)
│   │   │   └── Middleware/         # CheckRole, etc.
│   │   ├── Models/                 # Eloquent models
│   │   └── Observers/              # AuditObserver
│   ├── database/
│   │   ├── migrations/             # Una migration por tabla
│   │   └── seeders/                # Datos de prueba
│   ├── routes/
│   │   └── api.php                 # Todas las rutas de la API
│   ├── config/
│   │   ├── jwt.php                 # Config de JWT
│   │   └── cors.php                # Config de CORS
│   └── docs/
│       └── postman/                # Colección Postman exportada
│
├── frontend/                       # Vue 3
│   ├── src/
│   │   ├── components/
│   │   │   ├── common/             # AppButton, AppInput, AppModal, etc.
│   │   │   ├── layout/             # AppSidebar, AppHeader, AppLayout
│   │   │   └── modules/            # Componentes específicos por módulo
│   │   ├── composables/            # useToast, useModal, usePagination
│   │   ├── lib/
│   │   │   └── axios.js            # Instancia Axios con interceptores JWT
│   │   ├── router/
│   │   │   └── index.js            # Rutas + guards por rol
│   │   ├── stores/                 # auth, rooms, bookings, checkin
│   │   └── views/
│   │       ├── auth/               # LoginView
│   │       ├── admin/              # Dashboard, Rooms, Bookings, etc.
│   │       └── receptionist/       # Home, Rooms, Bookings, CheckInOut
│   └── public/
│
└── README.md

---

## Equipo

| Rol | Responsabilidad |
|-----|----------------|
| Backend | Laravel API · Migrations · Endpoints · JWT |
| Frontend | Vue 3 · Pinia · Vistas · Integración API |

---

<div align="center">

Proyecto de práctica · HotelOS MVP · 2025

</div>
