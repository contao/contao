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
use Contao\ApiBundle\Widget\RelationAwareWidgetConverterInterface;
use Contao\ApiBundle\Widget\WidgetConverterRegistry;
use Contao\CoreBundle\DataContainer\DcaHierarchy;
use Contao\CoreBundle\DataContainer\ForeignKeyParser;
use Contao\CoreBundle\Doctrine\DBAL\ParentTraversalOptions;
use Contao\DataContainer as ContaoDataContainer;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

final class DataContainerRelationResolver
{
    /**
     * @var array<string, list<HttpOperation>>|null
     */
    private array|null $readOperations = null;

    public function __construct(
        private readonly Connection $connection,
        private readonly ForeignKeyParser $foreignKeyParser,
        private readonly WidgetConverterRegistry $converters,
        private readonly ResourceMetadataCollectionFactoryInterface $metadataFactory,
        private readonly RouterInterface $router,
        private readonly DcaHierarchy $dcaHierarchy,
    ) {
    }

    public function supports(DataContainerFieldContext $field): bool
    {
        return ($this->isDynamicParentRelation($field) && [] !== $this->getReadOperations()) || $this->getRelation($field);
    }

    private function getRelation(DataContainerFieldContext $field, array $row = []): DataContainerRelationDefinition|null
    {
        $relation = $this->getConfiguredRelation($field->config) ?? $this->getImplicitRelation($field, $row);

        return $relation instanceof DataContainerRelationDefinition && [] !== ($this->getReadOperations()[$relation->table] ?? []) ? $relation : null;
    }

    public function resolveToReference(mixed $value, DataContainerFieldContext $field, array $row = []): mixed
    {
        $relation = $this->getRelation($field, $row);

        if (!$relation) {
            return $value;
        }

        return $this->mapValue(
            $value,
            function (mixed $identifier) use ($relation): DataContainerRelationReference|null {
                if (!\is_int($identifier) && !\is_string($identifier)) {
                    return null;
                }

                $iri = $this->createIri($relation, $identifier);

                return null === $iri ? null : new DataContainerRelationReference($identifier, $iri);
            },
        );
    }

    public function resolveToIdentifier(mixed $value, DataContainerFieldContext $field): mixed
    {
        $relation = $this->getRelation($field);

        if (!$relation) {
            return $value;
        }

        return $this->mapValue($value, fn (mixed $reference): mixed => $this->getIdentifier($relation, $this->getReferenceIri($reference)));
    }

    public function resolveRecordToIri(DataContainerRecord $record): string|null
    {
        return null === $record->id ? null : $this->createIri(new DataContainerRelationDefinition($record->table), $record->id);
    }

    private function mapValue(mixed $value, callable $callback): mixed
    {
        if (\is_array($value) && !$this->isReference($value)) {
            return array_map(fn (mixed $item): mixed => $this->mapValue($item, $callback), $value);
        }

        if (null === $value || '' === $value || 0 === $value || '0' === $value) {
            return null;
        }

        return $callback($value);
    }

    private function isReference(array $value): bool
    {
        return \array_key_exists('iri', $value) || \array_key_exists('@id', $value);
    }

    private function getReferenceIri(mixed $value): mixed
    {
        if ($value instanceof DataContainerRelationReference) {
            return $value->iri;
        }

        if (\is_array($value)) {
            return $value['iri'] ?? $value['@id'] ?? null;
        }

        // Also accept JSON-LD's compact IRI representation
        return $value;
    }

    private function createIri(DataContainerRelationDefinition $relation, mixed $identifier): string|null
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

    private function getIdentifier(DataContainerRelationDefinition $relation, mixed $iri): mixed
    {
        if (!\is_string($iri) || '' === $iri || false === ($path = parse_url($iri, PHP_URL_PATH))) {
            throw new UnprocessableEntityHttpException('Relation values must be valid IRIs.');
        }

        // An IRI identifies the resource to read, regardless of the current request method
        $context = $this->router->getContext();
        $method = $context->getMethod();
        $context->setMethod('GET');

        try {
            $parameters = $this->router->match($path);
        } catch (RoutingExceptionInterface) {
            throw new UnprocessableEntityHttpException('The relation IRI does not match an API resource.');
        } finally {
            $context->setMethod($method);
        }

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

        throw new UnprocessableEntityHttpException('The relation IRI points to an unexpected API resource.');
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

    private function getConfiguredRelation(array $config): object|null
    {
        $converter = $this->converters->get($config);

        if ($converter instanceof RelationAwareWidgetConverterInterface && $relation = $converter->getRelation($config)) {
            return $relation;
        }

        if (!\is_array($config['relation'] ?? null)) {
            return null;
        }

        $table = $config['relation']['table'] ?? null;

        if (!\is_string($table) && \is_string($config['foreignKey'] ?? null)) {
            $table = $this->foreignKeyParser->parse($config['foreignKey'])->getTableName();
        }

        if (!\is_string($table) || '' === $table) {
            return null;
        }

        $field = $config['relation']['field'] ?? 'id';

        return \is_string($field) && '' !== $field ? new DataContainerRelationDefinition($table, $field) : null;
    }

    private function getImplicitRelation(DataContainerFieldContext $field, array $row): DataContainerRelationDefinition|null
    {
        if (null === $field->table) {
            return null;
        }

        if ('pid' !== $field->name) {
            return null;
        }

        $dca = $GLOBALS['TL_DCA'][$field->table] ?? [];
        $parentTable = $dca['config']['dynamicPtable'] ?? false ? ($row['ptable'] ?? null) : ($dca['config']['ptable'] ?? null);

        if (\is_string($parentTable) && '' !== $parentTable) {
            return new DataContainerRelationDefinition($parentTable);
        }

        if (ContaoDataContainer::MODE_TREE === ($dca['list']['sorting']['mode'] ?? null)) {
            return new DataContainerRelationDefinition($field->table);
        }

        return null;
    }

    private function isDynamicParentRelation(DataContainerFieldContext $field): bool
    {
        return 'pid' === $field->name
            && null !== $field->table
            && true === ($GLOBALS['TL_DCA'][$field->table]['config']['dynamicPtable'] ?? false);
    }
}
