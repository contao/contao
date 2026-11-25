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
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\ApiBundle\Widget\RelationAwareWidgetConverterInterface;
use Contao\ApiBundle\Widget\WidgetConverterRegistry;
use Contao\CoreBundle\DataContainer\ForeignKeyParser;
use Contao\DataContainer as ContaoDataContainer;

final class DataContainerRelationDefinitionFactory
{
    public function __construct(
        private readonly ForeignKeyParser $foreignKeyParser,
        private readonly WidgetConverterRegistry $converters,
        private readonly ResourceMetadataCollectionFactoryInterface $metadataFactory,
    ) {
    }

    public function create(DataContainerFieldContext $field, array $row = []): object|null
    {
        return $this->getConfiguredRelation($field->config) ?? $this->getImplicitRelation($field, $row);
    }

    /**
     * @return iterable<object>
     */
    public function createForSchema(DataContainerFieldContext $field): iterable
    {
        if ($relation = $this->create($field)) {
            yield $relation;
        }

        if (!$this->isDynamicParentRelation($field)) {
            return;
        }

        foreach ($this->metadataFactory->create(DataContainerRecord::class) as $resource) {
            $table = $resource->getExtraProperties()['contao']['table'] ?? null;

            if (!\is_string($table) || '' === $table) {
                continue;
            }

            foreach ($resource->getOperations() ?? [] as $operation) {
                if ($operation instanceof Get) {
                    yield new DataContainerRelationDefinition($table);

                    break;
                }
            }
        }
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
