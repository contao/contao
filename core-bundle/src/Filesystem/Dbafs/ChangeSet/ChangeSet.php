<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\Dbafs\ChangeSet;

use Symfony\Component\Filesystem\Path;

/**
 * @phpstan-type CreateItemDefinition array{hash: string, path: string, type: self::TYPE_*}
 * @phpstan-type UpdateItemDefinition array{hash?: string, path?: string, lastModified?: int|null}
 * @phpstan-type DeleteItemDefinition self::TYPE_*
 *
 * @experimental
 */
class ChangeSet
{
    final public const ATTR_HASH = 'hash';

    final public const ATTR_PATH = 'path';

    final public const ATTR_TYPE = 'type';

    final public const ATTR_LAST_MODIFIED = 'lastModified';

    final public const TYPE_FILE = 0;

    final public const TYPE_DIRECTORY = 1;

    /**
     * @param list<array<string, string|int>>              $itemsToCreate
     * @param array<string|int, array<string, string|int>> $itemsToUpdate
     * @param array<string|int, int>                       $itemsToDelete
     * @param array<string|int, int|null>                  $lastModifiedUpdates
     *
     * @phpstan-param list<CreateItemDefinition>             $itemsToCreate
     * @phpstan-param array<array-key, UpdateItemDefinition> $itemsToUpdate
     * @phpstan-param array<array-key, DeleteItemDefinition> $itemsToDelete
     * @phpstan-param array<array-key, int|null>             $lastModifiedUpdates
     *
     * @internal
     */
    public function __construct(
        private readonly array $itemsToCreate,
        private readonly array $itemsToUpdate,
        private readonly array $itemsToDelete,
        private readonly array $lastModifiedUpdates = [],
    ) {
    }

    /**
     * Returns a copy of this ChangeSet with another one appended. Optionally all
     * paths of the appended ChangeSet will be prefixed with $pathPrefix.
     */
    public function withOther(self $changeSet, string $pathPrefix = ''): self
    {
        $itemsToCreate = array_combine(array_column($this->itemsToCreate, self::ATTR_PATH), $this->itemsToCreate);
        $itemsToUpdate = $this->itemsToUpdate;
        $itemsToDelete = $this->itemsToDelete;
        $lastModifiedUpdates = $this->lastModifiedUpdates;

        foreach ($changeSet->itemsToCreate as $item) {
            $prefixedPath = Path::join($pathPrefix, $item[self::ATTR_PATH]);
            $itemsToCreate[$prefixedPath] = [...$item, self::ATTR_PATH => $prefixedPath];
        }

        foreach ($changeSet->itemsToUpdate as $path => $item) {
            $prefixedPath = Path::join($pathPrefix, (string) $path);

            if (null !== ($newPath = $item[self::ATTR_PATH] ?? null)) {
                $item = [...$item, self::ATTR_PATH => Path::join($pathPrefix, $newPath)];
            }

            $itemsToUpdate[$prefixedPath] = [...$itemsToUpdate[$prefixedPath] ?? [], ...$item];
        }

        foreach ($changeSet->itemsToDelete as $path => $type) {
            $itemsToDelete[Path::join($pathPrefix, (string) $path)] = $type;
        }

        foreach ($changeSet->lastModifiedUpdates as $path => $lastModified) {
            $lastModifiedUpdates[Path::join($pathPrefix, (string) $path)] = $lastModified;
        }

        return new self(array_values($itemsToCreate), $itemsToUpdate, $itemsToDelete, $lastModifiedUpdates);
    }

    public static function createEmpty(): self
    {
        return new self([], [], []);
    }

    /**
     * Returns true if there are no changes.
     *
     * If $includeLastModified is set to true, changes to last modified timestamps
     * will be considered as well.
     */
    public function isEmpty(bool $includeLastModified = false): bool
    {
        $empty = !$this->itemsToCreate && !$this->itemsToUpdate && !$this->itemsToDelete;

        if (!$includeLastModified) {
            return $empty;
        }

        return $empty && !$this->lastModifiedUpdates;
    }

    /**
     * Returns a list of new items that should get created.
     *
     * @return list<ItemToCreate>
     */
    public function getItemsToCreate(): array
    {
        return array_map(
            static fn (array $item): ItemToCreate => new ItemToCreate(
                $item['hash'],
                $item['path'],
                self::TYPE_FILE === $item['type'],
            ),
            $this->itemsToCreate,
        );
    }

    /**
     * Returns a list of changes that should be applied to existing items.
     *
     * If $includeLastModified is set to true, changes to last modified timestamps
     * will be included in the definitions.
     *
     * @return list<ItemToUpdate>
     */
    public function getItemsToUpdate(bool $includeLastModified = false): array
    {
        $lastModifiedUpdates = $this->lastModifiedUpdates;

        $items = array_map(
            static function (int|string $existingPath, array $item) use ($includeLastModified, &$lastModifiedUpdates) {
                $lastModified = $includeLastModified && \array_key_exists($existingPath, $lastModifiedUpdates)
                    ? $lastModifiedUpdates[$existingPath]
                    : false;

                unset($lastModifiedUpdates[$existingPath]);

                return new ItemToUpdate(
                    (string) $existingPath,
                    $item[self::ATTR_HASH] ?? null,
                    $item[self::ATTR_PATH] ?? null,
                    $lastModified,
                );
            },
            array_keys($this->itemsToUpdate),
            array_values($this->itemsToUpdate),
        );

        if (!$includeLastModified) {
            return $items;
        }

        return [...array_map(
            static fn (int|string $existingPath, int $lastModified) => new ItemToUpdate(
                (string) $existingPath,
                null,
                null,
                $lastModified,
            ),
            array_keys($lastModifiedUpdates),
            array_values($lastModifiedUpdates),
        ), ...$items];
    }

    /**
     * Returns a list of items that should be deleted.
     *
     * @return list<ItemToDelete>
     */
    public function getItemsToDelete(): array
    {
        return array_map(
            static fn (int|string $path, int $type): ItemToDelete => new ItemToDelete(
                (string) $path,
                self::TYPE_FILE === $type,
            ),
            array_keys($this->itemsToDelete),
            array_values($this->itemsToDelete),
        );
    }
}
