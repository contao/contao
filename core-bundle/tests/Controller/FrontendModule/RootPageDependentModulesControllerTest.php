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
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Controller\FrontendModule\RootPageDependentModulesController;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Fragment\FragmentCompositor;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Global\ContaoVariable;
use Contao\CoreBundle\Twig\Inspector\InspectorNodeVisitor;
use Contao\CoreBundle\Twig\Inspector\Storage;
use Contao\CoreBundle\Twig\Interop\ContextFactory;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Runtime\FragmentRuntime;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\TemplateLoader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

class RootPageDependentModulesControllerTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->getContainerWithContaoConfiguration();
        $this->container->set('contao.cache.tag_manager', $this->createStub(CacheTagManager::class));

        System::setContainer($this->container);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME']);

        $this->resetStaticProperties([TemplateLoader::class, System::class, Config::class]);

        parent::tearDown();
    }

    public function testReturnsEmptyResponse(): void
    {
        $controller = new RootPageDependentModulesController();
        $controller->setContainer($this->mockContainer());
        $controller->setFragmentOptions(['template' => 'frontend_module/root_page_dependent_modules']);

        $response = $controller(new Request([], [], ['_scope' => 'frontend']), $this->getModuleModel(), 'main');

        $this->assertSame('', $response->getContent());
    }

    public function testReturnsEmptyResponseWhenNoModulesConfigured(): void
    {
        $page = $this->createClassWithPropertiesStub(PageModel::class);
        $page->rootId = 1;

        $module = $this->createClassWithPropertiesStub(ModuleModel::class);
        $module->rootPageDependentModules = serialize([]);

        $request = new Request([], [], ['_scope' => 'frontend', 'pageModel' => $page]);

        $requestStack = new RequestStack([$request]);

        $controller = new RootPageDependentModulesController();
        $controller->setContainer($this->mockContainer($requestStack));
        $controller->setFragmentOptions(['template' => 'frontend_module/root_page_dependent_modules']);

        $response = $controller($request, $module, 'main');

        $this->assertSame('', $response->getContent());
    }

    #[DataProvider('provideSelectedFragments')]
    public function testRendersSelectedFragmentWithCssAttributes(bool $isContentElement, string $wrapperId, string|null $propertyId, string $expectedId): void
    {
        $page = $this->createClassWithPropertiesStub(PageModel::class);
        $page->rootId = 1;

        $module = $this->createClassWithPropertiesStub(ModuleModel::class);
        $module->rootPageDependentModules = serialize([1 => $isContentElement ? 'content-10' : '10']);
        $module->cssID = serialize([$wrapperId, ' wrapper-class ']);
        $module->classes = [' foo ', 'bar'];

        $selectedData = [
            'id' => 10,
            'type' => 'text',
            'cssID' => serialize(['selected-id', ' selected-class ']),
        ];

        $modelClass = $isContentElement ? ContentModel::class : ModuleModel::class;

        $selectedModel = $this->createClassWithPropertiesStub($modelClass, $selectedData);
        $selectedModel
            ->method('cloneDetached')
            ->willReturn($this->createClassWithPropertiesStub($modelClass, $selectedData))
        ;

        $request = new Request([], [], ['_scope' => 'frontend', 'pageModel' => $page]);

        if (null !== $propertyId) {
            $request->attributes->set('templateProperties', ['cssID' => ' id="'.$propertyId.'"']);
        }

        $requestStack = new RequestStack([$request]);

        $controller = new RootPageDependentModulesController();
        $controller->setContainer($this->mockContainer(
            $requestStack,
            $selectedModel,
            function (ContentElementReference|FrontendModuleReference $reference) use ($isContentElement, $expectedId, $selectedModel): string {
                $this->assertSame($isContentElement, $reference instanceof ContentElementReference);
                $this->assertSame('header', $reference->getSection());

                $fragmentModel = $reference instanceof ContentElementReference ? $reference->getContentModel() : $reference->getModuleModel();
                $this->assertNotSame($selectedModel, $fragmentModel);
                $this->assertSame([$expectedId, 'selected-class wrapper-class foo bar'], $fragmentModel->cssID);

                return '<p>example-content</p>';
            },
        ));

        $controller->setFragmentOptions(['template' => 'frontend_module/root_page_dependent_modules']);

        $response = $controller($request, $module, 'header');

        $this->assertSame('<p>example-content</p>', trim($response->getContent()));
        $this->assertSame(['selected-id', ' selected-class '], StringUtil::deserialize($selectedModel->cssID, true));
    }

    public static function provideSelectedFragments(): iterable
    {
        foreach ([false, true] as $isContentElement) {
            $type = $isContentElement ? 'content element' : 'frontend module';

            yield $type.' with selected ID' => [$isContentElement, '', null, 'selected-id'];
            yield $type.' with wrapper ID' => [$isContentElement, 'wrapper-id', null, 'wrapper-id'];
            yield $type.' with property ID' => [$isContentElement, 'wrapper-id', 'property-id', 'property-id'];
        }
    }

    public function testReturnsEmptyResponseWhenSelectedFragmentDoesNotExist(): void
    {
        $page = $this->createClassWithPropertiesStub(PageModel::class, ['rootId' => 1]);
        $request = new Request([], [], ['_scope' => 'frontend', 'pageModel' => $page]);
        $model = $this->createClassWithPropertiesStub(ModuleModel::class, ['rootPageDependentModules' => serialize([1 => '10'])]);

        $controller = new RootPageDependentModulesController();
        $controller->setContainer($this->mockContainer(new RequestStack([$request])));
        $controller->setFragmentOptions(['template' => 'frontend_module/root_page_dependent_modules']);

        $this->assertSame('', $controller($request, $model, 'main')->getContent());
    }

    public function testPreservesNestedContentFragments(): void
    {
        $page = $this->createClassWithPropertiesStub(PageModel::class, ['rootId' => 1]);
        $request = new Request([], [], ['_scope' => 'frontend', 'pageModel' => $page]);
        $model = $this->createClassWithPropertiesStub(ModuleModel::class, ['rootPageDependentModules' => serialize([1 => 'content-10'])]);
        $selectedData = ['id' => 10, 'origId' => 11, 'type' => 'element_group'];

        $selectedModel = $this->createClassWithPropertiesStub(ContentModel::class, $selectedData);
        $selectedModel
            ->method('cloneDetached')
            ->willReturn($this->createClassWithPropertiesStub(ContentModel::class, $selectedData))
        ;

        $nestedReference = new ContentElementReference($this->createStub(ContentModel::class));
        $container = $this->mockContainer(new RequestStack([$request]), $selectedModel, function (ContentElementReference $reference) use ($nestedReference): string {
            $this->assertSame([$nestedReference], $reference->attributes['nestedFragments']);

            return '<p>nested content</p>';
        });

        $compositor = $this->createMock(FragmentCompositor::class);
        $compositor
            ->expects($this->once())
            ->method('getNestedFragments')
            ->with('contao.content_element.element_group', 11)
            ->willReturn([$nestedReference])
        ;

        $container->set('contao.fragment.compositor', $compositor);

        $controller = new RootPageDependentModulesController();
        $controller->setContainer($container);
        $controller->setFragmentOptions(['template' => 'frontend_module/root_page_dependent_modules']);

        $this->assertSame('<p>nested content</p>', trim($controller($request, $model, 'main')->getContent()));
    }

    private function mockContainer(RequestStack|null $requestStack = null, ContentModel|ModuleModel|null $selectedModel = null, \Closure|null $onRender = null): ContainerBuilder
    {
        $moduleAdapter = $this->createAdapterStub(['findById']);
        $moduleAdapter
            ->method('findById')
            ->willReturn($selectedModel instanceof ModuleModel ? $selectedModel : null)
        ;

        $contentAdapter = $this->createAdapterStub(['findById']);
        $contentAdapter
            ->method('findById')
            ->willReturn($selectedModel instanceof ContentModel ? $selectedModel : null)
        ;

        $controllerAdapter = $this->createAdapterMock(['getFrontendModule', 'getContentElement']);

        foreach (['getFrontendModule' => ModuleModel::class, 'getContentElement' => ContentModel::class] as $method => $modelClass) {
            $controllerAdapter
                ->expects($selectedModel instanceof $modelClass ? $this->once() : $this->never())
                ->method($method)
                ->willReturnCallback($onRender ?? static fn () => '')
            ;
        }

        $framework = $this->createContaoFrameworkStub([
            Controller::class => $controllerAdapter,
            ModuleModel::class => $moduleAdapter,
            ContentModel::class => $contentAdapter,
        ]);

        $pageFinder = new PageFinder(
            $framework,
            $this->createStub(RequestMatcherInterface::class),
            $requestStack ?? new RequestStack(),
        );

        $this->container->set('contao.framework', $framework);
        $this->container->set('contao.routing.page_finder', $pageFinder);
        $this->container->set('contao.routing.scope_matcher', $this->mockScopeMatcher());
        $this->container->set('contao.fragment.compositor', new FragmentCompositor());
        $this->container->set('contao.twig.interop.context_factory', new ContextFactory($this->mockScopeMatcher()));

        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../../contao/templates/twig', 'Contao');

        $filesystemLoader = $this->createStub(ContaoFilesystemLoader::class);
        $filesystemLoader
            ->method('exists')
            ->willReturn(true)
        ;

        $this->container->set('contao.twig.filesystem_loader', $filesystemLoader);

        $twig = new Environment($loader, ['strict_variables' => true]);

        $extension = new ContaoExtension(
            $twig,
            $filesystemLoader,
            $this->createStub(ContaoCsrfTokenManager::class),
            $this->createStub(ContaoVariable::class),
            new InspectorNodeVisitor($this->createStub(Storage::class), $twig),
        );

        foreach ($extension->getFunctions() as $function) {
            $twig->addFunction($function);
        }

        foreach ($extension->getFilters() as $filter) {
            $twig->addFilter($filter);
        }

        $twig->addRuntimeLoader(new FactoryRuntimeLoader([FragmentRuntime::class => static fn () => new FragmentRuntime($framework)]));
        $this->container->set('twig', $twig);

        if ($requestStack) {
            $this->container->set('request_stack', $requestStack);
        }

        return $this->container;
    }

    private function getModuleModel(): ModuleModel&Stub
    {
        return $this->createClassWithPropertiesStub(ModuleModel::class);
    }
}
