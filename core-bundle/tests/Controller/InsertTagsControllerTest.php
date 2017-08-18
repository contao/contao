<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Controller;

use Contao\CoreBundle\Controller\InsertTagsController;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;

/**
 * Tests the InsertTagsController class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class InsertTagsControllerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $controller = new InsertTagsController($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CoreBundle\Controller\InsertTagsController', $controller);
    }

    /**
     * Tests the renderNonCacheableInsertTag() action.
     */
    public function testRenderNonCacheableInsertTag()
    {
        /** @var Adapter|\PHPUnit_Framework_MockObject_MockObject $insertTagAdapter */
        $insertTagAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['replace'])
            ->getMock()
        ;

        $insertTagAdapter
            ->method('replace')
            ->willReturn('3858f62230ac3c915f300c664312c63f')
        ;

        $controller = new InsertTagsController($this->mockFramework($insertTagAdapter));
        $response = $controller->renderAction('{{request_token}}');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertSame('3858f62230ac3c915f300c664312c63f', $response->getContent());
    }

    /**
     * Returns a ContaoFramework instance.
     *
     * @param Adapter $adapter
     *
     * @return ContaoFramework The object instance
     */
    private function mockFramework($adapter)
    {
        $framework = $this->createMock(ContaoFramework::class);

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $framework
            ->method('createInstance')
            ->willReturn($adapter)
        ;

        $framework->setContainer($this->mockContainerWithContaoScopes());

        return $framework;
    }
}
