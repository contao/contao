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

use Contao\CoreBundle\Fixtures\Controller\Page\TestPageController;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\RouteConfig;
use Contao\System;
use Contao\TestCase\FunctionalTestCase;
use Symfony\Component\Routing\Route;

class PageControllerTest extends FunctionalTestCase
{
    private static ?array $lastImport = null;

    /**
     * @param string|false|null $path
     *
     * @dataProvider getPageController
     */
    public function testResolvesPageController(array $fixtures, string $request, $path, array $requirements, array $defaults): void
    {
        $_SERVER['REQUEST_URI'] = $request;
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en';
        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        $client = $this->createClient([], $_SERVER);
        $container = $client->getContainer();
        System::setContainer($container);

        $pageController = new TestPageController();
        $pageController->setContainer($container);
        $container->set(TestPageController::class, $pageController);

        $pathRegex = null;

        if (\is_string($path) && 0 === strncmp($path, '/', 1)) {
            $compiledRoute = (new Route($path, $defaults, $requirements))->compile();
            $pathRegex = $compiledRoute->getRegex();
        }

        $defaults['_controller'] = TestPageController::class;
        $routeConfig = new RouteConfig($path, $pathRegex, null, $requirements, [], $defaults);
        $container->get(PageRegistry::class)->add('foobar', $routeConfig);

        $this->loadFixtureFiles($fixtures);

        $client->request('GET', "https://example.com$request");
        $response = $client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
    }

    public function getPageController(): \Generator
    {
        foreach (['/test', '/test/5', '/test/5/abc'] as $request) {
            foreach ([true, false] as $withDefault) {
                foreach ([true, false] as $withSuffix) {
                    foreach ([true, false] as $withAlias) {
                        $description = sprintf(
                            'Request: %s, withDefault: %s, withSuffix: %s, withAlias: %s',
                            $request,
                            $withDefault ? 'yes' : 'no',
                            $withSuffix ? 'yes' : 'no',
                            $withAlias ? 'yes' : 'no'
                        );

                        yield $description => [
                            ['theme', ($withSuffix ? 'root-with-suffix' : 'root-without-suffix'), ($withAlias ? 'page-with-alias' : 'page-without-alias')],
                            $request.($withSuffix ? '.html' : ''),
                            '/test/{slug'.($withDefault ? '' : '?').'}',
                            ['slug' => '.+'],
                            $withDefault ? ['slug' => null] : [],
                        ];
                    }
                }
            }
        }
    }

    private function loadFixtureFiles(array $fileNames): void
    {
        // Do not reload the fixtures if they have not changed
        if (self::$lastImport && self::$lastImport === $fileNames) {
            return;
        }

        self::$lastImport = $fileNames;

        static::loadFixtures(array_map(
            static fn ($file) => __DIR__.'/../Fixtures/Functional/PageController/'.$file.'.yml',
            $fileNames
        ));
    }
}
