<?php

namespace AntonioPrimera\ContracteraLaravelClient\Data;

class GeneratedDocument
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $status,
        public readonly ?string $statusUrl,
        public readonly ?string $expiresAt,
        public readonly array $downloadUrls,
        public readonly array $raw,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: self::nullableString($data['generated_document_id'] ?? $data['document_id'] ?? null),
            status: self::nullableString($data['status'] ?? null),
            statusUrl: self::nullableString($data['status_url'] ?? null),
            expiresAt: self::nullableString($data['expires_at'] ?? null),
            downloadUrls: is_array($data['download_urls'] ?? null) ? $data['download_urls'] : [],
            raw: $data,
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
