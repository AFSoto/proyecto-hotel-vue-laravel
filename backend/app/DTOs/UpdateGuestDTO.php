<?php

namespace App\DTOs;

/**
 * UpdateGuestDTO
 *
 * Datos validados para actualizar un huésped. Propiedades nullable;
 * toArray() devuelve solo lo presente.
 */
class UpdateGuestDTO
{
    public function __construct(
        public readonly ?string $fullName,
        public readonly ?string $documentType,
        public readonly ?string $documentNumber,
        public readonly ?string $email,
        public readonly ?string $phone,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            fullName: $data['full_name'] ?? null,
            documentType: $data['document_type'] ?? null,
            documentNumber: $data['document_number'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->fullName !== null) {
            $data['full_name'] = $this->fullName;
        }
        if ($this->documentType !== null) {
            $data['document_type'] = $this->documentType;
        }
        if ($this->documentNumber !== null) {
            $data['document_number'] = $this->documentNumber;
        }
        if ($this->email !== null) {
            $data['email'] = $this->email;
        }
        if ($this->phone !== null) {
            $data['phone'] = $this->phone;
        }

        return $data;
    }
}
