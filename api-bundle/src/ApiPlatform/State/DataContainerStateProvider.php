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

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Contao\ApiBundle\Dto\DataContainerRecord;

/**
 * @implements ProviderInterface<DataContainerRecord>
 */
final class DataContainerStateProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $table = $this->getTable($operation);
        if (null === $table) {
            return null;
        }

        if ($operation instanceof GetCollection) {
            // TODO: load the records from $table and hydrate DataContainerRecord objects.
            return [];
        }

        if ($operation instanceof Get) {
            // TODO: load a single record from $table using $uriVariables['id'].
            // TODO: hydrate and return a DataContainerRecord.
            return new DataContainerRecord($table, [], $uriVariables['id'] ?? null);
        }

        return null;
    }

    private function getTable(Operation $operation): string|null
    {
        $table = $operation->getExtraProperties()['contao']['table'] ?? null;

        return \is_string($table) && '' !== $table ? $table : null;
    }
}
