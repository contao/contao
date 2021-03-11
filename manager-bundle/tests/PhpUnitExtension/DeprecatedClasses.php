<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\PhpUnitExtension;

use Contao\CoreBundle\TestUtil\DeprecatedClassesExtension;
use Contao\ManagerBundle\Security\Logout\LogoutHandler;

class DeprecatedClasses extends DeprecatedClassesExtension
{
    private $failed = false;

    protected function deprecationProvider(): array
    {
        return [
            LogoutHandler::class => ['%s class implements "Symfony\Component\Security\Http\Logout\LogoutHandlerInterface" that is deprecated %s'],
        ];
    }
}
