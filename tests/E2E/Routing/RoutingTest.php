<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\E2eTests\Routing;

use Contao\E2eTestBundle\Http\Origin;
use Contao\E2eTestBundle\ManagedEdition\ManagedEditionConfig;
use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use Contao\InstallationRecipe\Fixture\FixtureResult;
use Contao\InstallationRecipe\Fixture\FixtureSet;
use Contao\InstallationRecipe\Recipe\InstallationRecipe;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\BrowserKit\HttpBrowser;

class RoutingTest extends AbstractContaoMonorepoE2ETestCase
{
    #[DataProvider('getAliases')]
    public function testResolvesAliases(mixed ...$case): void
    {
        [$fixtures, $request, $statusCode, $pageTitle, , $host] = $case;
        $fixtureResult = $this->loadFixtureFiles($fixtures);
        $request = $fixtureResult->interpolate($request);
        $browser = $this->request($request, $host);

        $this->assertResponse($browser, $statusCode, $pageTitle);
    }

    public static function getAliases(): iterable
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
            '/{page_home}.html',
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

    #[DataProvider('getAliasesWithLocale')]
    public function testResolvesAliasesWithLocale(mixed ...$case): void
    {
        [$fixtures, $request, $statusCode, $pageTitle, , $host] = $case;
        $fixtureResult = $this->loadFixtureFiles($fixtures);
        $request = $fixtureResult->interpolate($request);
        self::managedEdition()->database()->connection()->executeStatement('UPDATE tl_page SET urlPrefix = language');
        $browser = $this->request($request, $host);

        $this->assertResponse($browser, $statusCode, $pageTitle);
    }

    public static function getAliasesWithLocale(): iterable
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
            '/en/{page_home}.html',
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

    #[DataProvider('getAliasesWithoutUrlSuffix')]
    public function testResolvesAliasesWithoutUrlSuffix(mixed ...$case): void
    {
        [$fixtures, $request, $statusCode, $pageTitle, , $host] = $case;
        $this->loadFixtureFiles($fixtures);
        self::managedEdition()->database()->connection()->executeStatement("UPDATE tl_page SET urlSuffix = ''");
        $browser = $this->request($request, $host);

        $this->assertResponse($browser, $statusCode, $pageTitle);
    }

    public static function getAliasesWithoutUrlSuffix(): iterable
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

    #[DataProvider('getRootAliases')]
    public function testResolvesTheRootPage(mixed ...$case): void
    {
        [$fixtures, $request, $statusCode, $pageTitle, $acceptLanguages, $host] = $case;
        $this->loadFixtureFiles($fixtures);
        $browser = $this->request($request, $host, $acceptLanguages);

        $this->assertResponse($browser, $statusCode, $pageTitle);
    }

    public static function getRootAliases(): iterable
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

    #[DataProvider('getRootAliasesWithLocale')]
    public function testResolvesTheRootPageWithLocale(mixed ...$case): void
    {
        [$fixtures, $request, $statusCode, $pageTitle, $acceptLanguages, $host] = $case;
        $this->loadFixtureFiles($fixtures);
        self::managedEdition()->database()->connection()->executeStatement("UPDATE tl_page SET urlPrefix = language WHERE urlPrefix = ''");
        $browser = $this->request($request, $host, $acceptLanguages);

        $this->assertResponse($browser, $statusCode, $pageTitle);
    }

    public static function getRootAliasesWithLocale(): iterable
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
        $this->loadFixtureFiles(['theme', 'language-sorting']);
        $browser = $this->request('/main/sub-zh.html', 'root-zh.local');

