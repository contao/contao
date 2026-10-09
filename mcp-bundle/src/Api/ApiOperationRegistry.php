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
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ApiOperationRegistry
{
    public function __construct(
        private readonly ResourceNameCollectionFactoryInterface $resourceNameFactory,
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory,
        private readonly OpenApiFactoryInterface $openApiFactory,
        private readonly NormalizerInterface $normalizer,
    ) {
    }

    public function discover(string $query = ''): array
    {
        $operations = [];

        foreach ($this->getOperations() as $name => $operation) {
            $haystack = $name.' '.$operation->getShortName().' '.$operation->getDescription();

            if ('' !== $query && !str_contains(strtolower($haystack), strtolower($query))) {
                continue;
            }

            $operations[] = [
                'operation' => $name,
                'method' => $operation->getMethod(),
                'resource' => $operation->getShortName(),
                'description' => $operation->getDescription(),
            ];
        }

        return ['operations' => $operations];
    }

    public function describe(string $name): array
    {
        $operation = $this->getOperation($name);
        $document = $this->normalizer->normalize(($this->openApiFactory)(), 'json');

        if (!\is_array($document)) {
            throw new \LogicException('The OpenAPI document could not be normalized.');
        }

        [$path, $description] = $this->findDescription($document, $name);

        return [
            'operation' => $name,
            'resource' => $operation->getShortName(),
            'method' => $operation->getMethod(),
            'path' => $path,
            ...$this->resolveReferences($description, $document),
        ];
    }

    public function getOperation(string $name): HttpOperation
    {
        return $this->getOperations()[$name]
            ?? throw new \OutOfBoundsException(\sprintf('Unknown API operation "%s".', $name));
    }

    /**
     * @return array<string, HttpOperation>
     */
    private function getOperations(): array
    {
        $operations = [];

        foreach ($this->resourceNameFactory->create() as $resourceClass) {
            foreach ($this->resourceMetadataFactory->create($resourceClass) as $resource) {
                foreach ($resource->getOperations() ?? [] as $operation) {
                    if ($operation instanceof HttpOperation && \is_string($operation->getName())) {
                        if (null === $operation->getShortName() && null !== $resource->getShortName()) {
                            $operation = $operation->withShortName($resource->getShortName());
                        }

                        $operations[$operation->getName()] = $operation;
                    }
                }
            }
        }

        ksort($operations);

        return $operations;
    }

    private function findDescription(array $document, string $name): array
    {
        foreach ($document['paths'] ?? [] as $path => $pathItem) {
            foreach ($pathItem as $description) {
                if (\is_array($description) && $name === ($description['operationId'] ?? null)) {
                    unset($description['operationId']);

                    return [$path, $description];
                }
            }
        }

        throw new \LogicException(\sprintf('API operation "%s" is missing from the OpenAPI document.', $name));
    }

    private function resolveReferences(mixed $value, array $document, array $resolving = []): mixed
    {
        if (!\is_array($value)) {
            return $value;
        }

        if (\is_string($value['$ref'] ?? null) && str_starts_with($value['$ref'], '#/')) {
            $reference = $value['$ref'];

            if (isset($resolving[$reference])) {
                return ['type' => 'object'];
            }

            $resolved = $this->resolvePointer($document, $reference);
            unset($value['$ref']);

            return $this->resolveReferences([...$resolved, ...$value], $document, $resolving + [$reference => true]);
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->resolveReferences($item, $document, $resolving);
        }

        return $value;
    }

    private function resolvePointer(array $document, string $reference): array
    {
        $value = $document;

        foreach (explode('/', substr($reference, 2)) as $segment) {
            $segment = str_replace(['~1', '~0'], ['/', '~'], $segment);
            $value = $value[$segment] ?? throw new \LogicException(\sprintf('Unknown OpenAPI reference "%s".', $reference));
        }

        return \is_array($value) ? $value : throw new \LogicException(\sprintf('Invalid OpenAPI reference "%s".', $reference));
    }
}
