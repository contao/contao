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
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class InsertTagsControllerTest extends TestCase
{
    public function testRendersNonCacheableInsertTag(): void
    {
        $insertTagParser = $this->createMock(InsertTagParser::class);
        $insertTagParser
            ->method('renderTag')
            ->with('request_token')
            ->willReturn(new InsertTagResult('3858f62230ac3c915f300c664312c63f'))
        ;

        $controller = new InsertTagsController($insertTagParser, null);
        $response = $controller->renderAction(new Request(), '{{request_token}}');

        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertNull($response->getMaxAge());
        $this->assertSame('3858f62230ac3c915f300c664312c63f', $response->getContent());

        $request = new Request();
        $request->query->set('clientCache', '300');

        $controller = new InsertTagsController($insertTagParser, null);
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
            ->method('renderTag')
            ->with('date::Y')
            ->willReturn(new InsertTagResult($year, OutputType::text, new \DateTimeImmutable($year.'-12-31 23:59:59')))
        ;

        $controller = new InsertTagsController($insertTagParser, null);
        $response = $controller->renderAction(new Request(), '{{date::Y}}');

        $this->assertTrue($response->headers->hasCacheControlDirective('public'));
        $this->assertFalse($response->headers->hasCacheControlDirective('no-store'));
        $this->assertSame((new \DateTimeImmutable($year.'-12-31 23:59:59'))->getTimestamp(), $response->getExpires()->getTimestamp());
        $this->assertSame($year, $response->getContent());
    }

    public function testInvalidTagsThrowsBadRequestException(): void
    {
        $controller = new InsertTagsController($this->createMock(InsertTagParser::class), null);

        $this->expectException(BadRequestHttpException::class);

        $controller->renderAction(new Request(), 'invalid {{insert}} tag');
    }
}
