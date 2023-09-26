<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Environment;
use Contao\Frontend;
use Contao\Input;
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\System;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @group legacy
 */
class RoutingTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupServerEnvGetPost();

        $GLOBALS['TL_CONFIG']['urlSuffix'] = '.html';
        $GLOBALS['TL_CONFIG']['addLanguageToUrl'] = false;
        Config::set('useAutoItem', false);

        Environment::reset();

        $container = new ContainerBuilder();
        $container->setParameter('kernel.charset', 'UTF-8');
        $container->setParameter('contao.legacy_routing', true);

        System::setContainer($container);

        $GLOBALS['TL_AUTO_ITEM'] = ['items'];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_AUTO_ITEM']);

        $this->restoreServerEnvGetPost();
        $this->resetStaticProperties([Input::class, Environment::class, System::class]);

        parent::tearDown();
    }

    public function testReturnsThePageIdFromTheUrl(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.7: Using "Contao\Frontend::getPageIdFromUrl()" has been deprecated %s.');

        $_SERVER['REQUEST_URI'] = 'home.html?foo=bar';

        $request = $this->createMock(Request::class);
        $request
            ->method('getBasePath')
            ->willReturn('')
        ;

        $request
            ->method('getScriptName')
            ->willReturn('index.php')
        ;

        $requestStack = new RequestStack();
        $requestStack->push($request);

        System::getContainer()->set('request_stack', $requestStack);

        $this->assertSame('home', Frontend::getPageIdFromUrl());
        $this->assertEmpty($_GET);
    }

    public function testReturnsNullIfTheRequestIsEmpty(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.7: Using "Contao\Frontend::getPageIdFromUrl()" has been deprecated %s.');

        $_SERVER['REQUEST_URI'] = '/';

        System::getContainer()->set('request_stack', new RequestStack());

        $this->assertNull(Frontend::getPageIdFromUrl());
        $this->assertEmpty($_GET);
    }

    public function testReturnsFalseIfTheRequestContainsAutoItem(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.7: Using "Contao\Frontend::getPageIdFromUrl()" has been deprecated %s.');

        $_SERVER['REQUEST_URI'] = 'home/auto_item/foo.html';

        $request = $this->createMock(Request::class);
        $request
            ->method('getBasePath')
            ->willReturn('')
        ;

        $request
            ->method('getScriptName')
            ->willReturn('index.php')
        ;

        $requestStack = new RequestStack();
        $requestStack->push($request);

        System::getContainer()->set('request_stack', $requestStack);

        $this->assertFalse(Frontend::getPageIdFromUrl());
        $this->assertEmpty($_GET);
    }

    public function testReturnsFalseIfTheUrlSuffixDoesNotMatch(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.7: Using "Contao\Frontend::getPageIdFromUrl()" has been deprecated %s.');

        $_SERVER['REQUEST_URI'] = 'home/auto_item/foo.xml';

        $request = $this->createMock(Request::class);
        $request
            ->method('getBasePath')
            ->willReturn('')
        ;

        $request
            ->method('getScriptName')
            ->willReturn('index.php')
        ;

        $requestStack = new RequestStack();
        $requestStack->push($request);

        System::getContainer()->set('request_stack', $requestStack);

        $this->assertFalse(Frontend::getPageIdFromUrl());
        $this->assertEmpty($_GET);
    }

    public function testReturnsFalseUponDuplicateParameters(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.7: Using "Contao\Frontend::getPageIdFromUrl()" has been deprecated %s.');

        $_SERVER['REQUEST_URI'] = 'home/foo/bar1/foo/bar2.html';

        $request = $this->createMock(Request::class);
        $request
            ->method('getBasePath')
            ->willReturn('')
        ;

        $request
            ->method('getScriptName')
            ->willReturn('index.php')
        ;

        $requestStack = new RequestStack();
        $requestStack->push($request);

        System::getContainer()->set('request_stack', $requestStack);
        System::getContainer()->set('contao.framework', $this->mockFrameworkWithPageAdapter());

        $this->assertFalse(Frontend::getPageIdFromUrl());
        $this->assertSame(['foo' => 'bar1'], $_GET);
    }

    public function testReturnsFalseIfTheRequestContainsAnAutoItemKeyword(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.7: Using "Contao\Frontend::getPageIdFromUrl()" has been deprecated %s.');

        $_SERVER['REQUEST_URI'] = 'home/items/bar.html';

        $request = $this->createMock(Request::class);
        $request
            ->method('getBasePath')
            ->willReturn('')
        ;

        $request
            ->method('getScriptName')
            ->willReturn('index.php')
        ;

        $requestStack = new RequestStack();
        $requestStack->push($request);

        System::getContainer()->set('request_stack', $requestStack);
        System::getContainer()->set('contao.framework', $this->mockFrameworkWithPageAdapter());
        Config::set('useAutoItem', true);

        $this->assertFalse(Frontend::getPageIdFromUrl());
        $this->assertEmpty($_GET);
    }

    public function testReturnsFalseIfAFragmentKeyIsEmpty(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.7: Using "Contao\Frontend::getPageIdFromUrl()" has been deprecated %s.');

        $_SERVER['REQUEST_URI'] = 'home//foo.html';

        $request = $this->createMock(Request::class);
        $request
            ->method('getBasePath')
            ->willReturn('')
        ;

        $request
            ->method('getScriptName')
            ->willReturn('index.php')
        ;

        $requestStack = new RequestStack();
        $requestStack->push($request);

        System::getContainer()->set('request_stack', $requestStack);
        System::getContainer()->set('contao.framework', $this->mockFrameworkWithPageAdapter());

        $this->assertFalse(Frontend::getPageIdFromUrl());
        $this->assertEmpty($_GET);
    }

    public function testDecodesTheRequestString(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.7: Using "Contao\Frontend::getPageIdFromUrl()" has been deprecated %s.');

        $_SERVER['REQUEST_URI'] = 'h%C3%B6me.html';

        $request = $this->createMock(Request::class);
        $request
            ->method('getBasePath')
            ->willReturn('')
        ;

        $request
            ->method('getScriptName')
            ->willReturn('index.php')
        ;

        $requestStack = new RequestStack();
        $requestStack->push($request);

        System::getContainer()->set('request_stack', $requestStack);

        $this->assertSame('höme', Frontend::getPageIdFromUrl());
        $this->assertEmpty($_GET);
    }

    /**
     * Needs to run in a separate process because it includes the functions.php file.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAddsTheAutoItemFragment(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.7: Using "Contao\Frontend::getPageIdFromUrl()" has been deprecated %s.');

        include_once __DIR__.'/../../contao/helper/functions.php';

        $_SERVER['REQUEST_URI'] = 'home/foo.html';

        $request = $this->createMock(Request::class);
        $request
            ->method('getBasePath')
            ->willReturn('')
        ;

        $request
            ->method('getScriptName')
            ->willReturn('index.php')
        ;

        $requestStack = new RequestStack();
        $requestStack->push($request);

        System::getContainer()->set('request_stack', $requestStack);
        System::getContainer()->set('contao.framework', $this->mockFrameworkWithPageAdapter());
        Config::set('useAutoItem', true);

        $this->assertSame('home', Frontend::getPageIdFromUrl());
        $this->assertSame(['auto_item' => 'foo'], $_GET);
    }

    public function testReturnsNullIfOnlyTheLanguageIsGiven(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.7: Using "Contao\Frontend::getPageIdFromUrl()" has been deprecated %s.');

        $_SERVER['REQUEST_URI'] = 'en/';

        $request = $this->createMock(Request::class);
        $request
            ->method('getBasePath')
            ->willReturn('')
        ;

        $request
            ->method('getScriptName')
            ->willReturn('index.php')
        ;

        $requestStack = new RequestStack();
        $requestStack->push($request);

        System::getContainer()->set('request_stack', $requestStack);
        $GLOBALS['TL_CONFIG']['addLanguageToUrl'] = true;

        $this->assertNull(Frontend::getPageIdFromUrl());
        $this->assertSame(['language' => 'en'], $_GET);
    }

    public function testReturnsFalseIfTheLanguageIsNotProvided(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.7: Using "Contao\Frontend::getPageIdFromUrl()" has been deprecated %s.');

        $_SERVER['REQUEST_URI'] = 'home.html';

        $request = $this->createMock(Request::class);
        $request
            ->method('getBasePath')
            ->willReturn('')
        ;

        $request
            ->method('getScriptName')
            ->willReturn('index.php')
        ;

        $requestStack = new RequestStack();
        $requestStack->push($request);

        System::getContainer()->set('request_stack', $requestStack);
        $GLOBALS['TL_CONFIG']['addLanguageToUrl'] = true;

        $this->assertFalse(Frontend::getPageIdFromUrl());
        $this->assertEmpty($_GET);
    }

    public function testReturnsFalseIfTheAliasIsEmpty(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.7: Using "Contao\Frontend::getPageIdFromUrl()" has been deprecated %s.');

        $_SERVER['REQUEST_URI'] = 'en//foo/bar.html';

        $request = $this->createMock(Request::class);
        $request
            ->method('getBasePath')
            ->willReturn('')
        ;

        $request
            ->method('getScriptName')
            ->willReturn('index.php')
        ;

        $requestStack = new RequestStack();
        $requestStack->push($request);

        System::getContainer()->set('request_stack', $requestStack);
        System::getContainer()->set('contao.framework', $this->mockFrameworkWithPageAdapter());
        $GLOBALS['TL_CONFIG']['addLanguageToUrl'] = true;
        Config::set('useAutoItem', true);

        $this->assertFalse(Frontend::getPageIdFromUrl());
        $this->assertSame(['language' => 'en'], $_GET);
    }

    public function testReturnsFalseIfThereAreNoFragments(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.7: Using "Contao\Frontend::getPageIdFromUrl()" has been deprecated %s.');

        $_SERVER['REQUEST_URI'] = '/.html';

        $request = $this->createMock(Request::class);
        $request
            ->method('getBasePath')
            ->willReturn('/foo')
        ;

        $request
            ->method('getScriptName')
            ->willReturn('index.php')
        ;

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $pageModel = $this->mockAdapter(['findByAliases']);
        $pageModel
            ->method('findByAliases')
            ->willReturn(null)
        ;

        System::getContainer()->set('request_stack', $requestStack);
        System::getContainer()->set('contao.framework', $this->mockFrameworkWithPageAdapter($pageModel));

        $this->assertFalse(Frontend::getPageIdFromUrl());
        $this->assertEmpty($_GET);
    }

    public function testHandlesFolderUrlsWithoutLanguage(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.7: Using "Contao\Frontend::getPageIdFromUrl()" has been deprecated %s.');

        $_SERVER['REQUEST_URI'] = 'foo/bar/home.html';
        $_SERVER['HTTP_HOST'] = 'domain.com';

        $request = $this->createMock(Request::class);
        $request
            ->method('getBasePath')
            ->willReturn('')
        ;

        $request
            ->method('getScriptName')
            ->willReturn('index.php')
        ;

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $page = $this->mockClassWithProperties(PageModel::class);
        $page->domain = '';
        $page->rootLanguage = 'en';
        $page->rootIsFallback = true;
        $page->alias = 'foo/bar/home';

        $page
            ->method('loadDetails')
            ->willReturn($page)
        ;

        $pageModel = $this->mockAdapter(['findByAliases']);
        $pageModel
            ->method('findByAliases')
            ->with($this->callback(
                function (array $options) {
                    $this->assertSame(['foo/bar/home', 'foo/bar', 'foo'], $options);

                    return true;
                }
            ))
            ->willReturn(new Collection([$page], 'tl_page'))
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageModel]);

        System::getContainer()->set('request_stack', $requestStack);
        System::getContainer()->set('contao.framework', $framework);

        $this->assertSame('foo/bar/home', Frontend::getPageIdFromUrl());
        $this->assertEmpty($_GET);
    }

    public function testHandlesFolderUrlsWithLanguage(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.7: Using "Contao\Frontend::getPageIdFromUrl()" has been deprecated %s.');

        $_SERVER['REQUEST_URI'] = 'en/foo/bar/home/news/test.html';
        $_SERVER['HTTP_HOST'] = 'domain.com';

        $request = $this->createMock(Request::class);
        $request
            ->method('getBasePath')
            ->willReturn('')
        ;

        $request
            ->method('getScriptName')
            ->willReturn('index.php')
        ;

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $page = $this->mockClassWithProperties(PageModel::class);
        $page->domain = 'domain.com';
        $page->rootLanguage = 'en';
        $page->rootIsFallback = true;
        $page->alias = 'foo/bar/home';

        $page
            ->method('loadDetails')
            ->willReturn($page)
        ;

        $pageModel = $this->mockAdapter(['findByAliases']);
        $pageModel
            ->method('findByAliases')
            ->with($this->callback(
                function (array $options) {
                    $this->assertSame(
                        [
                            'foo/bar/home/news/test',
                            'foo/bar/home/news',
                            'foo/bar/home',
                            'foo/bar',
                            'foo',
                        ],
                        $options
                    );

                    return true;
                }
            ))
            ->willReturn(new Collection([$page], 'tl_page'))
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageModel]);

        System::getContainer()->set('request_stack', $requestStack);
        System::getContainer()->set('contao.framework', $framework);
        $GLOBALS['TL_CONFIG']['addLanguageToUrl'] = true;

        $this->assertSame('foo/bar/home', Frontend::getPageIdFromUrl());
        $this->assertSame(['language' => 'en', 'news' => 'test'], $_GET);
    }

    public function testReturnsFalseIfThereAreNoAliases(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.7: Using "Contao\Frontend::getPageIdFromUrl()" has been deprecated %s.');

        $_SERVER['REQUEST_URI'] = 'foo/bar/home.html';
        $_SERVER['HTTP_HOST'] = 'domain.com';

        $request = $this->createMock(Request::class);
        $request
            ->method('getBasePath')
            ->willReturn('')
        ;

        $request
            ->method('getScriptName')
            ->willReturn('index.php')
        ;

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $page = $this->mockClassWithProperties(PageModel::class);
        $page->domain = 'domain.de';
        $page->rootLanguage = 'de';
        $page->rootIsFallback = false;
        $page->alias = 'startseite';

        $page
            ->method('loadDetails')
            ->willReturn($page)
        ;

        $pageModel = $this->mockAdapter(['findByAliases']);
        $pageModel
            ->method('findByAliases')
            ->with($this->callback(
                function (array $options) {
                    $this->assertSame(['foo/bar/home', 'foo/bar', 'foo'], $options);

                    return true;
                }
            ))
            ->willReturn(new Collection([$page], 'tl_page'))
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageModel]);

        System::getContainer()->set('request_stack', $requestStack);
        System::getContainer()->set('contao.framework', $framework);

        $this->assertFalse(Frontend::getPageIdFromUrl());
        $this->assertEmpty($_GET);
    }

    /**
     * @param Adapter<PageModel> $pageAdapter
     *
     * @return ContaoFramework&MockObject
     */
    private function mockFrameworkWithPageAdapter(Adapter $pageAdapter = null): ContaoFramework
    {
        if (null === $pageAdapter) {
            $pageAdapter = $this->mockAdapter(['findByAliases']);
            $pageAdapter
                ->expects($this->once())
                ->method('findByAliases')
                ->willReturn(null)
            ;
        }

        return $this->mockContaoFramework([PageModel::class => $pageAdapter]);
    }
}
