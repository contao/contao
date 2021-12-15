<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem;

/**
 * @phpstan-type CreateItemDefinition array{hash: string, path: string, type: self::TYPE_*}
 * @phpstan-type UpdateItemDefinition array{hash: string}|array{path: string}|array{hash: string, path: string}
 * @phpstan-type DeleteItemDefinition self::TYPE_*
 */
class ChangeSet
{
    public const ATTR_HASH = 'hash';
    public const ATTR_PATH = 'path';
    public const ATTR_TYPE = 'type';

    public const TYPE_FILE = 0;
    public const TYPE_FOLDER = 1;

    /**
     * @var array<array<string, string|int>>
     * @phpstan-var array<CreateItemDefinition>
     */
    private array $itemsToCreate;

    /**
     * @var array<string, array<string, string>>
     * @phpstan-var array<string, UpdateItemDefinition>
     */
    private array $itemsToUpdate;

    /**
     * @var array<string, int>
     * @phpstan-var array<string, self::TYPE_*>
     */
    private array $itemsToDelete;

    /**
     * @param array<array<string, string|int>>         $itemsToCreate
     * @param array<string, array<string, string|int>> $itemsToUpdate
     * @param array<string, int>                       $itemsToDelete
     * @phpstan-param array<CreateItemDefinition> $itemsToCreate
     * @phpstan-param array<string, UpdateItemDefinition> $itemsToUpdate
     * @phpstan-param array<string, DeleteItemDefinition> $itemsToDelete
     *
     * @internal
     */
    public function __construct(array $itemsToCreate, array $itemsToUpdate, array $itemsToDelete)
    {
        $this->itemsToCreate = $itemsToCreate;
        $this->itemsToUpdate = $itemsToUpdate;
        $this->itemsToDelete = $itemsToDelete;
    }

    public function isEmpty(): bool
    {
        return empty($this->itemsToCreate) && empty($this->itemsToUpdate) && empty($this->itemsToDelete);
    }

    /**
     * @return array<array<string, string>>
     * @phpstan-return array<CreateItemDefinition>
     */
    public function getItemsToCreate(): array
    {
        return $this->itemsToCreate;
    }

    /**
     * @return array<string, array<string, string|int>>
     * @phpstan-return array<string, UpdateItemDefinition>>
     */
    public function getItemsToUpdate(): array
    {
        return $this->itemsToUpdate;
    }

    /**
     * @return array<string, int>
     * @phpstan-return array<string, DeleteItemDefinition>
     */
    public function getItemsToDelete(): array
    {
        return $this->itemsToDelete;
    }
}
