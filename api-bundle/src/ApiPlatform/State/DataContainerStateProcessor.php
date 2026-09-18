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

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Contao\ApiBundle\DataContainer\DataContainerRecords;
use Contao\ApiBundle\Dto\DataContainerMcpRecord;
use Contao\ApiBundle\Dto\DataContainerMove;
use Contao\ApiBundle\Dto\DataContainerRecord;

/**
 * @implements ProcessorInterface<DataContainerRecord|DataContainerMcpRecord|mixed, DataContainerRecord|mixed>
 */
final class DataContainerStateProcessor implements ProcessorInterface
{
    public function __construct(private readonly DataContainerRecords $records)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $table = $this->getTable($operation);

        if (null === $table) {
            return $data;
        }

        if ($data instanceof DataContainerMove && 'move' === ($operation->getExtraProperties()['contao']['action'] ?? null)) {
            return $this->records->move($table, $uriVariables['id'], $data);
        }

        if (!$data instanceof DataContainerRecord && !$data instanceof DataContainerMcpRecord) {
            return $data;
        }

        if ($data instanceof DataContainerMcpRecord) {
            $data = DataContainerRecord::fromArray($table, $data->data, $data->id ?? $uriVariables['id'] ?? null);
        }

        if ($operation instanceof Delete || $this->hasMethod($operation, 'DELETE')) {
            $this->records->delete($data);

            return null;
        }

        if ($operation instanceof Post || $this->hasMethod($operation, 'POST')) {
            return $this->records->create($data);
        }

        if ($operation instanceof Patch || $this->hasMethod($operation, 'PATCH')) {
            return $this->records->update($data);
        }

        return $data;
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
