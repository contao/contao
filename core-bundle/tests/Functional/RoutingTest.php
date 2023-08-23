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

use Contao\System;
use Contao\TestCase\FunctionalTestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

class RoutingTest extends FunctionalTestCase
{
    use ExpectDeprecationTrait;

    private static array|null $lastImport = null;

    protected function setUp(): void
    {
        parent::setUp();

        $_GET = [];
        unset($GLOBALS['objPage']);

        $GLOBALS['TL_CONFIG']['addLanguageToUrl'] = false;
    }

    /**
     * @dataProvider getAliases
     */
    public function testResolvesAliases(array $fixtures, string $request, int $statusCode, string $pageTitle, array $query, string $host): void
    {
        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en';
        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        $client = $this->createClient([], $_SERVER);
        System::setContainer($client->getContainer());

        $this->loadFixtureFiles($fixtures);

        $crawler = $client->request('GET', "https://$host$request");
        $title = trim($crawler->filterXPath('//head/title')->text());
        $response = $client->getResponse();

        $this->assertSame($statusCode, $response->getStatusCode());
        $this->assertSame($query, $_GET);
        $this->assertStringContainsString($pageTitle, $title);
    }

    public function getAliases(): \Generator
    {
        yield 'Renders the page if the alias is "index" and the request is empty' => [
            ['theme', 'root-with-index'],
            '/',
            200,
            'Index - Root with index page',
            [],
            'root-with-index.local',
        ];

        yield 'Redirects to the first regular page if the alias is not "index" and the request is empty' => [
            ['theme', 'root-with-home'],
            '/',
            302,
            'Redirecting to https://root-with-home.local/home.html',
            [],
            'root-with-home.local',
        ];

        yield 'Renders the page if the alias matches' => [
            ['theme', 'root-with-home'],
            '/home.html',
            200,
            'Home - Root with home page',
            [],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if the request string is a double-slash' => [
            ['theme', 'root-with-home'],
            '//',
            404,
            'Error 404 Page',
            [],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if there is an item with an empty key' => [
            ['theme', 'root-with-home'],
            '/home//.html',
            404,
            'Error 404 Page',
            [],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if the URL suffix does not match' => [
            ['theme', 'root-with-home'],
            '/home.xml',
            404,
            'Error 404 Page',
            [],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if the URL contains the "auto_item" keyword' => [
            ['theme', 'root-with-home'],
            '/home/auto_item/foo.html',
            404,
            'Error 404 Page',
            [],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if the path contains duplicate keys' => [
            ['theme', 'root-with-home'],
            '/home/foo/bar1/foo/bar2.html',
            404,
            'Error 404 Page',
            ['foo' => 'bar1'],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if the path contains an unused argument' => [
            ['theme', 'root-with-home'],
            '/home/foo/bar.html',
            404,
            'Error 404 Page',
            ['foo' => 'bar'],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if the path contains an unused argument without value' => [
            ['theme', 'root-with-home'],
            '/home/foo.html',
            404,
            'Error 404 Page',
            ['auto_item' => 'foo'],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if the path contains an unused argument with an empty value' => [
            ['theme', 'root-with-home'],
            '/home/foo/.html',
            404,
            'Error 404 Page',
            ['foo' => ''],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if the path contains an item with an empty key' => [
            ['theme', 'root-with-home'],
            '/home//foo.html',
            404,
            'Error 404 Page',
            [],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if the alias is empty' => [
            ['theme', 'root-with-home'],
            '/.html',
            404,
            'Error 404 Page',
            [],
            'root-with-home.local',
        ];

        yield 'Urldecodes the alias' => [
            ['theme', 'root-with-special-chars'],
            '/h%C3%B6me.html',
            200,
            'Höme - Root with special chars',
            [],
            'root-with-special-chars.local',
        ];

        yield 'Renders the page if an existing auto item is requested' => [
            ['theme', 'root-with-home', 'news'],
            '/home/foobar.html',
            200,
            'Foobar - Root with home page',
            ['auto_item' => 'foobar'],
            'root-with-home.local',
        ];

        yield 'Redirects to the first regular page if the folder URL alias is not "index" and the request is empty' => [
            ['theme', 'root-with-folder-urls'],
            '/',
            302,
            'Redirecting to https://root-with-folder-urls.local/folder/url/home.html',
            [],
            'root-with-folder-urls.local',
        ];

        yield 'Renders the page if the folder URL alias matches' => [
            ['theme', 'root-with-folder-urls'],
            '/folder/url/home.html',
            200,
            'Home - Root with folder URLs',
            [],
            'root-with-folder-urls.local',
        ];

        yield 'Renders the folder URL page if an existing auto item is requested' => [
            ['theme', 'root-with-folder-urls', 'news'],
            '/folder/url/home/foobar.html',
            200,
            'Foobar - Root with folder URLs',
            ['auto_item' => 'foobar'],
            'root-with-folder-urls.local',
        ];

        yield 'Renders the 404 exception if the folder URL contains the "auto_item" keyword' => [
            ['theme', 'root-with-folder-urls', 'news'],
            '/folder/url/home/auto_item/foo.html',
            404,
            'Not Found',
            [],
            'root-with-folder-urls.local',
        ];

        yield 'Renders the page if the URL contains a page ID and the page has no alias' => [
            ['theme', 'page-without-alias'],
            '/15.html',
            200,
            'Home - Page without alias',
            [],
            'localhost',
        ];

        yield 'Renders the 404 page if the URL contains a page ID but the page has an alias' => [
            ['theme', 'root-with-home'],
            '/2.html',
            404,
            'Error 404 Page',
            [],
            'root-with-home.local',
        ];
    }

