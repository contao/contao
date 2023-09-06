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
use Contao\CoreBundle\Controller\Page\RootPageController;
use Contao\CoreBundle\Exception\NoActivePageFoundException;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Contao\System;
use Psr\Log\NullLogger;
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

        $request = Request::create('https://example.com/foobar');

        $insertTagParser = $this->createMock(InsertTagParser::class);
        $insertTagParser
            ->method('replaceInline')
            ->with('lorem/ipsum')
            ->willReturn('lorem/ipsum')
        ;

        $controller = new RedirectPageController($insertTagParser);
        $response = $controller($request, $pageModel);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/lorem/ipsum', $response->getTargetUrl());
        $this->assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
    }

    public function testCreatesTemporaryRedirectToUrl(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'redirect' => 'temporary',
            'url' => 'lorem/ipsum',
        ]);

        $request = Request::create('https://example.com/foobar');

        $insertTagParser = $this->createMock(InsertTagParser::class);
        $insertTagParser
            ->method('replaceInline')
            ->with('lorem/ipsum')
            ->willReturn('lorem/ipsum')
        ;

        $controller = new RedirectPageController($insertTagParser);
        $response = $controller($request, $pageModel);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/lorem/ipsum', $response->getTargetUrl());
        $this->assertSame(Response::HTTP_SEE_OTHER, $response->getStatusCode());
    }
}
