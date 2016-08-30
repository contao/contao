<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Controller;

use Contao\CoreBundle\Controller\EsiController;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the EsiController class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class EsiControllerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $controller = new EsiController($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CoreBundle\Controller\EsiController', $controller);
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
            ->getMock();
        $insertTagAdapter
            ->expects($this->any())
            ->method('replace')
            ->willReturn('3858f62230ac3c915f300c664312c63f');

        $controller = new EsiController($this->mockFramework($insertTagAdapter));
        $response = $controller->renderNonCacheableInsertTag('{{request_token}}');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('3858f62230ac3c915f300c664312c63f', $response->getContent());
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
    }

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
            ->getMock();

        $framework
            ->expects($this->once())
            ->method('initialize');

        $framework
            ->expects($this->any())
            ->method('createInstance')
            ->willReturn($adapter);

        return $framework;
    }
}
