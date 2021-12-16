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
 * @phpstan-type UpdateItemDefinition array{hash?: string, path?: string, lastModified?: int|null}
 * @phpstan-type DeleteItemDefinition self::TYPE_*
 */
class ChangeSet
{
    public const ATTR_HASH = 'hash';
    public const ATTR_PATH = 'path';
    public const ATTR_TYPE = 'type';
    public const ATTR_LAST_MODIFIED = 'lastModified';

    public const TYPE_FILE = 0;
    public const TYPE_DIRECTORY = 1;

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
     * @var array<string, int|null>
     */
    private array $lastModifiedUpdates;

    /**
     * @param array<array<string, string|int>>         $itemsToCreate
     * @param array<string, array<string, string|int>> $itemsToUpdate
     * @param array<string, int>                       $itemsToDelete
     * @param array<string, int|null>                  $lastModifiedUpdates
     *
     * @phpstan-param array<CreateItemDefinition> $itemsToCreate
     * @phpstan-param array<string, UpdateItemDefinition> $itemsToUpdate
     * @phpstan-param array<string, DeleteItemDefinition> $itemsToDelete
     *
     * @internal
     */
    public function __construct(array $itemsToCreate, array $itemsToUpdate, array $itemsToDelete, array $lastModifiedUpdates = [])
    {
        $this->itemsToCreate = $itemsToCreate;
        $this->itemsToUpdate = $itemsToUpdate;
        $this->itemsToDelete = $itemsToDelete;

        $this->lastModifiedUpdates = $lastModifiedUpdates;
    }

    /**
     * Returns true if there are no changes.
     *
     * If $includeLastModified is set to true, changes to last modified
     * timestamps will be considered as well.
     */
    public function isEmpty(bool $includeLastModified = false): bool
    {
        $empty = empty($this->itemsToCreate) && empty($this->itemsToUpdate) && empty($this->itemsToDelete);

        if (!$includeLastModified) {
            return $empty;
        }

        return $empty && empty($this->lastModifiedUpdates);
    }

    /**
     * Returns a collection of definitions that describe new items that should
     * get created.
     *
     * @return array<array<string, string>>
     * @phpstan-return array<CreateItemDefinition>
     */
    public function getItemsToCreate(): array
    {
        return $this->itemsToCreate;
    }

    /**
     * Returns a list of definitions - each indexed by their existing path -
     * that describe changes that should be applied to items of those paths.
     *
     * If $includeLastModified is set to true, changes to last modified
     * timestamps will be included in the definitions.
     *
     * @return array<string, array<string, string|int>>
     * @phpstan-return array<string, UpdateItemDefinition>>
     */
    public function getItemsToUpdate(bool $includeLastModified = false): array
    {
        if (!$includeLastModified) {
            return $this->itemsToUpdate;
        }

        $lastModifiedUpdates = array_map(
            static fn (int $value): array => [self::ATTR_LAST_MODIFIED => $value],
            $this->lastModifiedUpdates
        );

        $itemsToUpdate = $this->itemsToUpdate;

        foreach ($itemsToUpdate as $path => &$definition) {
            if (null !== ($lastModifiedDefinition = $lastModifiedUpdates[$path] ?? null)) {
                $definition = array_merge($definition, $lastModifiedDefinition);
                unset($lastModifiedUpdates[$path]);
            }
        }

        return array_merge($lastModifiedUpdates, $itemsToUpdate);
    }

    /**
     * Returns a list of items to be deleted. Keys are paths, values the type
     * of resource (self::TYPE_FILE or self::TYPE_DIRECTORY).
     *
     * @return array<string, int>
     * @phpstan-return array<string, DeleteItemDefinition>
     */
    public function getItemsToDelete(): array
    {
        return $this->itemsToDelete;
    }

    /**
     * Returns a list of items where the last modified time should be updated.
     * Keys are paths, values the new timestamp.
     *
     * @return array<string, int|null> last modified timestamps indexed by path
     */
    public function getLastModifiedUpdates(): array
    {
        return $this->lastModifiedUpdates;
    }
}
