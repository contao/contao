<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\Matcher;

use Contao\CoreBundle\Routing\Matcher\LanguageFilter;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class LanguageFilterTest extends TestCase
{
    /**
     * @dataProvider getRoutesAndLanguages
     */
    public function testRemovesARouteIfTheAcceptedLanguagesDoNotMatch(string $name, PageModel|null $page, string $acceptLanguage, bool $expectRemoval): void
    {
        $route = $this->createMock(Route::class);
        $route
            ->expects($this->once())
            ->method('getDefault')
            ->with('pageModel')
            ->willReturn($page)
        ;

        $collection = $this->createMock(RouteCollection::class);
        $collection
            ->expects($this->once())
            ->method('all')
            ->willReturn([$name => $route])
        ;

        $collection
            ->expects($expectRemoval ? $this->once() : $this->never())
            ->method('remove')
        ;

        $request = Request::create('/');
        $request->headers->set('Accept-Language', $acceptLanguage);

        $filter = new LanguageFilter();
        $filter->filter($collection, $request);
    }

    public function getRoutesAndLanguages(): \Generator
    {
        yield 'Removes a fallback page route if the accepted language does not match' => [
            'tl_page.2.fallback',
            $this->mockClassWithProperties(PageModel::class, ['rootIsFallback' => false, 'rootLanguage' => 'en', 'urlPrefix' => '']),
            'de',
            true,
        ];

        yield 'Removes a root page route if the accepted language does not match' => [
            'tl_page.2.root',
            $this->mockClassWithProperties(PageModel::class, ['rootIsFallback' => false, 'rootLanguage' => 'en', 'urlPrefix' => '']),
            'de',
            true,
        ];

        yield 'Does not remove a route if there is no Contao page object' => [
            'tl_page.2.fallback',
            null,
            'de',
            false,
        ];

        yield 'Does not remove a route if the root page is the language fallback' => [
            'tl_page.2.root',
            $this->mockClassWithProperties(PageModel::class, ['rootIsFallback' => true, 'rootLanguage' => 'en', 'urlPrefix' => '']),
            'de',
            false,
        ];

        yield 'Does not remove a route if the root page language is accepted' => [
            'tl_page.2.root',
            $this->mockClassWithProperties(PageModel::class, ['rootIsFallback' => false, 'rootLanguage' => 'de', 'urlPrefix' => '']),
            'de',
            false,
        ];

        yield 'Does not remove a route if the root page language with region code is accepted' => [
            'tl_page.2.root',
            $this->mockClassWithProperties(PageModel::class, ['rootIsFallback' => false, 'rootLanguage' => 'de', 'urlPrefix' => '']),
            'de-CH',
            false,
        ];

        yield 'Does not remove a route if the root page language with region code equals the accepted language' => [
            'tl_page.2.root',
            $this->mockClassWithProperties(PageModel::class, ['rootIsFallback' => false, 'rootLanguage' => 'de-CH', 'urlPrefix' => '']),
            'de-CH',
            false,
        ];
    }
}
