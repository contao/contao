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
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Contao\ApiBundle\Dto\DataContainerRecord;

/**
 * @implements ProcessorInterface<DataContainerRecord|mixed, DataContainerRecord|mixed>
 */
final class DataContainerStateProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof DataContainerRecord) {
            return $data;
        }

        $table = $this->getTable($operation);
        if (null === $table) {
            return $data;
        }

        if ($operation instanceof Post) {
            // TODO: insert a new record into $table from $data->data.
            // TODO: return the persisted record with its generated id.
            return $data;
        }

        if ($operation instanceof Patch) {
            // TODO: update the record in $table identified by $uriVariables['id'].
            // TODO: return the updated record.
            return $data;
        }

        if ($operation instanceof Delete) {
            // TODO: delete the record in $table identified by $uriVariables['id'].
            return null;
        }

        return $data;
    }

    private function getTable(Operation $operation): string|null
    {
        $table = $operation->getExtraProperties()['contao']['table'] ?? null;

        return \is_string($table) && '' !== $table ? $table : null;
    }
}
