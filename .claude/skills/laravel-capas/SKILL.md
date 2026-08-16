---
name: laravel-capas
description: >-
  Metodología de arquitectura por capas para APIs REST en Laravel: Controller →
  Service → Repository → Model, todo dirigido por interfaces (Contracts), con
  DTOs, FormRequests y Resources, y una envoltura JSON estándar. Úsala SIEMPRE
  que trabajes en el backend de una API Laravel — al crear o modificar un
  endpoint, un recurso/CRUD, un controlador, un servicio, un repositorio, un
  DTO, un request de validación o un resource — aunque el usuario solo diga
  "agrega un endpoint", "hazme el CRUD de X", "necesito guardar/listar/editar
  Y" o "crea el controlador de Z". Su objetivo es que el código nuevo respete
  las mismas capas, responsabilidades y nombres (verbos en español) en vez de
  amontonar lógica en el controlador o saltarse el patrón repositorio.
---

# Arquitectura por capas en APIs Laravel

Esta skill captura una forma de trabajar, no un framework concreto ni una
versión. La idea de fondo es simple: **cada pieza tiene un solo trabajo, y las
capas se hablan por interfaces, no por implementaciones**. Eso mantiene los
controladores delgados, la lógica de negocio testeable y el acceso a datos
reemplazable.

Cuando entres a un proyecto que ya sigue este patrón, **imítalo**: mira un
recurso existente (por ejemplo un `KitController` + `KitService` +
`KitRepository`) y calca la estructura para el recurso nuevo. No inventes una
forma distinta.

## La regla de oro por capa

```
HTTP → Controller → Service → Repository → Model → BD
                       ↑           ↑
                  (Contracts: interfaces que se inyectan)
```

- **Controller** — traduce HTTP. Recibe el `Request`, arma un DTO, llama a UN
  método del servicio y devuelve una respuesta con la envoltura estándar. No
  tiene lógica de negocio ni toca la base de datos.
- **FormRequest** — valida la entrada y da los mensajes de error. Toda la
  validación vive aquí, no en el controlador.
- **DTO** — objeto inmutable que transporta los datos ya validados hacia el
  servicio. Aísla al servicio de la forma del `Request`.
- **Service** — el cerebro. Reglas de negocio, validaciones de dominio
  (existe/no existe, dependencias antes de borrar), orquestación y auditoría.
  Depende de *interfaces* de repositorios y otros servicios, nunca de sus
  implementaciones.
- **Repository** — el único que habla Eloquent/SQL. Expone métodos con nombre
  de intención (`activasPorCliente`) y hereda el CRUD genérico de un
  `BaseRepository`.
- **Contract (interface)** — el contrato de cada servicio y cada repositorio.
  Se inyecta por el constructor; un `ServiceProvider` decide qué
  implementación entra.
- **Resource** — transforma el modelo a la forma JSON pública (formatea fechas,
  enums como `{value, label}`, oculta lo interno).

Si dudas dónde poner algo: **¿es una regla del negocio? → Service. ¿es una
consulta a datos? → Repository. ¿es forma de la respuesta? → Resource. ¿es
forma de la petición? → Request/DTO.**

## Organización por dominios

Todas las capas se agrupan por el mismo dominio (subcarpetas paralelas), para
que un módulo se lea de corrido:

```
app/
├── Http/Controllers/Api/CosaController.php
├── Http/Requests/Dominio/StoreCosaRequest.php
├── Http/Resources/Dominio/CosaResource.php
├── DTOs/Dominio/CosaDTO.php
├── Services/Dominio/CosaService.php
├── Repositories/Dominio/CosaRepository.php
└── Contracts/
    ├── Services/Dominio/CosaServiceInterface.php
    └── Repositories/Dominio/CosaRepositoryInterface.php
```

## Convenciones de nombres

- **Controladores** en singular: `CategoriaController`.
- **Métodos** con verbo-sustantivo en español: `listar`, `obtener`, `crear`,
  `actualizar`, `cambiarEstado`, `eliminar`, `buscarActivos`. Consistencia por
  encima de todo — si el proyecto ya usa estos verbos, úsalos igual.
- **Rutas** RESTful, más `PATCH /{id}/estado` para activar/desactivar.
- **Subida de archivos**: usa un wrapper `POST /{id}/actualizar` (multipart), no
  `PUT`, porque `PUT` no maneja multipart limpio.
- **"Borrar"** casi siempre es *desactivar* (soft delete / cambio de estado),
  no un `DELETE` real. Antes de un borrado real, el servicio valida que no
  haya dependientes activos.

## Receta: agregar un recurso nuevo de punta a punta

Sigue este orden. Cada archivo es pequeño; la fuerza está en que todos existan
y se conecten. Antes de escribir, **abre un recurso ya hecho del proyecto y
úsalo de plantilla**.

1. **Migración + Modelo** (Eloquent), con sus relaciones y casts.
2. **Contrato del repositorio** — `Contracts/Repositories/Dominio/CosaRepositoryInterface.php`.
   Extiende el `BaseRepositoryInterface` y declara solo los métodos de consulta
   propios del dominio.
3. **Repositorio** — `Repositories/Dominio/CosaRepository.php`. Extiende
   `BaseRepository`, recibe el modelo en el constructor y agrega los métodos de
   consulta. Aquí, y solo aquí, va Eloquent.
4. **Contrato del servicio** — `Contracts/Services/Dominio/CosaServiceInterface.php`.
   Firma cada acción de negocio con sus tipos (recibe DTOs, devuelve modelos).
