<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\Config;
use Contao\CoreBundle\Security\User\BackendUser;
use Contao\Database;
use Contao\Environment;
use Contao\System;
use Contao\TestCase\ContaoTestCase;

class BackendUserTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME']);

        $this->resetStaticProperties([
            BackendUser::class,
            Config::class,
            Database::class,
            Environment::class,
            System::class,
        ]);

        parent::tearDown();
    }

    public function testCreatesUserFromData(): void
    {
        $container = $this->getContainerWithContaoConfiguration();

        System::setContainer($container);

        $user = new BackendUser([
            'id' => 0,
            'username' => 'virtual-user',
            'admin' => false,
            'inherit' => 'group',
            'groups' => [],
            'showHelp' => true,
            'useRTE' => false,
            'useCE' => false,
            'doNotCollapse' => false,
            'thumbnails' => true,
        ]);

        $this->assertSame('virtual-user', $user->getUserIdentifier());
        $this->assertFalse($user->isAdmin);
        $this->assertSame([], $user->groups);
    }
}
