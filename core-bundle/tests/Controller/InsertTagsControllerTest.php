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

use Contao\CoreBundle\Controller\InsertTagsController;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;

class InsertTagsControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $controller = new InsertTagsController($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CoreBundle\Controller\InsertTagsController', $controller);
    }

    public function testRendersNonCacheableInsertTag(): void
    {
        $adapter = $this->createMock(Adapter::class);

        $adapter
            ->method('__call')
            ->willReturnCallback(
                function (string $method): ?string {
                    if ('replace' === $method) {
                        return '3858f62230ac3c915f300c664312c63f';
                    }

                    return null;
                }
            )
        ;

        $controller = new InsertTagsController($this->mockFramework($adapter));
        $response = $controller->renderAction('{{request_token}}');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertSame('3858f62230ac3c915f300c664312c63f', $response->getContent());
    }

    /**
     * Mocks the Contao framework.
     *
     * @param Adapter $adapter
     *
     * @return ContaoFramework The object instance
     */
    private function mockFramework($adapter): ContaoFramework
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
