<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Functional;

use Contao\Config;
use Contao\Environment;
use Contao\Input;
use Contao\InsertTags;
use Contao\System;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

class RoutingTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        $fs = new Filesystem();
        $fs->remove(__DIR__.'/var');
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $_GET = [];

        Input::resetCache();
        Input::resetUnusedGet();
        Environment::reset();
        InsertTags::reset();
    }

    /**
     * @dataProvider getAliases
     */
    public function testResolvesAliases(string $request, int $statusCode, string $pageTitle, array $query, string $host, bool $autoItem, bool $folderUrl): void
    {
        Config::set('useAutoItem', $autoItem);
        Config::set('folderUrl', $folderUrl);
        Config::set('urlSuffix', '.html');
        Config::set('addLanguageToUrl', false);

        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['HTTP_HOST'] = $host;

        $client = $this->createClient([], $_SERVER);
        System::setContainer($client->getContainer());

        $crawler = $client->request('GET', $request);
        $title = trim($crawler->filterXPath('//head/title')->text());
        $response = $client->getResponse();

        $this->assertSame($statusCode, $response->getStatusCode());
        $this->assertSame($query, $_GET);
        $this->assertContains($pageTitle, $title);
    }

    /**
     * @return array<string,array<int,array<string,string>|bool|int|string>>
     */
    public function getAliases(): array
    {
        return [
            'Renders the page if the alias is "index" and the request is empty' => [
                '/',
                200,
                'Index - Root with index page',
                [],
                'root-with-index.local',
                false,
                false,
            ],
            'Redirects to the first regular page if the alias is not "index" and the request is empty' => [
                '/',
                302,
                'Redirecting to http://root-with-home.local/home.html',
                [],
                'root-with-home.local',
                false,
                false,
            ],
            'Renders the page if the alias matches' => [
                '/home.html',
                200,
                'Home - Root with home page',
                [],
                'root-with-home.local',
                false,
                false,
            ],
            'Renders the 404 page if the URL suffix does not match' => [
                '/home.xml',
                404,
                '(404 Not Found)',
                [],
                'root-with-home.local',
                false,
                false,
            ],
            'Renders the 404 page if the URL contains the "auto_item" keyword' => [
                '/home/auto_item/foo.html',
                404,
                '(404 Not Found)',
                [],
                'root-with-home.local',
                false,
                false,
            ],
            'Renders the 404 page if the path contains duplicate keys' => [
                '/home/foo/bar1/foo/bar2.html',
                404,
                '(404 Not Found)',
                ['foo' => 'bar1'],
                'root-with-home.local',
                false,
                false,
            ],
            'Renders the 404 page if the path contains unused arguments' => [
                '/home/foo/bar.html',
                404,
                '(404 Not Found)',
                ['foo' => 'bar'],
                'root-with-home.local',
                false,
                false,
            ],
            'Renders the page if an existing item is requested' => [
                '/home/items/foobar.html',
                200,
                'Foobar - Root with home page',
                ['items' => 'foobar'],
                'root-with-home.local',
                false,
                false,
            ],
            'Ignores key/value pairs if the key is empty' => [
                '/home//foo.html',
                200,
                'Home - Root with home page',
                [],
                'root-with-home.local',
                false,
                false,
            ],
            'Renders the default page if the alias is empty' => [
                '/.html',
                200,
                'Home - Root with home page',
                [],
                'root-with-home.local',
                false,
                false,
            ],
            'Urldecodes the alias' => [
                '/h%C3%B6me.html',
                200,
                'Höme - Root with special chars',
                [],
                'root-with-special-chars.local',
                false,
                false,
            ],
            'Renders the page if auto items are enabled an existing item is requested' => [
                '/home/foobar.html',
                200,
                'Foobar - Root with home page',
                ['auto_item' => 'foobar', 'items' => 'foobar'],
                'root-with-home.local',
                true,
                false,
            ],
            'Renders the 404 page if auto items are enabled and the URL contains the "auto_item" keyword' => [
                '/home/auto_item/foo.html',
                404,
                '(404 Not Found)',
                [],
                'root-with-home.local',
                true,
                false,
            ],
            'Renders the 404 page if auto items are enabled and the URL contains an auto item keyword' => [
                '/home/items/foobar.html',
                404,
                '(404 Not Found)',
                [],
                'root-with-home.local',
                true,
                false,
            ],
            'Redirects to the first regular page if the folder URL alias is not "index" and the request is empty' => [
                '/',
                302,
                'Redirecting to http://root-with-folder-urls.local/folder/url/home.html',
                [],
                'root-with-folder-urls.local',
                false,
                true,
            ],
            'Renders the page if the folder URL alias matches' => [
                '/folder/url/home.html',
                200,
                'Home - Root with folder URLs',
                [],
                'root-with-folder-urls.local',
                false,
                true,
            ],
            'Renders the folder URL page if an existing item is requested' => [
                '/folder/url/home/items/foobar.html',
                200,
                'Foobar - Root with folder URLs',
                ['items' => 'foobar'],
                'root-with-folder-urls.local',
                false,
                true,
            ],
            'Renders the folder URL page if auto items are enabled an existing item is requested' => [
                '/folder/url/home/foobar.html',
                200,
                'Foobar - Root with folder URLs',
                ['auto_item' => 'foobar', 'items' => 'foobar'],
                'root-with-folder-urls.local',
                true,
                true,
            ],
            'Renders the 404 page if auto items are enabled and the folder URL contains the "auto_item" keyword' => [
                '/folder/url/home/auto_item/foo.html',
                404,
                '(404 Not Found)',
                [],
                'root-with-folder-urls.local',
                true,
                true,
            ],
            'Renders the 404 page if auto items are enabled and the folder URL contains an auto item keyword' => [
                '/folder/url/home/items/foobar.html',
                404,
                '(404 Not Found)',
                [],
                'root-with-folder-urls.local',
                true,
                true,
            ],
        ];
    }

    /**
     * @dataProvider getAliasesWithLocale
     */
    public function testResolvesAliasesWithLocale(string $request, int $statusCode, string $pageTitle, array $query, string $host, bool $autoItem, bool $folderUrl): void
    {
        Config::set('useAutoItem', $autoItem);
        Config::set('folderUrl', $folderUrl);
        Config::set('urlSuffix', '.html');
        Config::set('addLanguageToUrl', true);

        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['HTTP_HOST'] = $host;

        $client = $this->createClient(['environment' => 'locale'], $_SERVER);
        System::setContainer($client->getContainer());

        $crawler = $client->request('GET', $request);
        $title = trim($crawler->filterXPath('//head/title')->text());
        $response = $client->getResponse();

        $this->assertSame($statusCode, $response->getStatusCode());
        $this->assertSame($query, $_GET);
        $this->assertContains($pageTitle, $title);
    }

    /**
     * @return array<string,array<int,array<string,string>|bool|int|string>>
     */
    public function getAliasesWithLocale(): array
    {
        return [
            'Redirects to the language root if the request is empty' => [
                '/',
                301,
                'Redirecting to http://root-with-index.local/en/',
                [],
                'root-with-index.local',
                false,
                false,
            ],
            'Renders the page if the alias is "index" and the request contains the language only' => [
                '/en/',
                200,
                'Index - Root with index page',
                ['language' => 'en'],
                'root-with-index.local',
                false,
                false,
            ],
            'Renders the page if the alias matches' => [
                '/en/home.html',
                200,
                'Home - Root with home page',
                ['language' => 'en'],
                'root-with-home.local',
                false,
                false,
            ],
            'Redirects if the alias matches but no language is given' => [
                '/home.html',
                301,
                'Redirecting to http://root-with-home.local/en/home.html',
                [],
                'root-with-home.local',
                false,
                false,
            ],
            'Renders the 404 page if the URL suffix does not match' => [
                '/en/home.xml',
                404,
                '(404 Not Found)',
                [],
                'root-with-home.local',
                false,
                false,
            ],
            'Renders the 404 page if the URL contains the "auto_item" keyword' => [
                '/en/home/auto_item/foo.html',
                404,
                '(404 Not Found)',
                [],
                'root-with-home.local',
                false,
                false,
            ],
            'Renders the 404 page if the path contains duplicate keys' => [
                '/en/home/foo/bar1/foo/bar2.html',
                404,
                '(404 Not Found)',
                ['language' => 'en', 'foo' => 'bar1'],
                'root-with-home.local',
                false,
                false,
            ],
            'Renders the 404 page if the path contains unused arguments' => [
                '/en/home/foo/bar.html',
                404,
                '(404 Not Found)',
                ['language' => 'en', 'foo' => 'bar'],
                'root-with-home.local',
                false,
                false,
            ],
            'Renders the page if an existing item is requested' => [
                '/en/home/items/foobar.html',
                200,
                'Foobar - Root with home page',
                ['language' => 'en', 'items' => 'foobar'],
                'root-with-home.local',
                false,
                false,
            ],
            'Ignores key/value pairs if the key is empty' => [
                '/en/home//foo.html',
                200,
                'Home - Root with home page',
                ['language' => 'en'],
                'root-with-home.local',
                false,
                false,
            ],
            'Renders the default page if the alias is empty' => [
                '/en/.html',
                200,
                'Home - Root with home page',
                ['language' => 'en'],
                'root-with-home.local',
                false,
                false,
            ],
            'Urldecodes the alias' => [
                '/en/h%C3%B6me.html',
                200,
                'Höme - Root with special chars',
                ['language' => 'en'],
                'root-with-special-chars.local',
                false,
                false,
            ],
            'Renders the page if auto items are enabled an existing item is requested' => [
                '/en/home/foobar.html',
                200,
                'Foobar - Root with home page',
                ['language' => 'en', 'auto_item' => 'foobar', 'items' => 'foobar'],
                'root-with-home.local',
                true,
                false,
            ],
            'Renders the 404 page if auto items are enabled and the URL contains the "auto_item" keyword' => [
                '/en/home/auto_item/foo.html',
                404,
                '(404 Not Found)',
                [],
                'root-with-home.local',
                true,
                false,
            ],
            'Renders the 404 page if auto items are enabled and the URL contains an auto item keyword' => [
                '/en/home/items/foobar.html',
                404,
                '(404 Not Found)',
                ['language' => 'en'],
                'root-with-home.local',
                true,
                false,
            ],
            'Renders the page if the folder URL alias matches' => [
                '/en/folder/url/home.html',
                200,
                'Home - Root with folder URLs',
                ['language' => 'en'],
                'root-with-folder-urls.local',
                false,
                true,
            ],
            'Renders the folder URL page if an existing item is requested' => [
                '/en/folder/url/home/items/foobar.html',
                200,
                'Foobar - Root with folder URLs',
                ['language' => 'en', 'items' => 'foobar'],
                'root-with-folder-urls.local',
                false,
                true,
            ],
            'Renders the folder URL page if auto items are enabled an existing item is requested' => [
                '/en/folder/url/home/foobar.html',
                200,
                'Foobar - Root with folder URLs',
                ['language' => 'en', 'auto_item' => 'foobar', 'items' => 'foobar'],
                'root-with-folder-urls.local',
                true,
                true,
            ],
            'Renders the 404 page if auto items are enabled and the folder URL contains the "auto_item" keyword' => [
                '/en/folder/url/home/auto_item/foo.html',
                404,
                '(404 Not Found)',
                [],
                'root-with-folder-urls.local',
                true,
                true,
            ],
            'Renders the 404 page if auto items are enabled and the folder URL contains an auto item keyword' => [
                '/en/folder/url/home/items/foobar.html',
                404,
                '(404 Not Found)',
                ['language' => 'en'],
                'root-with-folder-urls.local',
                true,
                true,
            ],
        ];
    }

    /**
     * @dataProvider getAliasesWithoutUrlSuffix
     */
    public function testResolvesAliasesWithoutUrlSuffix(string $request, int $statusCode, string $pageTitle, array $query, string $host, bool $autoItem, bool $folderUrl): void
    {
        Config::set('useAutoItem', $autoItem);
        Config::set('folderUrl', $folderUrl);
        Config::set('urlSuffix', '');
        Config::set('addLanguageToUrl', false);

        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['HTTP_HOST'] = $host;

        $client = $this->createClient(['environment' => 'suffix'], $_SERVER);
        System::setContainer($client->getContainer());

        $crawler = $client->request('GET', $request);
        $title = trim($crawler->filterXPath('//head/title')->text());
        $response = $client->getResponse();

        $this->assertSame($statusCode, $response->getStatusCode());
        $this->assertSame($query, $_GET);
        $this->assertContains($pageTitle, $title);
    }

    /**
     * @return array<string,array<int,array<string,string>|bool|int|string>>
     */
    public function getAliasesWithoutUrlSuffix(): array
    {
        return [
            'Renders the page if the alias is "index" and the request is empty' => [
                '/',
                200,
                'Index - Root with index page',
                [],
                'root-with-index.local',
                false,
                false,
            ],
            'Redirects to the first regular page if the alias is not "index" and the request is empty' => [
                '/',
                302,
                'Redirecting to http://root-with-home.local/home',
                [],
                'root-with-home.local',
                false,
                false,
            ],
            'Renders the page if the alias matches' => [
                '/home',
                200,
                'Home - Root with home page',
                [],
                'root-with-home.local',
                false,
                false,
            ],
            'Renders the 404 page if the URL suffix does not match' => [
                '/home.xml',
                404,
                '(404 Not Found)',
                [],
                'root-with-home.local',
                false,
                false,
            ],
            'Renders the 404 page if the URL contains the "auto_item" keyword' => [
                '/home/auto_item/foo',
                404,
                '(404 Not Found)',
                [],
                'root-with-home.local',
                false,
                false,
            ],
            'Renders the 404 page if the path contains duplicate keys' => [
                '/home/foo/bar1/foo/bar2',
                404,
                '(404 Not Found)',
                ['foo' => 'bar1'],
                'root-with-home.local',
                false,
                false,
            ],
            'Renders the 404 page if the path contains unused arguments' => [
                '/home/foo/bar',
                404,
                '(404 Not Found)',
                ['foo' => 'bar'],
                'root-with-home.local',
                false,
                false,
            ],
            'Renders the page if an existing item is requested' => [
                '/home/items/foobar',
                200,
                'Foobar - Root with home page',
                ['items' => 'foobar'],
                'root-with-home.local',
                false,
                false,
            ],
            'Ignores key/value pairs if the key is empty' => [
                '/home//foo',
                200,
                'Home - Root with home page',
                [],
                'root-with-home.local',
                false,
                false,
            ],
            'Urldecodes the alias' => [
                '/h%C3%B6me',
                200,
                'Höme - Root with special chars',
                [],
                'root-with-special-chars.local',
                false,
                false,
            ],
            'Renders the page if auto items are enabled an existing item is requested' => [
                '/home/foobar',
                200,
                'Foobar - Root with home page',
                ['auto_item' => 'foobar', 'items' => 'foobar'],
                'root-with-home.local',
                true,
                false,
            ],
            'Renders the 404 page if auto items are enabled and the URL contains the "auto_item" keyword' => [
                '/home/auto_item/foo',
                404,
                '(404 Not Found)',
                [],
                'root-with-home.local',
                true,
                false,
            ],
            'Renders the 404 page if auto items are enabled and the URL contains an auto item keyword' => [
                '/home/items/foobar',
                404,
                '(404 Not Found)',
                [],
                'root-with-home.local',
                true,
                false,
            ],
            'Redirects to the first regular page if the folder URL alias is not "index" and the request is empty' => [
                '/',
                302,
                'Redirecting to http://root-with-folder-urls.local/folder/url/home',
                [],
                'root-with-folder-urls.local',
                false,
                true,
            ],
            'Renders the page if the folder URL alias matches' => [
                '/folder/url/home',
                200,
                'Home - Root with folder URLs',
                [],
                'root-with-folder-urls.local',
                false,
                true,
            ],
            'Renders the folder URL page if an existing item is requested' => [
                '/folder/url/home/items/foobar',
                200,
                'Foobar - Root with folder URLs',
                ['items' => 'foobar'],
                'root-with-folder-urls.local',
                false,
                true,
            ],
            'Renders the folder URL page if auto items are enabled an existing item is requested' => [
                '/folder/url/home/foobar',
                200,
                'Foobar - Root with folder URLs',
                ['auto_item' => 'foobar', 'items' => 'foobar'],
                'root-with-folder-urls.local',
                true,
                true,
            ],
            'Renders the 404 page if auto items are enabled and the folder URL contains the "auto_item" keyword' => [
                '/folder/url/home/auto_item/foo',
                404,
                '(404 Not Found)',
                [],
                'root-with-folder-urls.local',
                true,
                true,
            ],
            'Renders the 404 page if auto items are enabled and the folder URL contains an auto item keyword' => [
                '/folder/url/home/items/foobar',
                404,
                '(404 Not Found)',
                [],
                'root-with-folder-urls.local',
                true,
                true,
            ],
        ];
    }

    /**
     * @dataProvider getRootAliases
     */
    public function testResolvesTheRootPage(string $request, int $statusCode, string $pageTitle, string $acceptLanguages, string $host): void
    {
        Config::set('addLanguageToUrl', false);

        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $acceptLanguages;

        $client = $this->createClient([], $_SERVER);
        System::setContainer($client->getContainer());

        $crawler = $client->request('GET', $request);
        $title = trim($crawler->filterXPath('//head/title')->text());
        $response = $client->getResponse();

        $this->assertSame($statusCode, $response->getStatusCode());
        $this->assertContains($pageTitle, $title);
    }

    /**
     * @return array<string,array<int,array<string,string>|bool|int|string>>
     */
    public function getRootAliases(): array
    {
        return [
            'Renders the root page if one of the accept languages matches' => [
                '/',
                200,
                'Index - Root with index page',
                'en,de',
                'root-with-index.local',
            ],
            'Renders the fallback page if none of the accept languages matches' => [
                '/',
                200,
                'Index - Root with index page',
                'de,fr',
                'root-with-index.local',
            ],
            'Throws a "no root page found" exception if no language matches' => [
                '/',
                500,
                'No root page found (500 Internal Server Error)',
                'de,fr',
                'root-without-fallback-language.local',
            ],
            'Redirects to the first language root if the accept languages matches' => [
                '/',
                302,
                'Redirecting to http://same-domain-root.local/english-site.html',
                'en',
                'same-domain-root.local',
            ],
            'Redirects to the second language root if the accept languages matches' => [
                '/',
                302,
                'Redirecting to http://same-domain-root.local/german-site.html',
                'de',
                'same-domain-root.local',
            ],
            'Redirects to the fallback root if none of the accept languages matches' => [
                '/',
                302,
                'Redirecting to http://same-domain-root.local/english-site.html',
                'fr',
                'same-domain-root.local',
            ],
        ];
    }

    /**
     * @dataProvider getRootAliasesWithLocale
     */
    public function testResolvesTheRootPageWithLocale(string $request, int $statusCode, string $pageTitle, string $acceptLanguages, string $host): void
    {
        Config::set('addLanguageToUrl', true);

        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $acceptLanguages;

        $client = $this->createClient(['environment' => 'locale'], $_SERVER);
        System::setContainer($client->getContainer());

        $crawler = $client->request('GET', $request);
        $title = trim($crawler->filterXPath('//head/title')->text());
        $response = $client->getResponse();

        $this->assertSame($statusCode, $response->getStatusCode());
        $this->assertContains($pageTitle, $title);
    }

    /**
     * @return array<string,array<int,array<string,string>|bool|int|string>>
     */
    public function getRootAliasesWithLocale(): array
    {
        return [
            'Redirects to the language root if one of the accept languages matches' => [
                '/',
                301,
                'Redirecting to http://root-with-index.local/en/',
                'en,de',
                'root-with-index.local',
            ],
            'Redirects to the language fallback if none of the accept languages matches' => [
                '/',
                301,
                'Redirecting to http://root-with-index.local/en/',
                'de,fr',
                'root-with-index.local',
            ],
            'Throws a "no root page found" exception if none of the accept languages matches' => [
                '/',
                500,
                'No root page found (500 Internal Server Error)',
                'de,fr',
                'root-without-fallback-language.local',
            ],
            'Renders the root page if the locale matches' => [
                '/en/',
                200,
                'Index - Root with index page',
                'en,de',
                'root-with-index.local',
            ],
            'Renders the 404 page if the locale does not match' => [
                '/de/',
                404,
                '(404 Not Found)',
                'de,fr',
                'root-with-index.local',
            ],
            'Throws a "no root page found" exception if the locale does not match' => [
                '/fr/',
                500,
                'No root page found (500 Internal Server Error)',
                'de,fr',
                'root-without-fallback-language.local',
            ],
        ];
    }
}
