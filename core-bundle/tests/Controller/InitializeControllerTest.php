<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller;

use Contao\CoreBundle\Controller\InitializeController;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

class InitializeControllerTest extends TestCase
{
    /**
     * @group legacy
     *
     * @expectedDeprecation Custom entry points are deprecated and will no longer work in Contao 5.0.
     */
    public function testFailsIfTheRequestIsNotAMasterRequest(): void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getMasterRequest')
            ->willReturn(null)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('request_stack', $requestStack);

        $controller = new InitializeController();
        $controller->setContainer($container);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The request stack did not contain a master request.');

        $controller->indexAction();
    }
}
