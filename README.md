# Contractera Laravel Client

Laravel client package for backend-only Contractera integrations.

The package is meant to be used together with the Vue SDK:

```bash
npm install @raprim/contractera-plugin-vue3
```

The frontend package renders the UI. This Composer package handles the server-to-server Contractera API calls from a Laravel host application such as AgroCity or ProjectCity.

Browser code must never receive Contractera tokens.

## Installation

```bash
composer require antonioprimera/contractera-laravel-client
```

Publish the config:

```bash
php artisan vendor:publish --tag="contractera-laravel-client-config"
```

Add environment values:

```env
CONTRACTERA_BASE_URL=https://contractera.ro
CONTRACTERA_APPLICATION_TOKEN=plain-text-application-token
CONTRACTERA_DEFAULT_PLACEHOLDER_PATTERN=__#__
CONTRACTERA_TIMEOUT=30
CONTRACTERA_RETRY_TIMES=2
CONTRACTERA_RETRY_SLEEP_MILLISECONDS=250
```

Local development usually uses:

```env
CONTRACTERA_BASE_URL=https://contractor.test
```

## Security model

Contractera uses two token types:

1. `Application token` - stored in the host backend and used for cross-account operations such as provisioning, updating, deleting and regenerating account tokens.
2. `Account token` - issued by Contractera for one Contractera account and used for template/document operations.

The host application must store account tokens encrypted and must proxy all frontend requests through its own backend.

## Provision an account

```php
use AntonioPrimera\ContracteraLaravelClient\ContracteraClient;

$contracteraAccount = app(ContracteraClient::class)->provisionAccount(
    externalAccountId: 'agrocity-account-'.$agrocityAccount->id,
    name: $agrocityAccount->name,
);

$agrocityAccount->forceFill([
    'contractera_account_id' => $contracteraAccount->id,
    'contractera_external_account_id' => $contracteraAccount->externalAccountId,
    'contractera_account_token' => $contracteraAccount->accountToken,
])->save();
```

Recommended host model casts:

```php
protected function casts(): array
{
    return [
        'contractera_account_token' => 'encrypted',
        'contractera_connected_at' => 'datetime',
        'contractera_disconnected_at' => 'datetime',
    ];
}
```

## Application-scoped operations

```php
$client = app(ContracteraClient::class);

$accounts = $client->listAccounts();

$account = $client->updateAccount($contracteraAccountId, [
    'name' => 'Updated account name',
]);

$account = $client->regenerateAccountToken($contracteraAccountId);

$client->deleteAccount($contracteraAccountId);
```

`deleteAccount()` marks the account for deletion in Contractera and immediately revokes account-scoped tokens.

## Account-scoped operations

Create an account-scoped client from the encrypted token stored in the host application:

```php
$accountClient = app(ContracteraClient::class)
    ->forAccountToken($agrocityAccount->contractera_account_token);
```

### List templates

```php
$templates = $accountClient->listTemplates();
```

### Upload template

```php
$template = $accountClient->uploadTemplate(
    name: 'Contract arenda',
    placeholderPattern: '__#__',
    file: $request->file('file'),
);
```

For a placeholder like `__OWNER_NAME__`, use pattern `__#__`. Contractera exposes key `OWNER_NAME`.

### Placeholder metadata

```php
$placeholders = $accountClient->listPlaceholders($templateId);

$updated = $accountClient->updatePlaceholders($templateId, [
    [
        'key' => 'OWNER_NAME',
        'label' => 'Nume proprietar',
        'input_type' => 'text',
        'required' => true,
        'help_text' => null,
        'display_order' => 1,
        'input_config' => [],
    ],
]);
```

Supported `input_type` values:

```txt
text
textarea
date
number
email
select
checkbox
rich_text
```

### Validate and preview

```php
$preview = $accountClient->validatePreview($templateId, [
    'OWNER_NAME' => 'Ion Popescu',
]);

if ($preview->valid) {
    echo $preview->html;
}
```

The host frontend should allow generation only after Contractera returns a valid preview response.

### Generate document

```php
$document = $accountClient->generateDocument(
    templateId: $templateId,
    values: [
        'OWNER_NAME' => 'Ion Popescu',
    ],
    format: 'docx',
);

$document->id;
$document->status;
$document->downloadUrls;
```

### Status and download

```php
$document = $accountClient->generatedDocument($documentId);

$response = $accountClient->downloadDocument($documentId, 'docx');

return response($response->body(), $response->status(), [
    'Content-Type' => $response->header('Content-Type'),
    'Content-Disposition' => $response->header('Content-Disposition'),
]);
```

The host application should expose local download URLs to the browser, not direct Contractera URLs.

## Frontend integration

The Vue SDK receives an adapter implemented by the host frontend. That adapter calls local backend routes, and those local backend routes use this Composer package.

Recommended local routes:

```http
GET /contractera/templates
POST /contractera/templates
GET /contractera/templates/{templateId}/placeholders
PATCH /contractera/templates/{templateId}/placeholders
POST /contractera/templates/{templateId}/validate-preview
POST /contractera/templates/{templateId}/generate
GET /contractera/generated-documents/{documentId}
GET /contractera/generated-documents/{documentId}/download?format=docx|pdf|html
```

## Host application testing

After installing this package in a Laravel host application, test both layers:

1. backend proxy tests with `Http::fake()`:
   - provisioning calls use the Application token;
   - template/document calls use the account token;
   - browser responses never contain Contractera tokens;
   - `validate-preview` converts frontend `values` to Contractera `replacements`;
   - generated document URLs returned to the browser are local host URLs;
2. frontend/browser checks with `@raprim/contractera-plugin-vue3`:
   - placeholder metadata loads and saves;
   - live preview updates after debounced form edits;
   - invalid input blocks generation;
   - generated documents download through the host backend;
   - mobile mode uses `mobile-mode="tab"` or another explicit mobile strategy.

The reference integration app is:

```txt
/Users/antonio/Workspace/workbench/contractera-integrator
```

Its Contractera integration tests are in:

```txt
tests/Feature/ContracteraIntegrationTest.php
```

## Testing

```bash
composer test
composer analyse
composer format
```

## Compatibility

Use this package with `@raprim/contractera-plugin-vue3` using the same major version once both packages are versioned.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
