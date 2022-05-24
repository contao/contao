<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\PhpunitExtension;

use Contao\CoreBundle\Security\Authentication\ContaoLoginAuthenticationListener;
use Contao\CoreBundle\Security\Authentication\Provider\AuthenticationProvider;
use Contao\TestCase\DeprecatedClassesPhpunitExtension;

final class DeprecatedClasses extends DeprecatedClassesPhpunitExtension
{
    protected function deprecationProvider(): array
    {
        return [
            ContaoLoginAuthenticationListener::class => ['%s"Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener" class is deprecated%s'],
            AuthenticationProvider::class => [
                '%s"Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider" class is deprecated%s',
                '%s"Symfony\Component\Security\Core\Authentication\Provider\UserAuthenticationProvider" class is deprecated%s',
                '%s"Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface" interface is deprecated%s',
            ],
        ];
    }
}
