<?php

namespace AntonioPrimera\ContracteraLaravelClient;

use AntonioPrimera\ContracteraLaravelClient\Data\GeneratedDocument;
use AntonioPrimera\ContracteraLaravelClient\Data\ValidationPreview;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class ContracteraAccountClient
{
    public function __construct(private readonly PendingRequest $request) {}

    public function listTemplates(): array
    {
        return $this->request
            ->get('/api/v1/templates')
            ->throw()
            ->json();
    }

    public function uploadTemplate(string $name, string $placeholderPattern, UploadedFile|string $file): array
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $filename = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($file);
        $fileHandle = fopen((string) $filePath, 'r');

        if ($fileHandle === false) {
            throw new RuntimeException('Template file could not be read.');
        }

        try {
            return $this->request
                ->asMultipart()
                ->post('/api/v1/templates', [
                    ['name' => 'name', 'contents' => $name],
                    ['name' => 'placeholder_pattern', 'contents' => $placeholderPattern],
                    [
                        'name' => 'file',
                        'contents' => $fileHandle,
                        'filename' => $filename,
                    ],
                ])
                ->throw()
                ->json();
        } finally {
            fclose($fileHandle);
        }
    }

    public function listPlaceholders(string $templateId): array
    {
        return $this->request
            ->get("/api/v1/templates/{$templateId}/placeholders")
            ->throw()
            ->json();
    }

    public function updatePlaceholders(string $templateId, array $placeholders): array
    {
        return $this->request
            ->patch("/api/v1/templates/{$templateId}/placeholders", [
                'placeholders' => $placeholders,
            ])
            ->throw()
            ->json();
    }

    public function validatePreview(string $templateId, array $values): ValidationPreview
    {
        $response = $this->request
            ->post("/api/v1/templates/{$templateId}/validate-preview", [
                'replacements' => $values,
            ])
            ->throw()
            ->json('data');

        return ValidationPreview::fromArray(is_array($response) ? $response : []);
    }

    public function generateDocument(string $templateId, array $values, string $format = 'docx'): GeneratedDocument
    {
        $response = $this->request
            ->post("/api/v1/templates/{$templateId}/generate", [
                'replacements' => $values,
                'format' => $format,
            ])
            ->throw()
            ->json('data');

        return GeneratedDocument::fromArray(is_array($response) ? $response : []);
    }

    public function generatedDocument(string $documentId): GeneratedDocument
    {
        $response = $this->request
            ->get("/api/v1/generated-documents/{$documentId}")
            ->throw()
            ->json('data');

        return GeneratedDocument::fromArray(is_array($response) ? $response : []);
    }

    public function downloadDocument(string $documentId, string $format = 'docx'): HttpResponse
    {
        return $this->request
            ->get("/api/v1/generated-documents/{$documentId}/download", [
                'format' => $format,
            ])
            ->throw();
    }
}
