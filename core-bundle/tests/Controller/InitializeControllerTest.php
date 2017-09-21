<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Controller;

use Contao\CoreBundle\Controller\InitializeController;
use Contao\CoreBundle\Response\InitializeControllerResponse;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class InitializeControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $controller = new InitializeController();

        $this->assertInstanceOf('Contao\CoreBundle\Controller\InitializeController', $controller);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Custom entry points are deprecated and will no longer work in Contao 5.0.
     */
    public function testReturnsAResponseInTheIndexActionMethod(): void
    {
        if (!defined('TL_MODE')) {
            define('TL_MODE', 'BE');
        }

        if (!defined('TL_SCRIPT')) {
            define('TL_SCRIPT', 'index.php');
        }

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $container = new ContainerBuilder();
        $container->set('request_stack', $requestStack);
        $container->set('contao.framework', $this->mockContaoFramework());

        $controller = new InitializeController();
        $controller->setContainer($container);

        $this->assertInstanceOf(InitializeControllerResponse::class, $controller->indexAction());
    }
}
