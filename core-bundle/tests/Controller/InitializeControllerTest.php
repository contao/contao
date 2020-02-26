<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller;

use Contao\CoreBundle\Controller\InitializeController;
use Contao\CoreBundle\Response\InitializeControllerResponse;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Tests the InitializeController class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InitializeControllerTest extends TestCase
{
    /**
     * Tests the indexAction() method.
     *
     * @group legacy
     *
     * @expectedDeprecation Custom entry points are deprecated and will no longer work in Contao 5.0.
     */
    public function testReturnsAResponseInTheIndexActionMethod()
    {
        if (!\defined('TL_MODE')) {
            \define('TL_MODE', 'BE');
        }

        if (!\defined('TL_SCRIPT')) {
            \define('TL_SCRIPT', 'index.php');
        }

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $container = new ContainerBuilder();
        $container->set('request_stack', $requestStack);
        $container->set('contao.framework', $this->mockContaoFramework());
        $container->set('event_dispatcher', $this->createMock(EventDispatcherInterface::class));
        $container->set('http_kernel', $this->createMock(HttpKernelInterface::class));
        $container->set('kernel', $this->createMock(KernelInterface::class));

        $controller = new InitializeController();
        $controller->setContainer($container);

        $this->assertInstanceOf(InitializeControllerResponse::class, $controller->indexAction());
    }
}
