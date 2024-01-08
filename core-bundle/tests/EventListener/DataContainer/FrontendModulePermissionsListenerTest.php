<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\BackendUser;
use Contao\CoreBundle\EventListener\DataContainer\FrontendModulePermissionsListener;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Tests\TestCase;
use Contao\ModuleEventlist;
use Contao\ModuleNavigation;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;

class FrontendModulePermissionsListenerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        unset($GLOBALS['TL_DCA'], $GLOBALS['FE_MOD']);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA'], $GLOBALS['FE_MOD']);
        parent::tearDown();
    }

    public function testSetsDefaultTypeIfUserHasLimitedAccess(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 1, 'isAdmin' => false, 'frontendModules' => ['navigation']]);

        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn($user)
        ;

        $connection = $this->createMock(Connection::class);

        $GLOBALS['TL_DCA']['tl_module']['fields']['type']['sql']['default'] = 'text';

        $listener = new FrontendModulePermissionsListener($security, $connection);
        $listener->setDefaultType();

        $this->assertSame('navigation', $GLOBALS['TL_DCA']['tl_module']['fields']['type']['default']);
    }

    public function testGetFrontendModuleOptions(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 1, 'isAdmin' => false, 'frontendModules' => ['navigation']]);

        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn($user)
        ;

        $connection = $this->createMock(Connection::class);

        $GLOBALS['FE_MOD'] = ['events' => ['eventlist' => ModuleEventlist::class], 'navigationMenu' => ['navigation' => ModuleNavigation::class]];

        $listener = new FrontendModulePermissionsListener($security, $connection);

        $this->assertSame(['events' => ['eventlist'], 'navigationMenu' => ['navigation']], $listener->frontendModuleOptions());
    }

    public function testFilterFrontendModuleOptions(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 1, 'isAdmin' => false, 'frontendModules' => ['navigation']]);

        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn($user)
        ;

        $security
            ->expects($this->exactly(2))
            ->method('isGranted')
            ->withConsecutive(
                [ContaoCorePermissions::USER_CAN_ACCESS_FRONTEND_MODULE_TYPE, 'navigation'],
                [ContaoCorePermissions::USER_CAN_ACCESS_FRONTEND_MODULE_TYPE, 'html'],
            )
            ->willReturn(true, false)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAllAssociative')
            ->willReturn([
                ['id' => 1, 'name' => 'Main Navigation', 'type' => 'navigation', 'theme' => 'Default Theme'],
                ['id' => 2, 'name' => 'Footer', 'type' => 'html', 'theme' => 'Default Theme'],
            ])
        ;

        $listener = new FrontendModulePermissionsListener($security, $connection);
        $this->assertSame(
            [
                'Default Theme' => [
                    1 => 'Main Navigation (ID 1)',
                ],
            ],
            $listener->allowedFrontendModuleOptions(),
        );
    }
}
