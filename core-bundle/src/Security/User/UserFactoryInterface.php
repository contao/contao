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

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @template T of ContaoUser
 */
interface UserFactoryInterface
{
    /**
     * @param array<string, mixed> $data
     *
     * @return T
     */
    public function create(array $data): ContaoUser;

    /**
     * @param class-string<UserInterface> $className
     */
    public function supportsClass(string $className): bool;

    public function getTable(): string;
}
