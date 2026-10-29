<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Api;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Contao\CoreBundle\File\UploadSizeProvider;
use Mcp\Exception\ToolCallException;

final class BinaryPayloadHandler
{
    public function __construct(
        private readonly UploadSizeProvider $uploadSizeProvider,
        private readonly int|null $maximumMcpPayloadSize = null,
    ) {
    }

    public function decode(HttpOperation $operation, mixed $data): mixed
    {
        if (null === $data || null === $contentType = $this->getBinaryContentType($operation)) {
            return $data;
        }

        if (!\is_string($data)) {
            throw new ToolCallException(\sprintf('The "%s" payload must be a base64-encoded string.', $contentType));
        }

        $maximumSize = $this->getMaximumSize($operation, $contentType);

        if (\strlen($data) > $this->getMaximumEncodedLength($maximumSize)) {
            throw $this->createSizeException($maximumSize);
        }

        $decoded = base64_decode($data, true);

        if (false === $decoded) {
            throw new ToolCallException('The binary payload is not valid base64.');
        }

        if (\strlen($decoded) > $maximumSize) {
            throw $this->createSizeException($maximumSize);
        }

        return $decoded;
    }

    public function describe(HttpOperation $operation): array|null
    {
        if (null === $contentType = $this->getBinaryContentType($operation)) {
            return null;
        }

        $maximumSize = $this->getMaximumSize($operation, $contentType);

        return [
            'contentType' => $contentType,
            'encoding' => 'base64',
            'maxDecodedSize' => $maximumSize,
            'maxEncodedLength' => $this->getMaximumEncodedLength($maximumSize),
            'supported' => true,
        ];
    }

    private function getBinaryContentType(HttpOperation $operation): string|null
    {
        $openApi = $operation->getOpenapi();

        if (!$openApi instanceof OpenApiOperation) {
            return null;
        }

        foreach ($openApi->getRequestBody()?->getContent() ?? [] as $contentType => $mediaType) {
            if (!$mediaType instanceof MediaType) {
                continue;
            }

            $schema = $mediaType->getSchema();

            if ('string' === ($schema['type'] ?? null) && 'binary' === ($schema['format'] ?? null)) {
                return $contentType;
            }
        }

        return null;
    }

    private function getMaximumSize(HttpOperation $operation, string $contentType): int
    {
        $maximumSize = $this->uploadSizeProvider->getMaximumUploadSize();
        $operationMaximum = $this->getOperationMaximumSize($operation, $contentType);

        if (null !== $operationMaximum) {
            $maximumSize = min($maximumSize, $operationMaximum);
        }

        return null === $this->maximumMcpPayloadSize ? $maximumSize : min($maximumSize, $this->maximumMcpPayloadSize);
    }

    private function getOperationMaximumSize(HttpOperation $operation, string $contentType): int|null
    {
        $openApi = $operation->getOpenapi();

        if (!$openApi instanceof OpenApiOperation) {
            return null;
        }

        $content = $openApi->getRequestBody()?->getContent();
        $mediaType = $content[$contentType] ?? null;
        $schema = $mediaType instanceof MediaType ? $mediaType->getSchema() : null;
        $maximumSize = $schema['maxLength'] ?? null;

        return \is_int($maximumSize) && $maximumSize > 0 ? $maximumSize : null;
    }

    private function getMaximumEncodedLength(int $maximumSize): int
    {
        return 4 * intdiv($maximumSize + 2, 3);
    }

    private function createSizeException(int $maximumSize): ToolCallException
    {
        return new ToolCallException(\sprintf('The decoded binary payload must not exceed %d bytes.', $maximumSize));
    }
}
