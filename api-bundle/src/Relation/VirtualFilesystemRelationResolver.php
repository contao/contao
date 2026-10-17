<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Relation;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Contao\ApiBundle\Dto\VirtualFilesystemItem;
use Contao\ApiBundle\Dto\VirtualFilesystemLocation;
use Contao\CoreBundle\Filesystem\Dbafs\UnableToResolveUuidException;
use Contao\CoreBundle\Filesystem\VirtualFilesystemRegistry;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends AbstractRelationResolver<VirtualFilesystemRelationDefinition>
 */
final class VirtualFilesystemRelationResolver extends AbstractRelationResolver
{
    /**
     * @var array<string, array<string, Get>>|null
     */
    private array|null $readOperations = null;

    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $metadataFactory,
        private readonly RouterInterface $router,
        private readonly VirtualFilesystemRegistry $filesystems,
    ) {
    }

    public function supports(object $relation): bool
    {
        return $relation instanceof VirtualFilesystemRelationDefinition
            && ($operation = $this->getReadOperation($relation))
            && $this->filesystems->has($operation->getExtraProperties()['contao']['filesystem']);
    }

    /**
     * @param VirtualFilesystemRelationDefinition $relation
     */
    protected function createIri(object $relation, mixed $identifier): string|null
    {
        $operation = $this->getReadOperation($relation);

        if (!$operation || !\is_string($identifier) || '' === $identifier) {
            return null;
        }

        if (VirtualFilesystemRelationDefinition::UUID === $relation->identifier) {
            if (!Uuid::isValid($identifier)) {
                return null;
            }

            $identifier = Uuid::fromString($identifier)->toRfc4122();
        }

        return $this->router->generate($operation->getRouteName() ?? $operation->getName(), [$relation->identifier => $identifier]);
    }

    /**
     * @param VirtualFilesystemRelationDefinition $relation
     */
    protected function getIdentifier(object $relation, mixed $iri): string
    {
        $parameters = $this->matchIri($iri, $this->router);
        $operation = $this->getReadOperations($relation->filesystem)[$parameters['_route'] ?? ''] ?? null;

        if (!$operation) {
            throw new UnprocessableEntityHttpException('The relation IRI points to an unexpected API resource.');
        }

        try {
            $location = VirtualFilesystemLocation::fromArray(array_intersect_key($parameters, ['path' => true, 'uuid' => true]))->toLocation();
            $item = $this->filesystems->get($operation->getExtraProperties()['contao']['filesystem'])->get($location);
        } catch (BadRequestHttpException|UnableToResolveUuidException $exception) {
            throw new UnprocessableEntityHttpException('The relation IRI does not identify an existing file or directory.', $exception);
        }

        $identifier = VirtualFilesystemRelationDefinition::PATH === $relation->identifier ? $item?->getPath() : $item?->getUuid()?->toRfc4122();

        if (null === $identifier) {
            throw new UnprocessableEntityHttpException(\sprintf('The relation IRI does not identify a file or directory with a %s.', $relation->identifier));
        }

        return $identifier;
    }

    private function getReadOperation(VirtualFilesystemRelationDefinition $relation): Get|null
    {
        foreach ($this->getReadOperations($relation->filesystem) as $operation) {
            if (\array_key_exists($relation->identifier, $operation->getUriVariables() ?? [])) {
                return $operation;
            }
        }

        return null;
    }

    /**
     * @return array<string, Get>
     */
    private function getReadOperations(string $filesystem): array
    {
        if (null !== $this->readOperations) {
            return $this->readOperations[$filesystem] ?? [];
        }

        $this->readOperations = [];

        foreach ($this->metadataFactory->create(VirtualFilesystemItem::class) as $resource) {
            foreach ($resource->getOperations() ?? [] as $operation) {
                $metadata = ($operation->getExtraProperties()['contao'] ?? []) + ($resource->getExtraProperties()['contao'] ?? []);
                $name = $metadata['relation_filesystem'] ?? $metadata['filesystem'] ?? null;

                if ($operation instanceof Get && \is_string($name)) {
                    $this->readOperations[$name][$operation->getRouteName() ?? $operation->getName()] = $operation->withExtraProperties(['contao' => $metadata] + $operation->getExtraProperties());
                }
            }
        }

        return $this->readOperations[$filesystem] ?? [];
    }
}
