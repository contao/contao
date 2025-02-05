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
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RedirectPageControllerTest extends TestCase
{
    /**
     * @dataProvider getRedirectPages
     */
    public function testRedirectsToUrl(string $redirect, string $url, string $redirectUrl): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'redirect' => $redirect,
            'url' => $url,
        ]);

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($pageModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn($redirectUrl)
        ;

        $controller = new RedirectPageController($urlGenerator);
        $response = $controller($pageModel);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($redirectUrl, $response->getTargetUrl());

        if ('permanent' === $redirect) {
            $this->assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
        } else {
            $this->assertSame(Response::HTTP_SEE_OTHER, $response->getStatusCode());
        }
    }

    public static function getRedirectPages(): iterable
    {
        yield ['permanent', '/foobar/index.php/lorem/ipsum', 'https://example.com/foobar/index.php/lorem/ipsum'];
        yield ['temporary', '/foobar/index.php/lorem/ipsum', 'https://example.com/foobar/index.php/lorem/ipsum'];
        yield ['permanent', 'lorem/ipsum', 'https://example.com/foobar/lorem/ipsum'];
        yield ['permanent', '{{link_url::123}}', 'https://example.com/foobar/index.php/lorem/ipsum'];
    }
}
