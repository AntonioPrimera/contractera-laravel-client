<?php

use AntonioPrimera\ContracteraLaravelClient\ContracteraClient;
use AntonioPrimera\ContracteraLaravelClient\Data\ContracteraAccount;
use AntonioPrimera\ContracteraLaravelClient\Data\GeneratedDocument;
use AntonioPrimera\ContracteraLaravelClient\Data\ValidationPreview;
use AntonioPrimera\ContracteraLaravelClient\Exceptions\MissingApplicationToken;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

it('provisions an account using the configured application token', function () {
    config()->set('contractera-laravel-client.base_url', 'https://contractera.test');
    config()->set('contractera-laravel-client.application_token', 'app-token');

    Http::fake([
        'contractera.test/api/applications/v1/accounts' => Http::response([
            'data' => [
                'id' => 'account-uuid',
                'external_account_id' => 'agrocity-account-123',
                'name' => 'Ferma Test',
                'account_token' => 'account-token',
            ],
        ]),
    ]);

    $account = app(ContracteraClient::class)->provisionAccount(
        externalAccountId: 'agrocity-account-123',
        name: 'Ferma Test',
    );

    expect($account)
        ->toBeInstanceOf(ContracteraAccount::class)
        ->id->toBe('account-uuid')
        ->externalAccountId->toBe('agrocity-account-123')
        ->name->toBe('Ferma Test')
        ->accountToken->toBe('account-token');

    Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer app-token')
        && $request->method() === 'POST'
        && $request->url() === 'https://contractera.test/api/applications/v1/accounts'
        && $request['external_account_id'] === 'agrocity-account-123'
        && $request['name'] === 'Ferma Test');
});

it('lists updates regenerates and deletes application owned accounts', function () {
    config()->set('contractera-laravel-client.base_url', 'https://contractera.test');
    config()->set('contractera-laravel-client.application_token', 'app-token');

    Http::fake([
        'contractera.test/api/applications/v1/accounts' => Http::response([
            'data' => [
                [
                    'id' => 'account-uuid',
                    'external_account_id' => 'agrocity-account-123',
                    'name' => 'Ferma Test',
                ],
            ],
        ]),
        'contractera.test/api/applications/v1/accounts/account-uuid' => Http::response([
            'data' => [
                'id' => 'account-uuid',
                'external_account_id' => 'agrocity-account-123',
                'name' => 'Ferma Redenumita',
            ],
        ]),
        'contractera.test/api/applications/v1/accounts/account-uuid/token' => Http::response([
            'data' => [
                'id' => 'account-uuid',
                'external_account_id' => 'agrocity-account-123',
                'name' => 'Ferma Redenumita',
                'account_token' => 'new-account-token',
            ],
        ]),
    ]);

    $client = app(ContracteraClient::class);

    expect($client->listAccounts())
        ->toHaveCount(1)
        ->sequence(fn ($account) => $account
            ->toBeInstanceOf(ContracteraAccount::class)
            ->id->toBe('account-uuid'));

    expect($client->updateAccount('account-uuid', ['name' => 'Ferma Redenumita']))
        ->toBeInstanceOf(ContracteraAccount::class)
        ->name->toBe('Ferma Redenumita');

    expect($client->regenerateAccountToken('account-uuid'))
        ->toBeInstanceOf(ContracteraAccount::class)
        ->accountToken->toBe('new-account-token');

    $client->deleteAccount('account-uuid');

    Http::assertSent(fn ($request): bool => $request->method() === 'DELETE'
        && $request->url() === 'https://contractera.test/api/applications/v1/accounts/account-uuid'
        && $request->hasHeader('Authorization', 'Bearer app-token'));
});

it('validates and previews a template using an account scoped token', function () {
    config()->set('contractera-laravel-client.base_url', 'https://contractera.test');

    Http::fake([
        'contractera.test/api/v1/templates/template-uuid/validate-preview' => Http::response([
            'data' => [
                'valid' => true,
                'errors' => [],
                'html' => '<p>Contract completat</p>',
                'warning' => 'semantic preview',
            ],
        ]),
    ]);

    $preview = app(ContracteraClient::class)
        ->forAccountToken('account-token')
        ->validatePreview('template-uuid', ['OWNER_NAME' => 'Ion Popescu']);

    expect($preview)
        ->toBeInstanceOf(ValidationPreview::class)
        ->valid->toBeTrue()
        ->errors->toBe([])
        ->html->toBe('<p>Contract completat</p>')
        ->warning->toBe('semantic preview');

    Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer account-token')
        && $request->method() === 'POST'
        && $request->url() === 'https://contractera.test/api/v1/templates/template-uuid/validate-preview'
        && $request['replacements']['OWNER_NAME'] === 'Ion Popescu');
});