    /**
     * @dataProvider getAliasesWithLocale
     */
    public function testResolvesAliasesWithLocale(array $fixtures, string $request, int $statusCode, string $pageTitle, array $query, string $host): void
    {
        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en';
        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        $client = $this->createClient([], $_SERVER);
        System::setContainer($client->getContainer());

        $this->loadFixtureFiles($fixtures);

        self::getContainer()
            ->get('doctrine')
            ->getConnection()
            ->executeStatement('UPDATE tl_page SET urlPrefix=language')
        ;

        $crawler = $client->request('GET', "https://$host$request");
        $title = trim($crawler->filterXPath('//head/title')->text());
        $response = $client->getResponse();

        $this->assertSame($statusCode, $response->getStatusCode());
        $this->assertSame($query, $_GET);
        $this->assertStringContainsString($pageTitle, $title);
    }

    public function getAliasesWithLocale(): \Generator
    {
        yield 'Redirects to the language root if the request is empty' => [
            ['theme', 'root-with-index'],
            '/',
            302,
            'Redirecting to https://root-with-index.local/en/',
            ['language' => 'en'],
            'root-with-index.local',
        ];

        yield 'Renders the page if the alias is "index" and the request contains the language only' => [
            ['theme', 'root-with-index'],
            '/en/',
            200,
            'Index - Root with index page',
            ['language' => 'en'],
            'root-with-index.local',
        ];

        yield 'Renders the page if the alias matches' => [
            ['theme', 'root-with-home'],
            '/en/home.html',
            200,
            'Home - Root with home page',
            ['language' => 'en'],
            'root-with-home.local',
        ];

        yield 'Redirects if the alias matches but no language is given' => [
            ['theme', 'root-with-home'],
            '/home.html',
            302,
            'Redirecting to https://root-with-home.local/en/home.html',
            [],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if the URL suffix does not match' => [
            ['theme', 'root-with-home'],
            '/en/home.xml',
            404,
            'Error 404 Page',
            [],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if the path ends with a double-slash' => [
            ['theme', 'root-with-home'],
            '/en//',
            404,
            'Error 404 Page',
            [],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if the URL contains the "auto_item" keyword' => [
            ['theme', 'root-with-home'],
            '/en/home/auto_item/foo.html',
            404,
            'Error 404 Page',
            [],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if the path contains duplicate keys' => [
            ['theme', 'root-with-home'],
            '/en/home/foo/bar1/foo/bar2.html',
            404,
            'Error 404 Page',
            ['language' => 'en', 'foo' => 'bar1'],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if the path contains an unused argument' => [
            ['theme', 'root-with-home'],
            '/en/home/foo/bar.html',
            404,
            'Error 404 Page',
            ['language' => 'en', 'foo' => 'bar'],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if the path contains item with an empty key' => [
            ['theme', 'root-with-home', 'news'],
            '/en/home//foo.html',
            404,
            'Error 404 Page',
            ['language' => 'en'],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if the alias is empty' => [
            ['theme', 'root-with-home'],
            '/en/.html',
            404,
            'Error 404 Page',
            [],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page for an unknown language' => [
            ['theme', 'root-with-home'],
            '/fr/home.html',
            404,
            'Error 404 Page',
            [],
            'root-with-home.local',
        ];

        yield 'Urldecodes the alias' => [
            ['theme', 'root-with-special-chars'],
            '/en/h%C3%B6me.html',
            200,
            'Höme - Root with special chars',
            ['language' => 'en'],
            'root-with-special-chars.local',
        ];

        yield 'Renders the page if an existing auto item is requested' => [
            ['theme', 'root-with-home', 'news'],
            '/en/home/foobar.html',
            200,
            'Foobar - Root with home page',
            ['language' => 'en', 'auto_item' => 'foobar'],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if there is an item with an empty key' => [
            ['theme', 'root-with-home', 'news'],
            '/en/home/foobar//foo.html',
            404,
            'Error 404 Page',
            ['language' => 'en', 'auto_item' => 'foobar'],
            'root-with-home.local',
        ];

        yield 'Renders the page if there is an item with an empty value and another item with an empty key' => [
            ['theme', 'root-with-home', 'news'],
            '/en/home/foobar///foo.html',
            404,
            'Error 404 Page',
            ['language' => 'en', 'foobar' => ''],
            'root-with-home.local',
        ];

        yield 'Renders the page if the folder URL alias matches' => [
            ['theme', 'root-with-folder-urls'],
            '/en/folder/url/home.html',
            200,
            'Home - Root with folder URLs',
            ['language' => 'en'],
            'root-with-folder-urls.local',
        ];

        yield 'Renders the folder URL page if an existing auto item is requested' => [
            ['theme', 'root-with-folder-urls', 'news'],
            '/en/folder/url/home/foobar.html',
            200,
            'Foobar - Root with folder URLs',
            ['language' => 'en', 'auto_item' => 'foobar'],
            'root-with-folder-urls.local',
        ];

        yield 'Renders the 404 exception if the folder URL contains the "auto_item" keyword' => [
            ['theme', 'root-with-folder-urls', 'news'],
            '/en/folder/url/home/auto_item/foo.html',
            404,
            'Not Found',
            [],
            'root-with-folder-urls.local',
        ];

        yield 'Renders the page if the URL contains a page ID and the page has no alias' => [
            ['theme', 'page-without-alias'],
            '/en/15.html',
            200,
            'Home - Page without alias',
            ['language' => 'en'],
            'localhost',
        ];

        yield 'Renders the 404 page if the URL contains a page ID but the page has an alias' => [
            ['theme', 'root-with-home'],
            '/en/2.html',
            404,
            'Error 404 Page',
            [],
            'root-with-home.local',
        ];

        yield 'Redirects to the first regular page if the alias is not "index" and the request is only the prefix' => [
            ['theme', 'root-with-home-and-prefix'],
            '/en/',
            302,
            'Redirecting to https://root-with-home.local/en/home.html',
            ['language' => 'en'],
            'root-with-home.local',
        ];
    }

