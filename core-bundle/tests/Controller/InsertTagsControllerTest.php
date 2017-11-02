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
        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $framework
            ->method('createInstance')
            ->willReturn($this->mockConfiguredAdapter(['replace' => '3858f62230ac3c915f300c664312c63f']))
        ;

        $controller = new InsertTagsController($framework);
        $response = $controller->renderAction('{{request_token}}');

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertSame('3858f62230ac3c915f300c664312c63f', $response->getContent());
    }
}
