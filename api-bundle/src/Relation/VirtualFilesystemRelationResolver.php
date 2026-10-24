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
use Contao\CoreBundle\Filesystem\Dbafs\UnableToResolveUuidException;
use Contao\CoreBundle\Filesystem\VirtualFilesystemRegistry;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends AbstractRelationResolver<VirtualFilesystemRelationDefinition>
 */
final class VirtualFilesystemRelationResolver extends AbstractRelationResolver
{
    /**
     * @var array<string, Get>|null
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
            && ($operation = $this->getReadOperation($relation->filesystem))
            && $this->filesystems->has($operation->getExtraProperties()['contao']['filesystem']);
    }

    /**
     * @param VirtualFilesystemRelationDefinition $relation
     */
    protected function createIri(object $relation, mixed $identifier): string|null
    {
        $operation = $this->getReadOperation($relation->filesystem);

        return $operation && \is_string($identifier) && Uuid::isValid($identifier)
            ? $this->router->generate($operation->getRouteName() ?? $operation->getName(), ['pathOrUuid' => $identifier])
            : null;
    }

    /**
     * @param VirtualFilesystemRelationDefinition $relation
     */
    protected function getIdentifier(object $relation, mixed $iri): string
    {
        $parameters = $this->matchIri($iri, $this->router);
        $operation = $this->getReadOperation($relation->filesystem);

        if (!$operation || ($operation->getRouteName() ?? $operation->getName()) !== ($parameters['_route'] ?? null)) {
            throw new UnprocessableEntityHttpException('The relation IRI points to an unexpected API resource.');
        }

        $path = $parameters['pathOrUuid'] ?? $parameters['path'] ?? null;

        if (!\is_string($path)) {
            throw new UnprocessableEntityHttpException('The relation IRI does not identify a registered file or directory.');
        }

        try {
            $location = Uuid::isValid($path) ? Uuid::fromString($path) : $path;
            $uuid = $this->filesystems->get($operation->getExtraProperties()['contao']['filesystem'])->get($location)?->getUuid();
        } catch (UnableToResolveUuidException $exception) {
            throw new UnprocessableEntityHttpException('The relation IRI does not identify a registered file or directory.', $exception);
        }

        if (!$uuid) {
            throw new UnprocessableEntityHttpException('The relation IRI does not identify a registered file or directory.');
        }

        return $uuid->toRfc4122();
    }

    private function getReadOperation(string $filesystem): Get|null
    {
        if (null !== $this->readOperations) {
            return $this->readOperations[$filesystem] ?? null;
        }

        $this->readOperations = [];

        foreach ($this->metadataFactory->create(VirtualFilesystemItem::class) as $resource) {
            foreach ($resource->getOperations() ?? [] as $operation) {
                $metadata = ($operation->getExtraProperties()['contao'] ?? []) + ($resource->getExtraProperties()['contao'] ?? []);
                $name = $metadata['relation_filesystem'] ?? $metadata['filesystem'] ?? null;

                if ($operation instanceof Get && \is_string($name)) {
                    $this->readOperations[$name] ??= $operation->withExtraProperties(['contao' => $metadata] + $operation->getExtraProperties());
                }
            }
        }

        return $this->readOperations[$filesystem] ?? null;
    }
}