    /**
     * @dataProvider getAliasesWithoutUrlSuffix
     */
    public function testResolvesAliasesWithoutUrlSuffix(array $fixtures, string $request, int $statusCode, string $pageTitle, array $query, string $host): void
    {
        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en';
        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        $client = $this->createClient([], $_SERVER);
        System::setContainer($client->getContainer());

        $this->loadFixtureFiles($fixtures);

        self::getContainer()
            ->get('doctrine')
            ->getConnection()
            ->executeStatement("UPDATE tl_page SET urlSuffix=''")
        ;

        $crawler = $client->request('GET', "https://$host$request");
        $title = trim($crawler->filterXPath('//head/title')->text());
        $response = $client->getResponse();

        $this->assertSame($statusCode, $response->getStatusCode());
        $this->assertSame($query, $_GET);
        $this->assertStringContainsString($pageTitle, $title);
    }

    public function getAliasesWithoutUrlSuffix(): \Generator
    {
        yield 'Renders the page if the alias is "index" and the request is empty' => [
            ['theme', 'root-with-index'],
            '/',
            200,
            'Index - Root with index page',
            [],
            'root-with-index.local',
        ];

        yield 'Redirects to the first regular page if the alias is not "index" and the request is empty' => [
            ['theme', 'root-with-home'],
            '/',
            302,
            'Redirecting to https://root-with-home.local/home',
            [],
            'root-with-home.local',
        ];

        yield 'Renders the page if the alias matches' => [
            ['theme', 'root-with-home'],
            '/home',
            200,
            'Home - Root with home page',
            [],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if the URL suffix does not match' => [
            ['theme', 'root-with-home'],
            '/home.xml',
            404,
            'Error 404 Page',
            [],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if the URL contains the "auto_item" keyword' => [
            ['theme', 'root-with-home'],
            '/home/auto_item/foo',
            404,
            'Error 404 Page',
            [],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if the path contains duplicate keys' => [
            ['theme', 'root-with-home'],
            '/home/foo/bar1/foo/bar2',
            404,
            'Error 404 Page',
            ['foo' => 'bar1'],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if the path contains an unused argument' => [
            ['theme', 'root-with-home', 'news'],
            '/home/foo/bar',
            404,
            'Error 404 Page',
            ['foo' => 'bar'],
            'root-with-home.local',
        ];

        yield 'Renders the 404 page if the path contains an item with item with an empty key' => [
            ['theme', 'root-with-home', 'news'],
            '/home//foo',
            404,
            'Error 404 Page',
            [],
            'root-with-home.local',
        ];

        yield 'Urldecodes the alias' => [
            ['theme', 'root-with-special-chars'],
            '/h%C3%B6me',
            200,
            'Höme - Root with special chars',
            [],
            'root-with-special-chars.local',
        ];

        yield 'Renders the page if an existing auto item is requested' => [
            ['theme', 'root-with-home', 'news'],
            '/home/foobar',
            200,
            'Foobar - Root with home page',
            ['auto_item' => 'foobar'],
            'root-with-home.local',
        ];

        yield 'Redirects to the first regular page if the folder URL alias is not "index" and the request is empty' => [
            ['theme', 'root-with-folder-urls'],
            '/',
            302,
            'Redirecting to https://root-with-folder-urls.local/folder/url/home',
            [],
            'root-with-folder-urls.local',
        ];

        yield 'Renders the page if the folder URL alias matches' => [
            ['theme', 'root-with-folder-urls'],
            '/folder/url/home',
            200,
            'Home - Root with folder URLs',
            [],
            'root-with-folder-urls.local',
        ];

        yield 'Renders the folder URL page if an existing auto item is requested' => [
            ['theme', 'root-with-folder-urls', 'news'],
            '/folder/url/home/foobar',
            200,
            'Foobar - Root with folder URLs',
            ['auto_item' => 'foobar'],
            'root-with-folder-urls.local',
        ];

        yield 'Renders the 404 exception if the folder URL contains the "auto_item" keyword' => [
            ['theme', 'root-with-folder-urls', 'news'],
            '/folder/url/home/auto_item/foo',
            404,
            'Not Found',
            [],
            'root-with-folder-urls.local',
        ];
    }

