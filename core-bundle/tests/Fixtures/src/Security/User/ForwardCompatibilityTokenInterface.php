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

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @see https://github.com/sebastianbergmann/phpunit/issues/4732
 */
interface ForwardCompatibilityTokenInterface extends TokenInterface
{
    public function getUserIdentifier(): string;
}
