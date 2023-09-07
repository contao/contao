<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\Page;

use Contao\CoreBundle\Controller\Page\RedirectPageController;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectPageControllerTest extends TestCase
{
    public function testCreatesPermanentRedirectToUrl(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'redirect' => 'permanent',
            'url' => 'lorem/ipsum',
        ]);

        $request = Request::create(
            'https://example.com/foobar/index.php/foobar',
            'GET',
            [],
            [],
            [],
            [
                'SCRIPT_FILENAME' => '/foobar/index.php',
                'SCRIPT_NAME' => '/foobar/index.php',
            ],
        );

        $insertTagParser = $this->createMock(InsertTagParser::class);
        $insertTagParser
            ->method('replaceInline')
            ->willReturnCallback(static fn (string $value) => $value)
        ;

        $controller = new RedirectPageController($insertTagParser);
        $response = $controller($request, $pageModel);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://example.com/foobar/index.php/lorem/ipsum', $response->getTargetUrl());
        $this->assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
    }

    public function testCreatesTemporaryRedirectToUrl(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'redirect' => 'temporary',
            'url' => 'lorem/ipsum',
        ]);

        $request = Request::create(
            'https://example.com/foobar/index.php/foobar',
            'GET',
            [],
            [],
            [],
            [
                'SCRIPT_FILENAME' => '/foobar/index.php',
                'SCRIPT_NAME' => '/foobar/index.php',
            ],
        );

        $insertTagParser = $this->createMock(InsertTagParser::class);
        $insertTagParser
            ->method('replaceInline')
            ->willReturnCallback(static fn (string $value) => $value)
        ;

        $controller = new RedirectPageController($insertTagParser);
        $response = $controller($request, $pageModel);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://example.com/foobar/index.php/lorem/ipsum', $response->getTargetUrl());
        $this->assertSame(Response::HTTP_SEE_OTHER, $response->getStatusCode());
    }
}