    /**
     * @dataProvider getRootAliases
     */
    public function testResolvesTheRootPage(array $fixtures, string $request, int $statusCode, string $pageTitle, string $acceptLanguages, string $host): void
    {
        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $acceptLanguages;
        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        $client = $this->createClient([], $_SERVER);
        System::setContainer($client->getContainer());

        $this->loadFixtureFiles($fixtures);

        $crawler = $client->request('GET', "https://$host$request");
        $title = trim($crawler->filterXPath('//head/title')->text());
        $response = $client->getResponse();

        $this->assertSame($statusCode, $response->getStatusCode());
        $this->assertStringContainsString($pageTitle, $title);
    }

    public function getRootAliases(): \Generator
    {
        yield 'Renders the root page if one of the accept languages matches' => [
            ['theme', 'root-with-index'],
            '/',
            200,
            'Index - Root with index page',
            'en,de',
            'root-with-index.local',
        ];

        yield 'Renders the fallback page if none of the accept languages matches' => [
            ['theme', 'root-with-index'],
            '/',
            200,
            'Index - Root with index page',
            'de,fr',
            'root-with-index.local',
        ];

        yield 'Matches a root page without hostname' => [
            ['theme', 'domain-without-hostname'],
            '/',
            200,
            'Home - Domain without hostname',
            'en',
            'domain-without-hostname.local',
        ];

        yield 'Matches a hostname with port' => [
            ['theme', 'localhost'],
            '/',
            200,
            'Home - Localhost',
            'en',
            '127.0.0.1:8080',
        ];

        yield 'Redirects to the first language root if the accept languages matches' => [
            ['theme', 'same-domain-root'],
            '/',
            302,
            'Redirecting to https://same-domain-root.local/english-site.html',
            'en',
            'same-domain-root.local',
        ];

        yield 'Redirects to the second language root if the accept languages matches' => [
            ['theme', 'same-domain-root'],
            '/',
            302,
            'Redirecting to https://same-domain-root.local/german-site.html',
            'de',
            'same-domain-root.local',
        ];

        yield 'Redirects to the fallback root if none of the accept languages matches' => [
            ['theme', 'same-domain-root'],
            '/',
            302,
            'Redirecting to https://same-domain-root.local/english-site.html',
            'fr',
            'same-domain-root.local',
        ];
    }

