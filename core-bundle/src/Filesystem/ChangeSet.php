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

class ChangeSet
{
    public const ATTR_HASH = 'hash';
    public const ATTR_PATH = 'path';

    /**
     * @var array<int, array<string, string>>
     * @phpstan-var array<int, array<self::ATTR_*, string>>
     */
    private array $itemsToCreate;

    /**
     * @var array<string, array<string, string>>
     * @phpstan-var array<string, array<self::ATTR_*, string>>
     */
    private array $itemsToUpdate;

    /**
     * @var array<int, string>
     */
    private array $itemsToDelete;

    /**
     * @param array<int, array<string, string>>    $itemsToCreate
     * @param array<string, array<string, string>> $itemsToUpdate
     * @param array<int, string>                   $itemsToDelete
     * @phpstan-param array<int, array<self::ATTR_*, string>> $itemsToCreate
     * @phpstan-param array<string, array<self::ATTR_*, string>> $itemsToUpdate
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
     * @return array<int, array<string, string>>
     * @phpstan-return array<int, array<self::ATTR_*, string>>
     */
    public function getItemsToCreate(): array
    {
        return $this->itemsToCreate;
    }

    /**
     * @return array<string, array<string, string>>
     * @phpstan-return array<string, array<self::ATTR_*, string>>
     */
    public function getItemsToUpdate(): array
    {
        return $this->itemsToUpdate;
    }

    /**
     * @return array<int, string>
     */
    public function getItemsToDelete(): array
    {
        return $this->itemsToDelete;
    }
}
