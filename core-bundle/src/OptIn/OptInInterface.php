<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\OptIn;

use Contao\Model\Collection;

interface OptInInterface
{
    /**
     * Creates a new double opt-in token.
     */
    public function create(string $prefix, string $email, string $table, int $id): OptInTokenInterface;

    /**
     * Finds a double opt-in token by its identifier.
     */
    public function find(string $identifier): ?OptInTokenInterface;

    /**
     * Purges expired tokens.
     */
    public function purgeTokens(): void;

    /**
     * Delete a collection of related records together with their double opt-in tokens.
     */
    public function deleteWithRelatedRecord(Collection $models): void;
}
