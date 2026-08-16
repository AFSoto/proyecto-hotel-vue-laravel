// Catálogo fijo de estados de habitación.
// Debe coincidir con el enum de la tabla rooms en el backend.

export const ROOM_STATUSES = [
  { value: 'available', label: 'Disponible' },
  { value: 'occupied', label: 'Ocupada' },
  { value: 'maintenance', label: 'Mantenimiento' },
]

// Mapa value → label, para pintar el texto del badge sin recorrer el array.
export const ROOM_STATUS_LABELS = {
  available: 'Disponible',
  occupied: 'Ocupada',
  maintenance: 'Mantenimiento',
}
