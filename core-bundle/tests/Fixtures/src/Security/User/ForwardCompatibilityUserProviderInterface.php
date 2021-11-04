<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fixtures\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @see https://github.com/sebastianbergmann/phpunit/issues/4732
 */
interface ForwardCompatibilityUserProviderInterface extends UserProviderInterface
{
    public function loadUserByIdentifier(string $identifier): UserInterface;
}
