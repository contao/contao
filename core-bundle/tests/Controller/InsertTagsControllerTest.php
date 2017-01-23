<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Controller;

use Contao\CoreBundle\Controller\InsertTagsController;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Test\TestCase;

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
    public function testInstantiation()
    {
        $controller = new InsertTagsController($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CoreBundle\Controller\InsertTagsController', $controller);
    }

    /**
     * Tests the renderNonCacheableInsertTag() action.
     */
    public function testRenderNonCacheableInsertTag()
    {
        $insertTagAdapter = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->setMethods(['replace'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $insertTagAdapter
            ->expects($this->any())
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
        $container = $this->mockContainerWithContaoScopes();

        /** @var ContaoFramework|\PHPUnit_Framework_MockObject_MockObject $framework */
        $framework = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\ContaoFramework')
            ->setConstructorArgs([
                $container->get('request_stack'),
                $this->mockRouter('/index.html'),
                $this->mockSession(),
                $this->getRootDir().'/app',
                error_reporting(),
            ])
            ->setMethods(['initialize', 'createInstance'])
            ->getMock()
        ;

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $framework
            ->expects($this->any())
            ->method('createInstance')
            ->willReturn($adapter)
        ;

        $framework->setContainer($container);

        return $framework;
    }
}
