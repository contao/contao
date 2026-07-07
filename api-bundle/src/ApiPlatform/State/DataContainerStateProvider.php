<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\ApiPlatform\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Contao\ApiBundle\Dto\DataContainerMcpRecord;
use Contao\ApiBundle\Dto\DataContainerRecord;

/**
 * @implements ProviderInterface<DataContainerMcpRecord|DataContainerRecord>
 */
final class DataContainerStateProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $table = $this->getTable($operation);
        if (null === $table) {
            return null;
        }

        if ($operation instanceof CollectionOperationInterface) {
            // TODO: load the records from $table and hydrate DataContainerRecord objects.
            return [];
        }

        if ($operation instanceof Get || $this->hasMethod($operation, 'GET')) {
            // TODO: load a single record from $table using $uriVariables['id'].
            // TODO: hydrate and return a DataContainerRecord.
            return new DataContainerRecord($table, [], $uriVariables['id'] ?? null);
        }

        $data = $context['mcp_data'] ?? null;

        if (!\is_array($data)) {
            return null;
        }

        return new DataContainerMcpRecord(
            \is_array($data['data'] ?? null) ? $data['data'] : [],
            \is_int($data['id'] ?? null) || \is_string($data['id'] ?? null) ? $data['id'] : null,
        );
    }

    private function hasMethod(Operation $operation, string $method): bool
    {
        return $operation instanceof HttpOperation && $method === $operation->getMethod();
    }

    private function getTable(Operation $operation): string|null
    {
        $table = $operation->getExtraProperties()['contao']['table'] ?? null;

        return \is_string($table) && '' !== $table ? $table : null;
    }
}
