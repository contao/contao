<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\CoreBundle\EventListener\CommandSchedulerListener;
use Contao\CoreBundle\Test\TestCase;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the CommandSchedulerListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class CommandSchedulerListenerTest extends TestCase
{
    /**
     * @var ContaoFramework|\PHPUnit_Framework_MockObject_MockObject
     */
    private $framework;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->framework = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\ContaoFramework')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->framework
            ->expects($this->any())
            ->method('getAdapter')
            ->willReturn($this->mockConfigAdapter())
        ;
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new CommandSchedulerListener($this->framework);

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\CommandSchedulerListener', $listener);
    }

    /**
     * Tests that the listener does nothing if the Contao framework is not booted.
     */
    public function testWithoutContaoFramework()
    {
        $this->framework
            ->expects($this->any())
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $this->framework
            ->expects($this->never())
            ->method('getAdapter')
        ;

        $listener = new CommandSchedulerListener($this->framework);
        $listener->onKernelTerminate();
    }

    /**
     * Tests that the listener does use the response if the Contao framework is booted.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testWithContaoFramework()
    {
        /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMock('Symfony\Component\DependencyInjection\Container', ['get']);

        $container
            ->expects($this->any())
            ->method('get')
            ->willReturn($this->mockContaoFramework())
        ;

        $this->framework
            ->expects($this->once())
            ->method('getAdapter')
        ;

        $this->framework
            ->expects($this->any())
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $listener = new CommandSchedulerListener($this->framework);
        $listener->setContainer($container);
        $listener->onKernelTerminate();
    }
}
