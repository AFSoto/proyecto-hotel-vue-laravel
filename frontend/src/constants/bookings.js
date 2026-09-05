// Catálogo fijo de estados de reserva.
// Debe coincidir con el enum de la tabla bookings en el backend
// y con los variants soportados por AppBadge.

export const BOOKING_STATUSES = [
  { value: 'confirmed', label: 'Confirmada' },
  { value: 'checked_in', label: 'Check-in' },
  { value: 'checked_out', label: 'Check-out' },
  { value: 'cancelled', label: 'Cancelada' },
]

// Mapa value → label, para pintar el texto del badge sin recorrer el array.
export const BOOKING_STATUS_LABELS = {
  confirmed: 'Confirmada',
  checked_in: 'Check-in',
  checked_out: 'Check-out',
  cancelled: 'Cancelada',
}
