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
use Contao\CoreBundle\Security\Logout\LogoutHandler;
use Contao\GdImage;
use Contao\TestCase\DeprecatedClassesPhpunitExtension;
use Symfony\Bundle\SecurityBundle\Security\LegacyLogoutHandlerListener;

final class DeprecatedClasses extends DeprecatedClassesPhpunitExtension
{
    protected function deprecationProvider(): array
    {
        $deprecations = [
            GdImage::class => ['%sUsing the "Contao\GdImage" class has been deprecated %s.'],
            ContaoLoginAuthenticationListener::class => ['%s"Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener" class is deprecated%s'],
            AuthenticationProvider::class => [
                '%s"Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider" class is deprecated%s',
                '%s"Symfony\Component\Security\Core\Authentication\Provider\UserAuthenticationProvider" class is deprecated%s',
                '%s"Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface" interface is deprecated%s',
            ],
        ];

        if (class_exists(LegacyLogoutHandlerListener::class)) {
            $deprecations[LogoutHandler::class] = ['%s class implements "Symfony\Component\Security\Http\Logout\LogoutHandlerInterface" that is deprecated %s'];
        }

        return $deprecations;
    }
}
