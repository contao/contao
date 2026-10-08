<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\User;

/**
 * @template T of ContaoUser
 */
interface UserFactoryInterface
{
    /**
     * @param array<string, mixed> $data
     *
     * @return ContaoUser<T>
     */
    public function create(array $data): ContaoUser;

    /**
     * @param class-string<T> $className
     */
    public function supportsClass(string $className): bool;

    public function getTable(): string;
}