    /**
     * @dataProvider getRootAliasesWithLocale
     */
    public function testResolvesTheRootPageWithLocale(array $fixtures, string $request, int $statusCode, string $pageTitle, string $acceptLanguages, string $host): void
    {
        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $acceptLanguages;
        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        $client = $this->createClient([], $_SERVER);
        System::setContainer($client->getContainer());

        $this->loadFixtureFiles($fixtures);

        self::getContainer()
            ->get('doctrine')
            ->getConnection()
            ->executeStatement("UPDATE tl_page SET urlPrefix=language WHERE urlPrefix=''")
        ;

        $crawler = $client->request('GET', "https://$host$request");
        $title = trim($crawler->filterXPath('//head/title')->text());
        $response = $client->getResponse();

        $this->assertSame($statusCode, $response->getStatusCode());
        $this->assertStringContainsString($pageTitle, $title);
    }

    public function getRootAliasesWithLocale(): \Generator
    {
        yield 'Redirects to the language root if one of the accept languages matches' => [
            ['theme', 'same-domain-root'],
            '/',
            302,
            'Redirecting to https://same-domain-root.local/de/',
            'de,en',
            'same-domain-root.local',
        ];

        yield 'Redirects to the language fallback if one of the accept languages matches' => [
            ['theme', 'same-domain-root'],
            '/',
            302,
            'Redirecting to https://same-domain-root.local/en/',
            'en,de',
            'same-domain-root.local',
        ];

        yield 'Redirects to the language fallback if none of the accept languages matches' => [
            ['theme', 'same-domain-root'],
            '/',
            302,
            'Redirecting to https://same-domain-root.local/en/',
            'fr,es',
            'same-domain-root.local',
        ];

        yield 'Redirects to "de" if "de-CH" is accepted and "de" is not' => [
            ['theme', 'same-domain-root'],
            '/',
            302,
            'Redirecting to https://same-domain-root.local/de/',
            'de-CH',
            'same-domain-root.local',
        ];

        yield 'Ignores the case of the language code' => [
            ['theme', 'same-domain-root'],
            '/',
            302,
            'Redirecting to https://same-domain-root.local/de/',
            'dE-at',
            'same-domain-root.local',
        ];

        yield 'Redirects to "de" if "de-CH" and "en" are accepted' => [
            ['theme', 'same-domain-root'],
            '/',
            302,
            'Redirecting to https://same-domain-root.local/de/',
            'de-CH,en',
            'same-domain-root.local',
        ];

        yield 'Renders the 404 exception if none of the accept languages matches' => [
            ['theme', 'root-without-fallback-language'],
            '/',
            404,
            'Not Found',
            'de,fr',
            'root-without-fallback-language.local',
        ];

        yield 'Renders the root page if the locale matches' => [
            ['theme', 'root-with-index'],
            '/en/',
            200,
            'Index - Root with index page',
            'en,de',
            'root-with-index.local',
        ];

        yield 'Renders the first language root if the locale matches' => [
            ['theme', 'same-domain-root-with-index'],
            '/en/',
            200,
            'English site - Same domain root English with index',
            'en,de',
            'same-domain-root-with-index.local',
        ];

        yield 'Renders the second language root if the locale matches' => [
            ['theme', 'same-domain-root-with-index'],
            '/de/',
            200,
            'German site - Same domain root German with index',
            'de,en',
            'same-domain-root-with-index.local',
        ];

        yield 'Renders the second language root if the locale matches regardless of accept language' => [
            ['theme', 'same-domain-root-with-index'],
            '/de/',
            200,
            'German site - Same domain root German with index',
            'fr',
            'same-domain-root-with-index.local',
        ];

        yield 'Renders the 404 exception if the locale does not match' => [
            ['theme', 'root-with-index'],
            '/de/',
            404,
            'Not Found',
            'de,fr',
            'root-with-index.local',
        ];

        yield 'Renders the 404 exception if the locale does not exist' => [
            ['theme', 'root-without-fallback-language'],
            '/fr/',
            404,
            'Not Found',
            'de,fr',
            'root-without-fallback-language.local',
        ];

        yield 'Redirects to the correct language if first page does not have index alias' => [
            ['theme', 'language-index-mix'],
            '/',
            302,
            'Redirecting to https://example.com/de/',
            'de,en',
            'example.com',
        ];

        yield 'Redirects to preferred language and region' => [
            ['theme', 'language-and-region'],
            '/',
            302,
            'Redirecting to https://example.com/de-CH/',
            'de,de-CH,fr',
            'example.com',
        ];

        yield 'Redirects to preferred language and ignores region if it does not exist' => [
            ['theme', 'language-and-region'],
            '/',
            302,
            'Redirecting to https://example.com/it-CH/',
            'it-IT,de',
            'example.com',
        ];

        yield 'Redirects to the language region by root page sorting' => [
            ['theme', 'language-and-region'],
            '/',
            302,
            'Redirecting to https://example.com/de-CH/',
            'de',
            'example.com',
        ];
    }

