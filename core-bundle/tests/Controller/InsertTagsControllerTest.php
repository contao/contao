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

use Contao\CoreBundle\Controller\InsertTagsController;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class InsertTagsControllerTest extends TestCase
{
    public function testRendersInsertTag(): void
    {
        $insertTagParser = $this->createMock(InsertTagParser::class);
        $insertTagParser
            ->method('replaceInline')
            ->with('{{request_token}}')
            ->willReturn('3858f62230ac3c915f300c664312c63f')
        ;

        $controller = new InsertTagsController($insertTagParser, $this->createMock(ContaoFramework::class), $this->createMock(RequestStack::class));
        $response = $controller->renderAction(new Request(), '{{request_token}}', null);

        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertNull($response->getMaxAge());
        $this->assertSame('3858f62230ac3c915f300c664312c63f', $response->getContent());
    }
}
