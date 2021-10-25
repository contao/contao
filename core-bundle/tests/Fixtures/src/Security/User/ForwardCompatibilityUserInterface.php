<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Fixtures\Security\User;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Can't add methods to existing interface, see https://github.com/sebastianbergmann/phpunit/issues/4732
 */
interface ForwardCompatibilityUserInterface extends UserInterface
{
    public function getUserIdentifier(): string;
}
