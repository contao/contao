<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\DataContainer;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\ApiBundle\Exception\InvalidInputException;
use Contao\ApiBundle\Relation\AbstractRelationResolver;
use Contao\CoreBundle\DataContainer\DcaHierarchy;
use Contao\CoreBundle\Doctrine\DBAL\ParentTraversalOptions;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends AbstractRelationResolver<DataContainerRelationDefinition>
 */
final class DataContainerRelationResolver extends AbstractRelationResolver
{
    /**
     * @var array<string, list<HttpOperation>>|null
     */
    private array|null $readOperations = null;

    public function __construct(
        private readonly Connection $connection,
        private readonly ResourceMetadataCollectionFactoryInterface $metadataFactory,
        private readonly RouterInterface $router,
        private readonly DcaHierarchy $dcaHierarchy,
    ) {
    }

    public function supports(object $relation): bool
    {
        return $relation instanceof DataContainerRelationDefinition && [] !== ($this->getReadOperations()[$relation->table] ?? []);
    }

    public function resolveRecordToIri(DataContainerRecord $record): string|null
    {
        return null === $record->id ? null : $this->createIri(new DataContainerRelationDefinition($record->table), $record->id);
    }

    protected function isEmptyValue(mixed $value): bool
    {
        return null === $value || '' === $value || 0 === $value || '0' === $value;
    }

    /**
     * @param DataContainerRelationDefinition $relation
     */
    protected function createIri(object $relation, mixed $identifier): string|null
    {
        $operations = $this->getReadOperations()[$relation->table] ?? [];

        if ('id' === $relation->field) {
            foreach ($operations as $operation) {
                if ([] === ($operation->getExtraProperties()['contao']['parents'] ?? [])) {
                    return $this->router->generate($operation->getRouteName() ?? $operation->getName(), ['id' => $identifier]);
                }
            }
        }

        $row = $this->findRow($relation->table, $relation->field, $identifier);

        if (null === $row || !isset($row['id'])) {
            return null;
        }

        foreach ($operations as $operation) {
            if (null !== ($parameters = $this->getRouteParameters($operation, $row))) {
                return $this->router->generate($operation->getRouteName() ?? $operation->getName(), $parameters);
            }
        }

        return null;
    }

    /**
     * @param DataContainerRelationDefinition $relation
     */
    protected function getIdentifier(object $relation, mixed $iri): mixed
    {
        $parameters = $this->matchIri($iri, $this->router);

        foreach ($this->getReadOperations()[$relation->table] ?? [] as $operation) {
            if (($operation->getRouteName() ?? $operation->getName()) !== ($parameters['_route'] ?? null)) {
                continue;
            }

            $id = $parameters['id'] ?? null;

            if ('id' === $relation->field) {
                return $id;
            }

            return $this->findRow($relation->table, 'id', $id)[$relation->field] ?? null;
        }

        throw new InvalidInputException('The relation IRI points to an unexpected API resource.');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findRow(string $table, string $field, mixed $value): array|null
    {
        $row = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->connection->quoteSingleIdentifier($table))
            ->where($this->connection->quoteSingleIdentifier($field).' = :value')
            ->setParameter('value', $value)
            ->executeQuery()
            ->fetchAssociative()
        ;

        return false === $row ? null : $row;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, int|string>|null
     */
    private function getRouteParameters(HttpOperation $operation, array $row): array|null
    {
        $parameters = ['id' => $row['id']];
        $parents = array_values(array_reverse($operation->getExtraProperties()['contao']['parents'] ?? []));
        $currentRow = $row;

        // Nested records need the path of their parent records
        if (null !== ($recursive = $operation->getExtraProperties()['contao']['recursive_parent'] ?? null)) {
            $rows = $this->dcaHierarchy->getParentRows($row['id'], $recursive['table'], new ParentTraversalOptions()->withColumns('ptable')->withBoundaryRow());
            $chain = array_reverse(array_column(\array_slice($rows, 1), 'id'));

            if ([] === $chain) {
                return null;
            }

            $parameters[$recursive['parameter']] = implode('/'.$recursive['segment'].'/', $chain);
            $currentRow = end($rows);
        }

        foreach ($parents as $index => $parent) {
            if (!\is_string($parent['table'] ?? null) || !\is_string($parent['parameter'] ?? null)) {
                return null;
            }

            if (isset($currentRow['ptable']) && $parent['table'] !== $currentRow['ptable']) {
                return null;
            }

            $parentId = $currentRow['pid'] ?? null;

            if (!\is_int($parentId) && !\is_string($parentId)) {
                return null;
            }

            $parameters[$parent['parameter']] = $parentId;

            if ($index < array_key_last($parents)) {
                $currentRow = $this->findRow($parent['table'], 'id', $parentId) ?? [];
            }
        }

        return $parameters;
    }

    /**
     * @return array<string, list<HttpOperation>>
     */
    private function getReadOperations(): array
    {
        if (null !== $this->readOperations) {
            return $this->readOperations;
        }

        $this->readOperations = [];

        foreach ($this->metadataFactory->create(DataContainerRecord::class) as $resource) {
            $table = $resource->getExtraProperties()['contao']['table'] ?? null;

            if (!\is_string($table)) {
                continue;
            }

            foreach ($resource->getOperations() ?? [] as $operation) {
                if ($operation instanceof Get) {
                    $this->readOperations[$table][] = $operation;
                }
            }
        }

        return $this->readOperations;
    }
}
