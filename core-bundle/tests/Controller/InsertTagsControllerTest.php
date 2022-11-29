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
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;

class InsertTagsControllerTest extends TestCase
{
    public function testRendersNonCacheableInsertTag(): void
    {
        $insertTagParser = $this->createMock(InsertTagParser::class);
        $insertTagParser
            ->method('replaceInline')
            ->with('{{request_token}}')
            ->willReturn('3858f62230ac3c915f300c664312c63f')
        ;

        $controller = new InsertTagsController($insertTagParser);
        $response = $controller->renderAction(new Request(), '{{request_token}}');

        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertNull($response->getMaxAge());
        $this->assertSame('3858f62230ac3c915f300c664312c63f', $response->getContent());

        $request = new Request();
        $request->query->set('clientCache', '300');

        $controller = new InsertTagsController($insertTagParser);
        $response = $controller->renderAction($request, '{{request_token}}');

        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertFalse($response->headers->hasCacheControlDirective('no-store'));
        $this->assertSame(300, $response->getMaxAge());
        $this->assertSame('3858f62230ac3c915f300c664312c63f', $response->getContent());
    }

    public function testSpecialDateInsertTagHandling(): void
    {
        $year = date('Y');

        $insertTagParser = $this->createMock(InsertTagParser::class);
        $insertTagParser
            ->method('replaceInline')
            ->with('{{date::Y}}')
            ->willReturn($year)
        ;

        $controller = new InsertTagsController($insertTagParser);
        $response = $controller->renderAction(new Request(), '{{date::Y}}');

        $this->assertTrue($response->headers->hasCacheControlDirective('public'));
        $this->assertSame((new \DateTimeImmutable($year.'-12-31 23:59:59'))->getTimestamp(), $response->getExpires()->getTimestamp());
        $this->assertSame($year, $response->getContent());
    }
}
