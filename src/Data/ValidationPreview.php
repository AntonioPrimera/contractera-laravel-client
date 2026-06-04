<?php

namespace AntonioPrimera\ContracteraLaravelClient\Data;

class ValidationPreview
{
    public function __construct(
        public readonly bool $valid,
        public readonly array $errors,
        public readonly ?string $html,
        public readonly ?string $warning,
        public readonly array $raw,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            valid: (bool) ($data['valid'] ?? false),
            errors: is_array($data['errors'] ?? null) ? $data['errors'] : [],
            html: is_string($data['html'] ?? null) ? $data['html'] : null,
            warning: is_string($data['warning'] ?? null) ? $data['warning'] : null,
            raw: $data,
        );
    }
}
