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
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use Contao\ApiBundle\DataContainer\DataContainerRecords;
use Contao\ApiBundle\Dto\DataContainerMcpRecord;
use Contao\ApiBundle\Dto\DataContainerRecord;

/**
 * @implements ProviderInterface<DataContainerMcpRecord|DataContainerRecord>
 */
final class DataContainerStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly DataContainerRecords $records,
        private readonly Pagination $pagination,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $table = $this->getTable($operation);
        if (null === $table) {
            return null;
        }

        if ($operation instanceof CollectionOperationInterface) {
            $context['filters'] = ($context['filters'] ?? []) + (($context['request'] ?? null)?->query->all() ?? []);
            [$page, , $itemsPerPage] = $this->pagination->getPagination($operation, $context);

            if ($itemsPerPage < 1) {
                throw new InvalidArgumentException('The itemsPerPage must be a positive integer.');
            }

            $parent = [
                'id' => $context['filters']['parent'] ?? ($context['request'] ?? null)?->query->get('parent'),
                'table' => $context['filters']['ptable'] ?? ($context['request'] ?? null)?->query->get('ptable'),
            ];

            return $this->records->list($table, $page, array_filter($parent, static fn ($value) => null !== $value), $itemsPerPage);
        }

        if ($operation instanceof HttpOperation && \in_array($operation->getMethod(), ['GET', 'PATCH', 'DELETE'], true)) {
            $id = $uriVariables['id'] ?? null;

            return null === $id ? null : $this->records->find($table, $id);
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

    private function getTable(Operation $operation): string|null
    {
        $table = $operation->getExtraProperties()['contao']['table'] ?? null;

        return \is_string($table) && '' !== $table ? $table : null;
    }
}
