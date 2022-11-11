<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\FrontendModule;

use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\CoreBundle\Controller\FrontendModule\RootPageDependentModulesController;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\System;
use Contao\Template;
use Contao\TemplateLoader;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RootPageDependentModulesControllerTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->getContainerWithContaoConfiguration();
        $this->container->set('contao.cache.entity_tags', $this->createMock(EntityCacheTags::class));

        System::setContainer($this->container);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME']);

        $this->resetStaticProperties([TemplateLoader::class, System::class, Config::class]);

        parent::tearDown();
    }

    public function testReturnsWildCardWhenBackendRequest(): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(true)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->willReturn('Root Page Dependent Modules')
        ;

        $container = $this->mockContainer();
        $container->set('contao.routing.scope_matcher', $scopeMatcher);
        $container->set('router', $router);
        $container->set('translator', $translator);
        $container->setParameter('kernel.project_dir', \dirname(__DIR__, 3));

        TemplateLoader::addFile('be_wildcard', 'src/Resources/contao/templates/backend');

        $controller = new RootPageDependentModulesController();
        $controller->setContainer($container);

        $response = $controller(new Request([], [], ['_scope' => 'backend']), $this->getModuleModel(), 'main');

        $expectedOutput =
            <<<'OUTPUT'
                <div class="tl_gray">
                    <span class="upper">### ROOT PAGE DEPENDENT MODULES ###</span>
                </div>
                OUTPUT;

        $minifiedExpectedOutput = preg_replace(['/\s\s|\n/', '/\s</'], ['', '<'], $expectedOutput);

        $this->assertSame($minifiedExpectedOutput, preg_replace(['/\s\s|\n/', '/\s</'], ['', '<'], $response->getContent()));
    }

    public function testReturnsEmptyResponse(): void
    {
        $controller = new RootPageDependentModulesController();
        $controller->setContainer($this->mockContainer());

        $response = $controller(new Request([], [], ['_scope' => 'frontend']), $this->getModuleModel(), 'main');

        $this->assertSame('', $response->getContent());
    }

    public function testReturnsEmptyResponseWhenNoModulesConfigured(): void
    {
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->rootId = 1;

        $module = $this->mockClassWithProperties(ModuleModel::class);
        $module->rootPageDependentModules = serialize([]);

        $request = new Request([], [], ['_scope' => 'frontend', 'pageModel' => $page]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $controller = new RootPageDependentModulesController();
        $controller->setContainer($this->mockContainer($requestStack));

        $response = $controller($request, $module, 'main');

        $this->assertSame('', $response->getContent());
    }

    public function testPopulatesTheTemplateWithTheModule(): void
    {
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->rootId = 1;

        $module = $this->mockClassWithProperties(ModuleModel::class);
        $module->rootPageDependentModules = serialize([1 => '10']);

        $request = new Request([], [], ['_scope' => 'frontend', 'pageModel' => $page]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $controller = new RootPageDependentModulesController();
        $controller->setContainer($this->mockContainer($requestStack, 'example-content'));

        $response = $controller($request, $module, 'main');

        $this->assertSame('example-content', $response->getContent());
    }

    public function testThrowsExceptionWhenGetResponseIsCalled(): void
    {
        $controller = new RootPageDependentModulesController();

        $this->expectException(\LogicException::class);

        $controller->getResponse(
            $this->createMock(Template::class),
            $this->createMock(ModuleModel::class),
            new Request()
        );
    }

    private function mockContainer(RequestStack $requestStack = null, string $content = null): ContainerBuilder
    {
        $controllerAdapter = $this->mockAdapter(['getFrontendModule']);
        $controllerAdapter
            ->expects($content ? $this->once() : $this->never())
            ->method('getFrontendModule')
            ->willReturn($content ?? '')
        ;

        $framework = $this->mockContaoFramework([Controller::class => $controllerAdapter]);

        $this->container->set('contao.framework', $framework);
        $this->container->set('contao.routing.scope_matcher', $this->mockScopeMatcher());

        if ($requestStack instanceof RequestStack) {
            $this->container->set('request_stack', $requestStack);
        }

        return $this->container;
    }

    /**
     * @return ModuleModel&MockObject
     */
    private function getModuleModel(): ModuleModel
    {
        return $this->mockClassWithProperties(ModuleModel::class);
    }
}
