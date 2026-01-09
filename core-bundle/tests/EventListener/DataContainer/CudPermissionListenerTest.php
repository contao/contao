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

use Contao\Controller;
use Contao\CoreBundle\Config\ResourceFinderInterface;
use Contao\CoreBundle\EventListener\DataContainer\CudPermissionListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DC_Folder;
use Contao\DC_Table;
use Symfony\Component\Finder\Finder;

class CudPermissionListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_DCA']);
    }

    public function testDoesNotAddDefaultPermissionsForUnknownDriver(): void
    {
        $GLOBALS['TL_DCA']['tl_foo']['config'] = [
            'dataContainer' => DC_Folder::class,
        ];

        $listener = new CudPermissionListener($this->createContaoFrameworkStub(), $this->createStub(ResourceFinderInterface::class));
        $listener->addDefaultPermissions('tl_foo');

        $this->assertArrayNotHasKey('permissions', $GLOBALS['TL_DCA']['tl_foo']['config']);
    }

    public function testDoesNotChangePermissionsIfKeyExists(): void
    {
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foo']['config'] = [
            'dataContainer' => DC_Table::class,
            'permissions' => ['foo'],
        ];

        $listener = new CudPermissionListener($this->createContaoFrameworkStub(), $this->createStub(ResourceFinderInterface::class));
        $listener->addDefaultPermissions('tl_foo');

        $this->assertSame(['foo'], $GLOBALS['TL_DCA']['tl_foo']['config']['permissions']);
    }

    public function testDoesNotAddPermissionsWithoutEditableFields(): void
    {
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'config' => [
                'dataContainer' => DC_Table::class,
            ],
            'fields' => [
                'foo' => [
                    // no inputType
                ],
            ],
        ];

        $listener = new CudPermissionListener($this->createContaoFrameworkStub(), $this->createStub(ResourceFinderInterface::class));
        $listener->addDefaultPermissions('tl_foo');

        $this->assertArrayNotHasKey('permissions', $GLOBALS['TL_DCA']['tl_foo']['config']);
    }

    public function testAddsDefaultPermissions(): void
    {
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'config' => [
                'dataContainer' => DC_Table::class,
            ],
            'fields' => [
                'foo' => [
                    'inputType' => 'text',
                ],
            ],
        ];

        $listener = new CudPermissionListener($this->createContaoFrameworkStub(), $this->createStub(ResourceFinderInterface::class));
        $listener->addDefaultPermissions('tl_foo');

        $this->assertSame(['create', 'update', 'delete'], $GLOBALS['TL_DCA']['tl_foo']['config']['permissions']);
    }

    public function testGetCudOptions(): void
    {
        /** @var CudPermissionListener|null $listener */
        $listener = null;

        $controllerAdapter = $this->createAdapterMock(['loadDataContainer']);
        $controllerAdapter
            ->expects($this->exactly(2))
            ->method('loadDataContainer')
            ->with($this->callback(
                static function (string $table) use (&$listener) {
                    $GLOBALS['TL_DCA'][$table] = [
                        'config' => ['dataContainer' => DC_Table::class],
                        'fields' => ['foo' => ['inputType' => 'text']],
                    ];

                    $listener->addDefaultPermissions($table);

                    return \in_array($table, ['tl_foo', 'tl_bar'], true);
                },
            ))
        ;

        $framework = $this->createContaoFrameworkStub([
            Controller::class => $controllerAdapter,
        ]);

        $file1 = $this->createMock(\SplFileInfo::class);
        $file1
            ->expects($this->exactly(3))
            ->method('getBasename')
            ->willReturn('tl_foo')
        ;

        $file2 = $this->createMock(\SplFileInfo::class);
        $file2
            ->expects($this->exactly(3))
            ->method('getBasename')
            ->willReturn('tl_bar')
        ;

        $finder = $this->createMock(Finder::class);
        $finder
            ->expects($this->once())
            ->method('depth')
            ->with(0)
            ->willReturnSelf()
        ;

        $finder
            ->expects($this->once())
            ->method('files')
            ->willReturnSelf()
        ;

        $finder
            ->expects($this->once())
            ->method('name')
            ->with('*.php')
            ->willReturnSelf()
        ;

        $finder
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$file1, $file2]))
        ;

        $resourceFinder = $this->createMock(ResourceFinderInterface::class);
        $resourceFinder
            ->expects($this->once())
            ->method('findIn')
            ->with('dca')
            ->willReturn($finder)
        ;

        $listener = new CudPermissionListener($framework, $resourceFinder);
        $options = $listener->getCudOptions();

        $this->assertSame(
            [
                'tl_foo' => ['create', 'update', 'delete'],
                'tl_bar' => ['create', 'update', 'delete'],
            ],
            $options,
        );
    }
}
