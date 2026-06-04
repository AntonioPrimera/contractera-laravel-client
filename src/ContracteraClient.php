<?php

namespace AntonioPrimera\ContracteraLaravelClient;

use AntonioPrimera\ContracteraLaravelClient\Data\ContracteraAccount;
use AntonioPrimera\ContracteraLaravelClient\Exceptions\MissingApplicationToken;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ContracteraClient
{
    /**
     * @return array<int, ContracteraAccount>
     */
    public function listAccounts(): array
    {
        $response = $this->applicationRequest()
            ->get('/api/applications/v1/accounts')
            ->throw()
            ->json('data');

        if (! is_array($response)) {
            return [];
        }

        return array_map(
            fn (array $account): ContracteraAccount => ContracteraAccount::fromArray($account),
            array_filter($response, 'is_array'),
        );
    }

    public function provisionAccount(string $externalAccountId, string $name): ContracteraAccount
    {
        $response = $this->applicationRequest()
            ->post('/api/applications/v1/accounts', [
                'external_account_id' => $externalAccountId,
                'name' => $name,
            ])
            ->throw()
            ->json('data');

        return ContracteraAccount::fromArray(is_array($response) ? $response : []);
    }

    public function updateAccount(string $accountId, array $attributes): ContracteraAccount
    {
        $response = $this->applicationRequest()
            ->patch("/api/applications/v1/accounts/{$accountId}", $attributes)
            ->throw()
            ->json('data');

        return ContracteraAccount::fromArray(is_array($response) ? $response : []);
    }

    public function regenerateAccountToken(string $accountId): ContracteraAccount
    {
        $response = $this->applicationRequest()
            ->post("/api/applications/v1/accounts/{$accountId}/token")
            ->throw()
            ->json('data');

        return ContracteraAccount::fromArray(is_array($response) ? $response : []);
    }

    public function deleteAccount(string $accountId): void
    {
        $this->applicationRequest()
            ->delete("/api/applications/v1/accounts/{$accountId}")
            ->throw();
    }

    public function forAccountToken(string $accountToken): ContracteraAccountClient
    {
        return new ContracteraAccountClient($this->baseRequest()->withToken($accountToken));
    }

    private function applicationRequest(): PendingRequest
    {
        $token = config('contractera-laravel-client.application_token');

        if (! is_string($token) || $token === '') {
            throw MissingApplicationToken::make();
        }

        return $this->baseRequest()->withToken($token);
    }

    private function baseRequest(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('contractera-laravel-client.base_url'), '/'))
            ->acceptJson()
            ->timeout((int) config('contractera-laravel-client.timeout', 30))
            ->retry(
                (int) config('contractera-laravel-client.retry_times', 2),
                (int) config('contractera-laravel-client.retry_sleep_milliseconds', 250),
            );
    }
}
