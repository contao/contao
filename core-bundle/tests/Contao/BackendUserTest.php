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

use Contao\BackendUser;
use Contao\Config;
use Contao\Database;
use Contao\Environment;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;

class BackendUserTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME'], $GLOBALS['TL_USERNAME']);

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
        $container->set('database_connection', $this->createStub(Connection::class));

        System::setContainer($container);

        $user = BackendUser::createFromData([
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
            'backendTheme' => 'flexible',
        ]);

        $this->assertSame('virtual-user', $user->getUserIdentifier());
        $this->assertFalse($user->isAdmin);
        $this->assertSame([], $user->groups);
    }
}
