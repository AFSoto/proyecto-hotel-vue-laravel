<?php

namespace App\DTOs;

/**
 * CreateGuestDTO
 *
 * Transporta los datos validados para crear un huésped.
 */
class CreateGuestDTO
{
    public function __construct(
        public readonly string $fullName,
        public readonly string $documentType,
        public readonly string $documentNumber,
        public readonly ?string $email,
        public readonly ?string $phone,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            fullName: $data['full_name'],
            documentType: $data['document_type'] ?? 'cc',
            documentNumber: $data['document_number'],
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'full_name' => $this->fullName,
            'document_type' => $this->documentType,
            'document_number' => $this->documentNumber,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }
}