        $this->assertResponse($browser, 200, '');
    }

    /**
     * @see https://github.com/contao/contao/issues/6328
     */
    #[DataProvider('disabledLanguageRedirectsProvider')]
    public function testCorrectHandlesDisabledLanguageRedirects(mixed ...$case): void
    {
        [$disableLanguageRedirects, $indexAlias, $requestLocale, $expectedLocation] = $case;
        $request = 'https://example.local/';
        $this->loadFixtureFiles(['disable-language-redirect']);
        $disableLanguageRedirect = $disableLanguageRedirects ? 1 : 0;
        $alias = $indexAlias ? 'index' : 'home';
        $connection = self::managedEdition()->database()->connection();
        $connection->update('tl_page', ['disableLanguageRedirect' => $disableLanguageRedirect], ['alias' => 'nl', 'type' => 'root']);
        $connection->update('tl_page', ['alias' => $alias], ['type' => 'regular']);

        $browser = $this->request('/', 'example.local', $requestLocale);
        $response = $browser->getInternalResponse();

        if ($expectedLocation === $request) {
            $this->assertSame(200, $response->getStatusCode());
        } else {
            $this->assertSame(302, $response->getStatusCode());
            $this->assertSame($expectedLocation, $response->getHeader('Location'));
        }
    }

    public static function disabledLanguageRedirectsProvider(): iterable
    {
        // Redirects to fallback because it is the only route on path "/"
        yield 'unknown locale, alias=home, disableLanguageRedirect=1' => [
            true,
            false,
            'af',
            'https://example.local/en/',
        ];

        // Redirects to NL because its root page matches "/" before the fallback one
        yield 'unknown locale, alias=home, disableLanguageRedirect=0' => [
            false,
            false,
            'af',
            'https://example.local/home.html',
        ];

        // Redirects to fallback because it is the only route on path "/"
        yield 'secondary locale, alias=home, disableLanguageRedirect=1' => [
            true,
            false,
            'nl',
            'https://example.local/en/',
        ];

        // Redirects to NL because its root page matches "/" before the fallback one
        yield 'secondary locale, alias=home, disableLanguageRedirect=0' => [
            false,
            false,
            'nl',
            'https://example.local/home.html',
        ];

        // Redirects to fallback because it is the only route on path "/"
        yield 'fallback locale, alias=home, disableLanguageRedirect=1' => [
            true,
            false,
            'en',
            'https://example.local/en/',
        ];

        // Redirects to NL because its root page matches "/" before the fallback one
        yield 'fallback locale, alias=home, disableLanguageRedirect=0' => [
            false,
            false,
            'en',
            'https://example.local/home.html',
        ];

        // Renders the NL index page because it matches "/" before the fallback
        yield 'unknown locale, alias=index, disableLanguageRedirect=1' => [
            true,
            true,
            'af',
            'https://example.local/',
        ];

        // Renders the NL index page because it matches "/" before the fallback
        yield 'unknown locale, alias=index, disableLanguageRedirect=0' => [
            false,
            true,
            'af',
            'https://example.local/',
        ];

        // Renders the NL index page because it matches "/" before the fallback
        yield 'secondary locale, alias=index, disableLanguageRedirect=1' => [
            true,
            true,
            'nl',
            'https://example.local/',
        ];

        // Renders the NL index page because it matches "/" before the fallback
        yield 'secondary locale, alias=index, disableLanguageRedirect=0' => [
            false,
            true,
            'nl',
            'https://example.local/',
        ];

        // Renders the NL index page because it matches "/" before the fallback
        yield 'fallback locale, alias=index, disableLanguageRedirect=1' => [
            true,
            true,
            'en',
            'https://example.local/',
        ];

        // Renders the NL index page because it matches "/" before the fallback
        yield 'fallback locale, alias=index, disableLanguageRedirect=0' => [
            false,
            true,
            'en',
            'https://example.local/',
        ];
    }

    public function testRendersLoginPageWhenRootIsProtected(): void
    {
        $this->loadFixtureFiles(['theme', 'protected-root']);
        $browser = $this->request('/', 'protected-root.local');

        $this->assertResponse($browser, 401, 'Error 401 Page');
    }

    #[DataProvider('getUrlPrefixMixProvider')]
    public function testUrlPrefixMix(mixed ...$case): void
    {
        [$request, $acceptLanguage, $statusCode, $pageTitle] = $case;
        $this->loadFixtureFiles(['theme', 'url-prefix-mix']);
        $browser = $this->request($request, 'example.local', $acceptLanguage);

        $this->assertResponse($browser, $statusCode, $pageTitle);
    }

    public static function getUrlPrefixMixProvider(): iterable
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

    public function testMultidomainWithLanguages(): void
    {
        $this->loadFixtureFiles(['theme', 'multidomain-languages']);
        $browser = $this->request('/de/bar/bar', 'example.ch', 'en_US,en');

        $this->assertResponse($browser, 200, 'Bar -');
    }

    protected static function createManagedEditionConfig(): ManagedEditionConfig
    {
        // The routing fixtures contain news modules and records in tl_news and
        // tl_news_archive.
        $composer = self::createMonorepoComposerConfig('core-bundle', 'news-bundle');

        return ManagedEditionConfig::create(InstallationRecipe::create($composer), self::projectDirectory());
    }

    protected function shouldResetContaoManagedEdition(): bool
    {
        // Every routing case selects and loads its own fixture set.
        return false;
    }

    /**
     * @param list<string> $fileNames
     */
    private function loadFixtureFiles(array $fileNames): FixtureResult
    {
        return self::managedEdition()->prepareDatabase(new FixtureSet(array_map(
            static fn ($file) => \dirname(__DIR__, 3).'/core-bundle/tests/Fixtures/Functional/Routing/'.$file.'.yaml',
            $fileNames,
        )));
    }

    private function request(string $path, string $host, string $acceptLanguage = 'en'): HttpBrowser
    {
        $browser = self::managedEdition()->createHttpBrowser(Origin::https($host));
        $browser->setServerParameter('HTTP_ACCEPT', 'text/html');
        $browser->setServerParameter('HTTP_ACCEPT_LANGUAGE', $acceptLanguage);
        $browser->request('GET', $path);

        return $browser;
    }

    private function assertResponse(HttpBrowser $browser, int $statusCode, string $expectedTitle): void
    {
        $response = $browser->getInternalResponse();
        $crawler = $browser->getCrawler();
        $this->assertSame($statusCode, $response->getStatusCode());
        $title = trim($crawler->filterXPath('//head/title')->text());

        $this->assertStringContainsString($expectedTitle, $title);
    }
}