it('lists and updates placeholder metadata using the account scoped token', function () {
    config()->set('contractera-laravel-client.base_url', 'https://contractera.test');

    $placeholders = [
        [
            'key' => 'OWNER_NAME',
            'label' => 'Nume proprietar',
            'input_type' => 'text',
            'required' => true,
        ],
    ];

    Http::fake([
        'contractera.test/api/v1/templates/template-uuid/placeholders' => Http::sequence()
            ->push(['data' => $placeholders])
            ->push(['data' => $placeholders]),
    ]);

    $accountClient = app(ContracteraClient::class)->forAccountToken('account-token');

    expect($accountClient->listPlaceholders('template-uuid')['data'])->toBe($placeholders);
    expect($accountClient->updatePlaceholders('template-uuid', $placeholders)['data'])->toBe($placeholders);

    Http::assertSent(fn ($request): bool => $request->method() === 'PATCH'
        && $request->url() === 'https://contractera.test/api/v1/templates/template-uuid/placeholders'
        && $request->hasHeader('Authorization', 'Bearer account-token')
        && $request['placeholders'][0]['key'] === 'OWNER_NAME');
});

it('generates a document and maps the response to a DTO', function () {
    config()->set('contractera-laravel-client.base_url', 'https://contractera.test');

    Http::fake([
        'contractera.test/api/v1/templates/template-uuid/generate' => Http::response([
            'data' => [
                'generated_document_id' => 'document-uuid',
                'status' => 'completed',
                'status_url' => '/api/v1/generated-documents/document-uuid',
                'expires_at' => '2026-07-03T00:00:00+00:00',
                'download_urls' => [
                    'docx' => '/api/v1/generated-documents/document-uuid/download?format=docx',
                    'pdf' => '/api/v1/generated-documents/document-uuid/download?format=pdf',
                    'html' => '/api/v1/generated-documents/document-uuid/download?format=html',
                ],
            ],
        ]),
    ]);

    $document = app(ContracteraClient::class)
        ->forAccountToken('account-token')
        ->generateDocument('template-uuid', ['OWNER_NAME' => 'Ion Popescu'], 'pdf');

    expect($document)
        ->toBeInstanceOf(GeneratedDocument::class)
        ->id->toBe('document-uuid')
        ->status->toBe('completed')
        ->statusUrl->toBe('/api/v1/generated-documents/document-uuid')
        ->downloadUrls->toHaveKey('pdf', '/api/v1/generated-documents/document-uuid/download?format=pdf');

    Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer account-token')
        && $request->method() === 'POST'
        && $request['format'] === 'pdf'
        && $request['replacements']['OWNER_NAME'] === 'Ion Popescu');
});

it('uploads a docx template as multipart with the account scoped token', function () {
    config()->set('contractera-laravel-client.base_url', 'https://contractera.test');

    Http::fake([
        'contractera.test/api/v1/templates' => Http::response([
            'data' => [
                'id' => 'template-uuid',
                'name' => 'Contract arenda',
                'placeholder_pattern' => '__#__',
            ],
        ]),
    ]);

    $template = app(ContracteraClient::class)
        ->forAccountToken('account-token')
        ->uploadTemplate(
            name: 'Contract arenda',
            placeholderPattern: '__#__',
            file: UploadedFile::fake()->create('contract.docx', 12, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        );

    expect($template['data']['id'])->toBe('template-uuid');

    Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer account-token')
        && $request->method() === 'POST'
        && $request->url() === 'https://contractera.test/api/v1/templates');
});

it('throws a dedicated exception when application token is missing', function () {
    config()->set('contractera-laravel-client.application_token', null);

    app(ContracteraClient::class)->provisionAccount('agrocity-account-123', 'Ferma Test');
})->throws(MissingApplicationToken::class);
