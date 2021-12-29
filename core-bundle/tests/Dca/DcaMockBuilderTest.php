<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Dca;

use Contao\CoreBundle\Dca\Schema\CallbackCollection;
use Contao\CoreBundle\Dca\Schema\Config;
use Contao\CoreBundle\Dca\Schema\OperationsCollection;
use Contao\CoreBundle\Dca\Schema\Palettes;
use Contao\CoreBundle\Dca\Schema\Sorting;
use Contao\CoreBundle\Tests\TestCase;

class DcaMockBuilderTest extends TestCase
{
    public function testMocksDcaData(): void
    {
        $factory = $this->getDcaMockBuilder()
            ->addDcaData('tl_foo', [
                'config.closed' => true,
            ])
            ->addDcaData('tl_bar', [
                'config' => [
                    'closed' => true,
                ],
                'list.operations' => [
                    'edit' => [],
                ],
            ])
            ->getMock()
        ;

        $dcaFoo = $factory->get('tl_foo');
        $dcaBar = $factory->get('tl_bar');

        $this->assertTrue($dcaFoo->get('config.closed'));
        $this->assertTrue($dcaFoo->config()->isClosed());

        $this->assertTrue($dcaBar->get('config.closed'));
        $this->assertSame([], $dcaBar->get('list.operations.edit'));
    }

    public function testAddsSpiesToDcaNodes(): void
    {
        $factory = $this->getDcaMockBuilder()
            ->addDcaData('tl_foo', [
                'config.closed' => true,
                'list.sorting.child_record_callback' => [static function (): void {}],
            ])
            ->addSpies('tl_foo', [
                // Add a spy on config()->callback('onload')->call
                'config.callback[onload].call' => $this->atLeast(2),
                'list.sorting.childRecordCallback' => $this->exactly(1),

                // Notes:
                // It's not possible to spy on a child node of another spied node
                // So spying on 'config.usesVersioning' in addition to 'config.callback[onload].call' is not possible.
                // However, multiple spies on the same node or on multiple child nodes are supported.
            ])
            ->getMock()
        ;

        $dca = $factory->get('tl_foo');

        $dca->list()->sorting()->childRecordCallback();
        $dca->config()->callback('onload')->call();
        $dca->config()->callback('onload')->call();
    }

    public function testAddsMocksToDcaNodes(): void
    {
        $onloadCallbacksMock = $this->createMock(CallbackCollection::class);
        $oncutCallbacksMock = $this->createMock(CallbackCollection::class);
        $operationsMock = $this->createMock(OperationsCollection::class);
        $sortingMock = $this->createMock(Sorting::class);
        $palettesMock = $this->createMock(Palettes::class);

        $factory = $this->getDcaMockBuilder()
            ->addDcaData('tl_foo', [
                'config' => [
                    'bar' => 'baz',
                ],
                'list' => [
                    'foo' => 'bar',
                ],
            ])
            ->addMocks('tl_foo', [
                // Use [] syntax to set arguments for the method call
                // Caveat: There are no more proxied calls to the original method if the method is mocked with any parameter!
                'config.callback[onload]' => $onloadCallbacksMock,
                'config.callback[oncut]' => $oncutCallbacksMock,
                'list.operations' => $operationsMock,
                'list.sorting' => $sortingMock,
                'palettes' => $palettesMock,

                // Notes:
                // It's not possible to mock both a parent and a child node.
                // So mocking 'list' in addition to 'list.sorting' is not possible.
                // In this case, the 'list' mock would have to return the correct mock.
            ])
            ->getMock()
        ;

        $dca = $factory->get('tl_foo');

        $this->assertSame($onloadCallbacksMock, $dca->config()->callback('onload'), 'DCA did not return the correct mock object.');
        $this->assertSame($oncutCallbacksMock, $dca->config()->callback('oncut'), 'DCA did not return the correct mock object.');
        $this->assertSame($operationsMock, $dca->list()->operations(), 'DCA did not return the correct mock object.');
        $this->assertSame($sortingMock, $dca->list()->sorting(), 'DCA did not return the correct mock object.');
        $this->assertSame($palettesMock, $dca->palettes(), 'DCA did not return the correct mock object.');

        $this->assertSame('baz', $dca->config()->get('bar'), 'Shadow mock did not return the original value.');
        $this->assertSame('bar', $dca->list()->get('foo'), 'Shadow mock did not return the original value.');
    }

    public function testThrowsExceptionForCollidingSpiesAndMocks(): void
    {
        $configMock = $this->createMock(Config::class);
        $configMock
            ->expects($this->never())
            ->method('isClosed')
        ;

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('config.isClosed');

        $this->getDcaMockBuilder()
            ->addDcaData('tl_foo', [
                'config.closed' => true,
            ])
            ->addSpies('tl_foo', [
                'config.isClosed' => $this->exactly(1),
            ])
            ->addMocks('tl_foo', [
                'config' => $configMock,
            ])
            ->getMock()
        ;
    }
}
