// Catálogo de tipos de documento para huéspedes.
// El backend guarda document_type como texto (default 'cc'), sin enum estricto.

export const DOCUMENT_TYPES = [
  { value: 'cc', label: 'Cédula de ciudadanía' },
  { value: 'ce', label: 'Cédula de extranjería' },
  { value: 'ti', label: 'Tarjeta de identidad' },
  { value: 'pasaporte', label: 'Pasaporte' },
  { value: 'nit', label: 'NIT' },
]

// Mapa value → label, para pintar el texto sin recorrer el array.
export const DOCUMENT_TYPE_LABELS = {
  cc: 'Cédula de ciudadanía',
  ce: 'Cédula de extranjería',
  ti: 'Tarjeta de identidad',
  pasaporte: 'Pasaporte',
  nit: 'NIT',
}
