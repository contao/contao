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
    /**
     * @dataProvider getRedirectPages
     */
    public function testRedirectsToUrl(string $redirect, string $url, string $redirectUrl, string|null $insertTagResult = null): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'redirect' => $redirect,
            'url' => $url,
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
            ->expects($this->once())
            ->method('replaceInline')
            ->willReturnCallback(
                static function (string $value) use ($insertTagResult) {
                    if (null !== $insertTagResult) {
                        return $insertTagResult;
                    }

                    return $value;
                },
            )
        ;

        $controller = new RedirectPageController($insertTagParser);
        $response = $controller($request, $pageModel);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($redirectUrl, $response->getTargetUrl());

        if ('permanent' === $redirect) {
            $this->assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
        } else {
            $this->assertSame(Response::HTTP_SEE_OTHER, $response->getStatusCode());
        }
    }

    public function getRedirectPages(): \Generator
    {
        yield ['permanent', '/foobar/index.php/lorem/ipsum', 'https://example.com/foobar/index.php/lorem/ipsum'];
        yield ['temporary', '/foobar/index.php/lorem/ipsum', 'https://example.com/foobar/index.php/lorem/ipsum'];
        yield ['permanent', 'lorem/ipsum', 'https://example.com/foobar/lorem/ipsum'];
        yield ['permanent', '{{link_url::123}}', 'https://example.com/foobar/index.php/lorem/ipsum', '/foobar/index.php/lorem/ipsum'];
    }
}
