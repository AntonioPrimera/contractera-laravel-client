<?php

namespace AntonioPrimera\ContracteraLaravelClient\Data;

class ContracteraAccount
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $externalAccountId,
        public readonly ?string $name,
        public readonly ?string $accountToken,
        public readonly array $raw,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: self::nullableString($data['id'] ?? null),
            externalAccountId: self::nullableString($data['external_account_id'] ?? null),
            name: self::nullableString($data['name'] ?? null),
            accountToken: self::nullableString($data['account_token'] ?? null),
            raw: $data,
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