5. **Servicio** — `Services/Dominio/CosaService.php`. Implementa la interfaz,
   inyecta las *interfaces* de los repositorios que necesite y el servicio de
   auditoría. Aquí van las validaciones de dominio y el registro de auditoría.
6. **DTO(s)** — `DTOs/Dominio/CosaDTO.php`. Propiedades `readonly`, un
   `fromRequest()` estático y un `toArray()` que mapea a las columnas.
7. **FormRequest(s)** — `Http/Requests/Dominio/StoreCosaRequest.php` (y
   `Update...`). `rules()` + `messages()`.
8. **Resource** — `Http/Resources/Dominio/CosaResource.php`.
9. **Controller** — `Http/Controllers/Api/CosaController.php`. Delgado, con el
   trait de respuestas e inyectando la *interfaz* del servicio.
10. **Rutas** en `routes/api.php`, dentro del grupo de seguridad correcto
    (auth/rol según el proyecto).
11. **Binding** interface→implementación en el `RepositoryServiceProvider`
    (una línea para el repo, una para el servicio). Sin esto, la inyección
    falla en tiempo de ejecución.
12. **Tests** de feature del endpoint.

## Ejemplo mínimo (patrón, no copiar literal)

**Controller** — traduce HTTP y nada más:

```php
class CosaController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CosaServiceInterface $cosaService,
    ) {}

    public function index(Request $request)
    {
        $cosas = $this->cosaService->listar(
            busqueda: $request->query('busqueda'),
            perPage: $this->getPerPage(default: 15, max: 90),
        );

        return $this->success(
            CosaResource::collection($cosas)->response()->getData(true),
            'Cosas obtenidas exitosamente.'
        );
    }

    public function store(StoreCosaRequest $request)
    {
        $dto = CosaDTO::fromRequest($request);
        $cosa = $this->cosaService->crear($dto);

        return $this->created(new CosaResource($cosa), 'Cosa creada exitosamente.');
    }
}
```

**Service** — reglas de negocio + auditoría, hablando con interfaces:

```php
class CosaService implements CosaServiceInterface
{
    public function __construct(
        private CosaRepositoryInterface $cosaRepository,
        private AuditoriaServiceInterface $auditoriaService,
    ) {}

    public function crear(CosaDTO $dto): Cosa
    {
        // validaciones de dominio antes de escribir...
        $cosa = $this->cosaRepository->create($dto->toArray());

        $this->auditoriaService->info('cosa.creada', 'cosas', $cosa->id, [
            'nombre' => $dto->nombre,
        ]);

        return $cosa->fresh(['relacion']);
    }

    public function actualizar(int $id, CosaDTO $dto): Cosa
    {
        $cosa = $this->cosaRepository->findById($id);
        if (!$cosa) {
            throw new NotFoundException('Cosa', $id);
        }
        $this->cosaRepository->update($cosa, $dto->toArray());

        return $cosa->fresh(['relacion']);
    }
}
```

**Repository** — el único con Eloquent:

```php
class CosaRepository extends BaseRepository implements CosaRepositoryInterface
{
    public function __construct(Cosa $model)
    {
        parent::__construct($model);
    }

    public function activas(): Collection
    {
        return $this->model->where('estado', 'activo')->orderBy('nombre')->get();
    }
}
```

**DTO** — datos validados, inmutables:

```php
class CosaDTO extends BaseDTO
{
    public function __construct(
        public readonly string $nombre,
        public readonly ?string $descripcion,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            nombre: $request->input('nombre'),
            descripcion: $request->input('descripcion'),
        );
    }

    public function toArray(): array
    {
        return ['nombre' => $this->nombre, 'descripcion' => $this->descripcion];
    }
}
```

## La envoltura de respuesta

Las respuestas van con un formato consistente a través de un trait
(`ApiResponse` o equivalente): `success()`, `created()`, `error()`,
`noContent()`. **Respeta el formato que ya use el proyecto** — no reinventes la
forma del JSON ni la clave de errores; míralo en el trait antes de responder.

## Auditoría

Si el proyecto tiene un servicio de auditoría, **toda mutación de un admin se
registra** (crear/actualizar/cambiar estado/eliminar): el servicio llama a
`auditoria->info()` / `->warning()` con la entidad y los cambios. Es parte del
patrón, no un extra opcional.

## Anti-patrones a evitar

- Lógica de negocio o consultas Eloquent dentro del controlador. → va al
  Service / Repository.
- Inyectar clases concretas en vez de interfaces. → rompe el desacoplamiento y
  el test con dobles.
- Devolver modelos crudos con `->toJson()`. → siempre pasa por un Resource.
- Validar a mano en el controlador. → FormRequest.
- Olvidar el binding en el ServiceProvider. → error de resolución en runtime.
- Crear un `delete()` real sin verificar dependientes. → primero valida.

## Checklist antes de dar por terminado

- [ ] Controlador delgado: solo HTTP → DTO → servicio → Resource.
- [ ] Interfaces creadas para servicio y repositorio, e inyectadas.
- [ ] Binding registrado en el ServiceProvider.
- [ ] Validación en FormRequest con mensajes.
- [ ] Respuesta con la envoltura estándar del proyecto.
- [ ] Auditoría en cada mutación (si el proyecto la usa).
- [ ] Nombres de métodos con el verbo español del proyecto.
- [ ] Test de feature del endpoint.
