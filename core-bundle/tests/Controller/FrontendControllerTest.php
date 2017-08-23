<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Controller;

use Contao\CoreBundle\Controller\FrontendController;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Tests\TestCase;

/**
 * Tests the FrontendController class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FrontendControllerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $controller = new FrontendController();

        $this->assertInstanceOf('Contao\CoreBundle\Controller\FrontendController', $controller);
    }

    /**
     * Tests the controller actions.
     */
    public function testReturnsAResponseInTheActionMethods()
    {
        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $container = $this->mockKernel()->getContainer();
        $container->set('contao.framework', $framework);

        $controller = new FrontendController();
        $controller->setContainer($container);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $controller->indexAction());
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $controller->cronAction());
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $controller->shareAction());
    }
}
