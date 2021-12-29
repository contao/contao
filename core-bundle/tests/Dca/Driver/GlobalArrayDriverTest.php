<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Dca\Driver;

use Contao\Controller;
use Contao\CoreBundle\Dca\Driver\GlobalArrayDriver;
use Contao\CoreBundle\Dca\Driver\MutableDataDriverInterface;
use Contao\CoreBundle\Dca\Observer\DriverDataChangeDataCallbackObserver;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;

class GlobalArrayDriverTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_DCA']);
    }

    public function testLoadsTheDataContainerAndReadsItsData(): void
    {
        $source = [
            'foo' => 'bar',
            'baz' => [],
        ];

        $driver = $this->createDriverWithData('tl_bar', $source);

        $data = $driver->read('tl_bar');

        $this->assertSame($source, $data);
    }

    public function testHandlesAllResourcesWithDataContainerData(): void
    {
        $GLOBALS['TL_DCA']['tl_foo'] = ['foo' => 'bar'];
        $GLOBALS['TL_DCA']['tl_bar'] = [];
        $GLOBALS['TL_DCA']['tl_baz'] = new \stdClass();
        $GLOBALS['TL_DCA']['tl_bat'] = null;

        $controllerAdapter = $this->mockAdapter(['loadDataContainer']);
        $controllerAdapter
            ->expects($this->exactly(5))
            ->method('loadDataContainer')
            ->withConsecutive(['tl_foo'], ['tl_bar'], ['tl_baz'], ['tl_bat'], ['tl_qux'])
        ;

        $framework = $this->mockContaoFramework([
            Controller::class => $controllerAdapter,
        ]);

        $driver = new GlobalArrayDriver($framework);

        $this->assertTrue($driver->handles('tl_foo'));
        $this->assertTrue($driver->handles('tl_bar'), 'Driver should handle an empty array.');
        $this->assertFalse($driver->handles('tl_baz'), 'Driver should not handle a non-array value.');
        $this->assertFalse($driver->handles('tl_bat'), 'Driver should not handle a null value.');
        $this->assertFalse($driver->handles('tl_qux'), 'Driver should not handle an undefined data container.');
    }

    public function testDetectsChangesInGlobalArray(): void
    {
        $source = [
            'foo' => 'bar',
            'baz' => [],
        ];

        $driver = $this->createDriverWithData('tl_foo', $source, $this->any());

        $this->assertFalse($driver->hasChanged('tl_foo'), 'The data should not be flagged as changed before the first read.');

        $driver->read('tl_foo');

        $this->assertFalse($driver->hasChanged('tl_foo'), 'The data should not be flagged as changed after a read.');

        $GLOBALS['TL_DCA']['tl_foo']['foo'] = 'foo';

        $this->assertTrue($driver->hasChanged('tl_foo'));

        $GLOBALS['TL_DCA']['tl_bar']['foo'] = 'foo';

        $this->assertFalse($driver->hasChanged('tl_foo'), 'Resource should not be affected by data change of another resource.');
    }

    public function testIsMutableDataDriver(): void
    {
        $this->assertInstanceOf(MutableDataDriverInterface::class, new GlobalArrayDriver($this->mockContaoFramework()));
    }

    public function testReturnsDriverDataChangeDataCallbackObserver(): void
    {
        $driver = new GlobalArrayDriver($this->mockContaoFramework());

        $this->assertInstanceOf(DriverDataChangeDataCallbackObserver::class, $driver->getMutableDataObserver('tl_foo'));
    }

    private function createDriverWithData(string $resource, array $data, InvocationOrder|null $loadCalls = null): GlobalArrayDriver
    {
        $GLOBALS['TL_DCA'][$resource] = $data;

        $controllerAdapter = $this->mockAdapter(['loadDataContainer']);
        $controllerAdapter
            ->expects($loadCalls ?? $this->once())
            ->method('loadDataContainer')
            ->with($resource)
        ;

        $framework = $this->mockContaoFramework([
            Controller::class => $controllerAdapter,
        ]);

        return new GlobalArrayDriver($framework);
    }
}
