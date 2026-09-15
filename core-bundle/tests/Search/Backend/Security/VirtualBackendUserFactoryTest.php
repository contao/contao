<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Backend\Security;

use Contao\BackendUser;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Search\Backend\Security\VirtualBackendUserFactory;
use Contao\Database;
use Contao\Environment;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;

class VirtualBackendUserFactoryTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA'], $GLOBALS['TL_MIME'], $GLOBALS['TL_USERNAME']);

        $this->resetStaticProperties([
            BackendUser::class,
            Config::class,
            Database::class,
            Environment::class,
            System::class,
        ]);

        parent::tearDown();
    }

    public function testCreatesUsersFromDcaDefaultsAndCachesTheDefaults(): void
    {
        $controllerAdapter = $this->createAdapterMock(['loadDataContainer']);
        $controllerAdapter
            ->expects($this->once())
            ->method('loadDataContainer')
            ->with('tl_user')
            ->willReturnCallback(
                static function (): void {
                    $GLOBALS['TL_DCA']['tl_user']['fields'] = self::getDcaFields();
                },
            )
        ;

        $framework = $this->createContaoFrameworkMock([Controller::class => $controllerAdapter]);
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('database_connection', $this->createStub(Connection::class));
        System::setContainer($container);

        $factory = new VirtualBackendUserFactory($framework);
        $user = $factory->createForGroupId(42);

        $this->assertSame('__contao_backend_search_group_42', $user->getUserIdentifier());
        $this->assertSame([42], $user->groups);
        $this->assertSame('computed', $user->computed);
        $this->assertSame(['value'], $user->serialized);
        $this->assertFalse(isset($user->withoutDefault));

        $this->assertSame([43], $factory->createForGroupId(43)->groups);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function getDcaFields(): array
    {
        return [
            'showHelp' => ['default' => true],
            'useRTE' => ['default' => false],
            'useCE' => ['default' => false],
            'doNotCollapse' => ['default' => false],
            'thumbnails' => ['default' => true],
            'backendTheme' => ['default' => 'flexible'],
            'computed' => ['default' => static fn (): string => 'computed'],
            'serialized' => ['default' => ['value']],
            'withoutDefault' => [],
        ];
    }
}
