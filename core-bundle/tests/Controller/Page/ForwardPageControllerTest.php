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

use Contao\CoreBundle\Controller\Page\ForwardPageController;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ForwardPageControllerTest extends TestCase
{
    /**
     * @dataProvider getRedirectPages
     */
    public function testRedirectsToUrl(string $redirectType, string $requestUrl, string $jumpToUrl, string $expectedRedirectUrl, string $pathParams = '', bool $forwardParams = false): void
    {
        if ($pathParams && !$forwardParams) {
            $this->expectException(PageNotFoundException::class);
        }

        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'redirect' => $redirectType,
            'jumpTo' => 42,
        ]);

        $jumpTo = $this->createMock(PageModel::class);
        $jumpTo
            ->method('getAbsoluteUrl')
            ->willReturnCallback(
                static fn ($value): string => Path::join($jumpToUrl, (string) $value),
            )
        ;

        $request = Request::create(
            $requestUrl,
            'GET',
            [],
            [],
            [],
            [
                'SCRIPT_FILENAME' => '/index.php',
                'SCRIPT_NAME' => '/index.php',
            ],
        );
        $request->attributes->set('_forward_params', $forwardParams);

        $adapter = $this->mockAdapter(['findPublishedById', 'findFirstPublishedRegularByPid']);
        $adapter
            ->method('findPublishedById')
            ->with(42)
            ->willReturn($jumpTo)
        ;

        $adapter
            ->method('findFirstPublishedRegularByPid')
            ->with(42)
            ->willReturn($jumpTo)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        $controller = new ForwardPageController($framework);
        $response = $controller($request, $pageModel, $pathParams);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($expectedRedirectUrl, $response->getTargetUrl());

        if ('permanent' === $redirectType) {
            $this->assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
        } else {
            $this->assertSame(Response::HTTP_SEE_OTHER, $response->getStatusCode());
        }

        if (!$forwardParams) {
            $this->assertNull(parse_url($response->getTargetUrl(), PHP_URL_QUERY));
        }
    }

    public function getRedirectPages(): \Generator
    {
        yield ['permanent', 'https://example.com/internal-redirect', 'https://example.com/redirect-target', 'https://example.com/redirect-target'];
        yield ['temporary', 'https://example.com/internal-redirect', 'https://example.com/redirect-target', 'https://example.com/redirect-target'];
        yield ['permanent', 'https://example.com/internal-redirect?foobar=1', 'https://example.com/redirect-target', 'https://example.com/redirect-target'];
        yield ['permanent', 'https://example.com/internal-redirect/foobar', 'https://example.com/redirect-target', 'https://example.com/redirect-target', 'foobar'];
        yield ['permanent', 'https://example.com/internal-redirect?foobar=1', 'https://example.com/redirect-target', 'https://example.com/redirect-target?foobar=1', '', true];
        yield ['permanent', 'https://example.com/internal-redirect/foobar', 'https://example.com/redirect-target', 'https://example.com/redirect-target/foobar', 'foobar', true];
    }
}
