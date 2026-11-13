<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Framework;

use Contao\CoreBundle\HttpClient\NoPrivateNetworkExceptRootPagesHttpClient;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Model\Collection;
use Contao\PageModel;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\AsyncResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Contracts\HttpClient\ResponseInterface;

class NoPrivateNetworkExceptRootPagesHttpClientTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([IpUtils::class]);

        parent::tearDown();
    }

    public function testRegularExternalRequest(): void
    {
        $response = new MockResponse('response');
        $client = $this->createClient(
            function (string $method, string $url, array $options) use ($response): ResponseInterface {
                $this->assertSame('GET', $method);
                $this->assertSame('https://example.com/foo?bar=baz', $url);
                $this->assertSame(0, $options['max_redirects']);

                return $response;
            },
            expectInternalRequests: false,
        );

        $response = $client->request('GET', 'https://example.com/foo?bar=baz', ['resolve' => ['example.com' => '172.66.147.243']]);
        $this->assertInstanceOf(AsyncResponse::class, $response);
        $this->assertSame('response', $response->getContent());
    }

    /**
     * @dataProvider getInternalRequests
     */
    public function testInternalRequests(string $allowedOrigin, array $disallowedUrls): void
    {
        $resolve = [];

        $resolve[parse_url($allowedOrigin, PHP_URL_HOST)] = '127.0.0.1';

        foreach ($disallowedUrls as $disallowedUrl) {
            $resolve[parse_url($disallowedUrl, PHP_URL_HOST)] = '127.0.0.1';
        }

        $response = new MockResponse('response');
        $client = $this->createClient(
            function (string $method, string $url, array $options) use ($allowedOrigin, $response): ResponseInterface {
                $this->assertSame('GET', $method);
                $this->assertSame("$allowedOrigin/foo?bar=baz", $url);
                $this->assertSame(0, $options['max_redirects']);

                return $response;
            },
            ["$allowedOrigin/root-page-alias.html"],
        );

        $response = $client->request('GET', "$allowedOrigin/foo?bar=baz", ['resolve' => $resolve]);
        $this->assertInstanceOf(AsyncResponse::class, $response);
        $this->assertSame('response', $response->getContent());

        foreach ($disallowedUrls as $disallowedUrl) {
            try {
                $client->request('GET', "$disallowedUrl/foo?bar=baz", ['resolve' => $resolve]);
            } catch (TransportException $exception) {
                $this->assertStringMatchesFormat('Host "%s" is blocked%s', $exception->getMessage());
                continue;
            }
            $this->fail(\sprintf('Disallowed URL "%s" should be blocked.', $disallowedUrl));
        }

        $client = $this->createClient();

        $this->expectException(TransportException::class);
        $this->expectExceptionMessageMatches('/^Host ".*" is blocked/');

        $client->request('GET', "$allowedOrigin/foo?bar=baz", ['resolve' => $resolve]);
    }

    public static function getInternalRequests(): iterable
    {
        yield [
            'https://contao.wip',
            ['http://contao.wip', 'http://contao.wip:443', 'http://contao.wip:8080', 'https://contao.wip:8080', 'https://subdomain.contao.wip'],
        ];

        yield [
            'https://contao.wip:8080',
            ['http://contao.wip', 'http://contao.wip:443', 'http://contao.wip:8080', 'https://contao.wip', 'https://subdomain.contao.wip'],
        ];

        yield [
            'https://localhost',
            ['https://127.0.0.1', 'http://localhost', 'http://localhost:443', 'http://localhost:8080', 'https://localhost:8080', 'https://subdomain.localhost'],
        ];

        yield [
            'https://127.0.0.1',
            ['http://127.0.0.1', 'http://127.0.0.1:443', 'http://127.0.0.1:8080', 'https://127.0.0.1:8080', 'https://127.1'],
        ];

        yield [
            'https://127.0.0.1:8080',
            ['http://127.0.0.1', 'http://127.0.0.1:443', 'http://127.0.0.1:8080', 'https://127.0.0.1', 'https://127.1'],
        ];

        yield [
            'https://[::1]',
            ['http://[::1]', 'http://[::1]:443', 'http://[::1]:8080', 'https://[::1]:8080', 'https://[0:0:0:0:0:0:0:1]'],
        ];

        yield [
            'https://[::1]:8080',
            ['http://[::1]', 'http://[::1]:443', 'http://[::1]:8080', 'https://[::1]', 'https://[0:0:0:0:0:0:0:1]'],
        ];
    }

    /**
     * @dataProvider getRedirectedRequests
     */
    public function testRedirectedRequests(array $allowedOrigins, array $redirectUrls, bool $shouldSucceed): void
    {
        $resolve = ['example.com' => '172.66.147.243'];

        foreach ($allowedOrigins as $allowedOrigin) {
            $resolve[parse_url($allowedOrigin, PHP_URL_HOST)] = '127.0.0.1';
        }

        $client = $this->createClient(
            function (string $method, string $url, array $options) use ($redirectUrls): MockResponse {
                $this->assertSame('GET', $method);
                $this->assertContains($url, $redirectUrls);
                $this->assertSame(0, $options['max_redirects']);

                $redirectIndex = array_search($url, $redirectUrls, true);

                if ($redirectIndex === array_key_last($redirectUrls)) {
                    return new MockResponse('response');
                }

                return new MockResponse(
                    'redirect',
                    [
                        'http_code' => 302,
                        'redirect_url' => $redirectUrls[$redirectIndex + 1],
                        'response_headers' => ['Location' => $redirectUrls[$redirectIndex + 1]],
                    ],
                );
            },
            array_map(
                static fn ($origin) => "$origin/root-page-alias.html",
                $allowedOrigins,
            ),
        );

        $response = $client->request('GET', $redirectUrls[0], ['resolve' => $resolve]);
        $this->assertInstanceOf(AsyncResponse::class, $response);

        if (!$shouldSucceed) {
            $this->expectException(TransportException::class);
            $this->expectExceptionMessageMatches('/^Host ".*" is blocked/');
        }

        $this->assertSame('response', $response->getContent());
    }

    public static function getRedirectedRequests(): iterable
    {
        yield 'Internal redirect' => [
            ['https://contao.wip'],
            ['https://contao.wip/feed.xml', 'https://contao.wip/en/feed.xml'],
            true,
        ];

        yield 'Internal redirects cross-protocol' => [
            ['http://contao.wip', 'https://contao.wip'],
            ['http://contao.wip/feed.xml', 'https://contao.wip/feed.xml', 'https://contao.wip/en/feed.xml'],
            true,
        ];

        yield 'Redirect external to internal to external' => [
            ['https://127.0.0.1:8080'],
            ['https://example.com/foo', 'https://127.0.0.1:8080/bar', 'https://example.com/baz'],
            true,
        ];

        yield 'Redirect internal to external to internal' => [
            ['https://127.0.0.1:8080'],
            ['https://127.0.0.1:8080/foo', 'https://example.com/bar', 'https://127.0.0.1:8080/baz'],
            true,
        ];

        yield 'Disallowed redirect cross-port' => [
            ['https://127.0.0.1:8080'],
            ['https://127.0.0.1:8080/foo', 'https://127.0.0.1/foo'],
            false,
        ];

        yield 'Disallowed redirect cross-protocol' => [
            ['https://contao.wip'],
            ['https://contao.wip/foo', 'http://contao.wip/foo'],
            false,
        ];

        yield 'Disallowed redirect external to internal' => [
            ['https://contao.wip'],
            ['https://example.com/foo', 'https://127.0.0.1/foo'],
            false,
        ];

        yield 'Disallowed redirect internal to external to internal' => [
            ['https://contao.wip'],
            ['https://contao.wip/foo', 'https://example.com/foo', 'https://127.0.0.1/foo'],
            false,
        ];

        yield 'Disallowed redirect internal to external to internal to external to internal' => [
            ['https://contao1.wip', 'https://contao2.wip'],
            ['https://contao1.wip/foo', 'https://example.com/foo1', 'https://contao2.wip/foo', 'https://example.com/foo2', 'https://127.0.0.1/foo'],
            false,
        ];

        yield 'Disallowed redirect internal to internal to internal cross-port' => [
            ['https://127.0.0.1:8080', 'https://127.0.0.1'],
            ['https://127.0.0.1:8080/foo', 'https://127.0.0.1/foo', 'https://127.0.0.1:3306/foo'],
            false,
        ];
    }

    private function createClient(\Closure|null $responseFactory = null, array $rootPageUrls = [], bool $expectInternalRequests = true): NoPrivateNetworkExceptRootPagesHttpClient
    {
        $pages = new Collection(
            array_map(
                fn ($url) => $this->mockClassWithProperties(PageModel::class, ['_test_url' => $url]),
                $rootPageUrls,
            ),
            'tl_page',
        );

        $pageAdapter = $this->mockAdapter(['findPublishedRootPages']);
        $pageAdapter
            ->expects($this->exactly((int) $expectInternalRequests))
            ->method('findPublishedRootPages')
            ->willReturn($pages)
        ;

        $framework = $this->mockContaoFramework([
            PageModel::class => $pageAdapter,
        ]);

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->exactly(\count($pages)))
            ->method('generate')
            ->willReturnCallback(static fn ($page) => $page->_test_url)
        ;

        return new NoPrivateNetworkExceptRootPagesHttpClient(
            new MockHttpClient($responseFactory),
            $framework,
            $urlGenerator,
        );
    }
}
