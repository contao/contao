<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\ContentElement;

use Contao\Config;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Controller\ContentElement\RootPageDependentElementsController;
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
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

class RootPageDependentElementsControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME']);

        $this->resetStaticProperties([System::class, Config::class]);

        parent::tearDown();
    }

    #[DataProvider('provideSelectedFragments')]
    public function testRendersTheFragmentOfTheCurrentRootPage(bool $isContentElement): void
    {
        $page = $this->createClassWithPropertiesStub(PageModel::class);
        $page->rootId = 1;

        $model = $this->createClassWithPropertiesStub(ContentModel::class);
        $model->rootPageDependentElements = serialize([1 => $isContentElement ? 'content-10' : '10', 2 => 'content-20']);
        $model->cssID = serialize(['wrapper-id', ' wrapper-class ']);
        $model->classes = [' foo ', 'bar'];

        $request = new Request([], [], ['_scope' => 'frontend', 'pageModel' => $page]);
        $requestStack = new RequestStack([$request]);

        $modelClass = $isContentElement ? ContentModel::class : ModuleModel::class;
        $selectedData = [
            'id' => 10,
            'origId' => 11,
            'type' => 'element_group',
            'cssID' => serialize(['selected-id', ' selected-class ']),
        ];

        $selectedModel = $this->createClassWithPropertiesStub($modelClass, $selectedData);
        $selectedModel
            ->method('cloneDetached')
            ->willReturn($this->createClassWithPropertiesStub($modelClass, $selectedData))
        ;

        $nestedReference = new ContentElementReference($this->createStub(ContentModel::class));
        $compositor = $this->createMock(FragmentCompositor::class);
        $compositor
            ->expects($isContentElement ? $this->once() : $this->never())
            ->method('getNestedFragments')
            ->with('contao.content_element.element_group', 11)
            ->willReturn([$nestedReference])
        ;

        $contentAdapter = $this->createAdapterMock(['findById']);
        $contentAdapter
            ->expects($this->once())
            ->method('findById')
            ->with('10')
            ->willReturn($selectedModel)
        ;

        $renderMethod = $isContentElement ? 'getContentElement' : 'getFrontendModule';

        $controllerAdapter = $this->createAdapterMock([$renderMethod]);
        $controllerAdapter
            ->expects($this->once())
            ->method($renderMethod)
            ->willReturnCallback(
                function (ContentElementReference|FrontendModuleReference $reference) use ($isContentElement, $selectedModel, $nestedReference): string {
                    $this->assertSame($isContentElement, $reference instanceof ContentElementReference);
                    $this->assertSame('header', $reference->getSection());

                    $fragmentModel = $reference instanceof ContentElementReference ? $reference->getContentModel() : $reference->getModuleModel();
                    $this->assertNotSame($selectedModel, $fragmentModel);
                    $this->assertSame(['wrapper-id', 'selected-class wrapper-class foo bar'], $fragmentModel->cssID);

                    if ($reference instanceof ContentElementReference) {
                        $this->assertSame([$nestedReference], $reference->attributes['nestedFragments']);
                    }

                    return '<p>example-content</p>';
                },
            )
        ;

        $framework = $this->createContaoFrameworkStub([Controller::class => $controllerAdapter, $modelClass => $contentAdapter]);

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.cache.tag_manager', $this->createStub(CacheTagManager::class));
        $container->set('contao.framework', $framework);
        $container->set('contao.routing.page_finder', new PageFinder($framework, $this->createStub(RequestMatcherInterface::class), $requestStack));
        $container->set('contao.routing.scope_matcher', $this->mockScopeMatcher());
        $container->set('request_stack', $requestStack);

        $container->set('contao.fragment.compositor', $compositor);
        $container->set('contao.twig.interop.context_factory', new ContextFactory($this->mockScopeMatcher()));

        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../../contao/templates', 'Contao');

        $filesystemLoader = $this->createStub(ContaoFilesystemLoader::class);
        $filesystemLoader
            ->method('exists')
            ->willReturn(true)
        ;

        $container->set('contao.twig.filesystem_loader', $filesystemLoader);

        $twig = new Environment($loader, ['strict_variables' => true]);
        $extension = new ContaoExtension(
            $twig,
            $filesystemLoader,
            $this->createStub(ContaoVariable::class),
            new InspectorNodeVisitor($this->createStub(Storage::class), $twig),
        );

        foreach ($extension->getFunctions() as $function) {
            $twig->addFunction($function);
        }

        $twig->addRuntimeLoader(new FactoryRuntimeLoader([FragmentRuntime::class => static fn () => new FragmentRuntime($framework)]));
        $container->set('twig', $twig);

        System::setContainer($container);

        $controller = new RootPageDependentElementsController();
        $controller->setContainer($container);
        $controller->setFragmentOptions(['template' => 'content_element/root_page_dependent_elements']);

        $this->assertSame('<p>example-content</p>', trim($controller($request, $model, 'header')->getContent()));
        $this->assertSame(['selected-id', ' selected-class '], StringUtil::deserialize($selectedModel->cssID, true));
    }

    public static function provideSelectedFragments(): iterable
    {
        yield 'content element' => [true];
        yield 'frontend module' => [false];
    }
}