    public function testOrdersThePageModelsByCandidates(): void
    {
        $request = 'https://root-zh.local/main/sub-zh.html';

        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['HTTP_HOST'] = 'root-zh.local';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en';
        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        $client = $this->createClient([], $_SERVER);
        System::setContainer($client->getContainer());

        $this->loadFixtureFiles(['theme', 'language-sorting']);

        $crawler = $client->request('GET', $request);
        $title = trim($crawler->filterXPath('//head/title')->text());
        $response = $client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('', $title);
    }

    public function testRendersLoginPageWhenRootIsProtected(): void
    {
        $request = 'https://protected-root.local/';

        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['HTTP_HOST'] = 'protected-root.local';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en';
        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        $client = $this->createClient([], $_SERVER);
        System::setContainer($client->getContainer());

        $this->loadFixtureFiles(['theme', 'protected-root']);

        $crawler = $client->request('GET', $request);
        $title = trim($crawler->filterXPath('//head/title')->text());
        $response = $client->getResponse();

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('Error 401 Page', $title);
    }

    /**
     * @dataProvider getUrlPrefixMixProvider
     */
    public function testUrlPrefixMix(string $request, string $acceptLanguage, int $statusCode, string $pageTitle): void
    {
        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['HTTP_HOST'] = 'example.local';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $acceptLanguage;
        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        $client = $this->createClient([], $_SERVER);
        System::setContainer($client->getContainer());

        $this->loadFixtureFiles(['theme', 'url-prefix-mix']);

        $crawler = $client->request('GET', "https://example.local$request");
        $title = trim($crawler->filterXPath('//head/title')->text());
        $response = $client->getResponse();

        $this->assertSame($statusCode, $response->getStatusCode());
        $this->assertStringContainsString($pageTitle, $title);
    }

    public function getUrlPrefixMixProvider(): \Generator
    {
        yield 'Renders the index page of supported accept language' => [
            '/',
            'nl',
            200,
            'Dutch site',
        ];

        yield 'Renders the index page of root with url prefix' => [
            '/en/',
            'en',
            200,
            'English site',
        ];

        yield 'Renders the index page of root without url prefix' => [
            '/',
            'en',
            200,
            'Dutch site',
        ];

        yield 'Renders the english 404 with "en" accept language' => [
            '/nl/',
            'en',
            404,
            'English 404 - English root',
        ];

        yield 'Renders the dutch 404 with "nl" accept language' => [
            '/nl/',
            'nl',
            404,
            'Dutch 404 - Dutch root',
        ];

        yield 'Renders the fallback root 404 on invalid prefix with unsupported accept language' => [
            '/nl/',
            'fr',
            404,
            'English 404 - English root',
        ];
    }

    private function loadFixtureFiles(array $fileNames): void
    {
        // Do not reload the fixtures if they have not changed
        if (self::$lastImport && self::$lastImport === $fileNames) {
            return;
        }

        self::$lastImport = $fileNames;

        static::loadFixtures(array_map(
            static fn ($file) => __DIR__.'/../Fixtures/Functional/Routing/'.$file.'.yaml',
            $fileNames,
        ));
    }
}
