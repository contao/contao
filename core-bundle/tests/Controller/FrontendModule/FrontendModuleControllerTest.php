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
use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Fixtures\Controller\FrontendModule\TestController;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\ModuleModel;
use Contao\System;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

class FrontendModuleControllerTest extends TestCase
{
    private ContainerBuilder $container;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->getContainerWithContaoConfiguration();
        $this->container->set('contao.cache.entity_tags', $this->createMock(EntityCacheTags::class));

        System::setContainer($this->container);
    }

    #[\Override]
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME']);

        $this->resetStaticProperties([System::class, Config::class]);

        parent::tearDown();
    }

    public function testCreatesTheTemplateFromTheClassName(): void
    {
        $controller = $this->getTestController();

        $response = $controller(new Request([], [], ['_scope' => 'frontend']), $this->mockClassWithProperties(ModuleModel::class), 'main');
        $template = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('mod_test', $template['templateName']);
    }

    public function testCreatesTheTemplateFromTheTypeFragmentOption(): void
    {
        $controller = $this->getTestController(['type' => 'foo']);

        $response = $controller(new Request(), $this->mockClassWithProperties(ModuleModel::class), 'main');
        $template = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('mod_foo', $template['templateName']);
    }

    public function testCreatesTheTemplateFromTheTemplateFragmentOption(): void
    {
        $controller = $this->getTestController(['template' => 'mod_bar']);

        $response = $controller(new Request(), $this->mockClassWithProperties(ModuleModel::class), 'main');
        $template = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('mod_bar', $template['templateName']);
    }

    public function testCreatesTheTemplateFromACustomTpl(): void
    {
        $loader = $this->createMock(LoaderInterface::class);
        $loader
            ->method('exists')
            ->with('@Contao/mod_bar.html.twig')
            ->willReturn(true)
        ;

        $this->container->set('contao.twig.filesystem_loader', $loader);
        $this->container->set('request_stack', new RequestStack());

        $controller = $this->getTestController();

        $model = $this->mockClassWithProperties(ModuleModel::class, ['customTpl' => 'mod_bar']);

        $response = $controller(new Request(), $model, 'main');
        $template = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('mod_bar', $template['templateName']);
    }

    public function testSetsTheClassFromTheType(): void
    {
        $controller = $this->getTestController();

        $response = $controller(new Request(), $this->mockClassWithProperties(ModuleModel::class), 'main');
        $template = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('', $template['cssID']);
        $this->assertSame('mod_test', $template['class']);
    }

    public function testSetsTheHeadlineFromTheModel(): void
    {
        $controller = $this->getTestController();

        $model = $this->mockClassWithProperties(ModuleModel::class, ['headline' => serialize(['unit' => 'h6', 'value' => 'foobar'])]);

        $response = $controller(new Request(), $model, 'main');
        $template = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('foobar', $template['headline']);
        $this->assertSame('h6', $template['hl']);
    }

    public function testSetsTheCssIdAndClassFromTheModel(): void
    {
        $controller = $this->getTestController();

        $model = $this->mockClassWithProperties(ModuleModel::class, ['cssID' => serialize(['foo', 'bar'])]);

        $response = $controller(new Request(), $model, 'main');
        $template = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(' id="foo"', $template['cssID']);
        $this->assertSame('mod_test bar', $template['class']);
    }

    public function testSetsTheLayoutSection(): void
    {
        $controller = $this->getTestController();

        $response = $controller(new Request(), $this->mockClassWithProperties(ModuleModel::class), 'left');
        $template = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('left', $template['inColumn']);
    }

    public function testSetsTemplateContextForModernFragments(): void
    {
        $filesystemLoader = $this->createMock(ContaoFilesystemLoader::class);
        $filesystemLoader
            ->expects($this->once())
            ->method('exists')
            ->with('@Contao/frontend_module/html.html.twig')
            ->willReturn(true)
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->method('getCurrentRequest')
            ->willReturn($this->createMock(Request::class))
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->method('isBackendRequest')
            ->willReturn(false)
        ;

        $this->container->set('request_stack', $requestStack);
        $this->container->set('contao.twig.filesystem_loader', $filesystemLoader);
        $this->container->set('contao.routing.scope_matcher', $scopeMatcher);

        $controller = $this->getTestController(['type' => 'html', 'template' => 'frontend_module/html']);

        $model = $this->mockClassWithProperties(ModuleModel::class, [
            'headline' => serialize(['value' => 'foo', 'unit' => 'h3']),
            'cssID' => serialize(['foo-id', 'foo-class']),
        ]);

        $response = $controller(new Request(), $model, 'main', ['bar-class', 'baz-class']);
        $template = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('html', $template['type']);
        $this->assertSame('frontend_module/html', $template['template']);
        $this->assertFalse($template['as_editor_view']);
        $this->assertSame('main', $template['section']);
        $this->assertSame('main', $template['section']);
        $this->assertSame('foo-id', $template['element_html_id']);
        $this->assertSame('foo-class bar-class baz-class', $template['element_css_classes']);
        $this->assertSame(['text' => 'foo', 'tag_name' => 'h3'], $template['headline']);
        $this->assertSame($model->row(), $template['data']);
    }

    public function testReturnsWildCardInBackendScope(): void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->method('getCurrentRequest')
            ->willReturn($this->createMock(Request::class))
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->method('isBackendRequest')
            ->willReturn(true)
        ;

        $tokenManager = $this->createMock(ContaoCsrfTokenManager::class);
        $tokenManager
            ->method('getDefaultTokenValue')
            ->willReturn('<token>')
        ;

        $twig = $this->createMock(Environment::class);
        $twig
            ->method('render')
            ->with(
                '@Contao/backend/module_wildcard.html.twig',
                [
                    'id' => 42,
                    'name' => 'foo',
                    'title' => 'foo headline',
                    'request_token' => '<token>',
                    'type' => 'foobar',
                ],
            )
            ->willReturn('<rendered wildcard>')
        ;

        $this->container->set('request_stack', $requestStack);
        $this->container->set('contao.routing.scope_matcher', $scopeMatcher);
        $this->container->set('contao.csrf.token_manager', $tokenManager);
        $this->container->set('twig', $twig);

        $controller = $this->getTestController();

        $model = $this->mockClassWithProperties(ModuleModel::class, [
            'id' => 42,
            'type' => 'foobar',
            'name' => 'foo',
            'headline' => serialize(['value' => 'foo headline', 'unit' => 'h3']),
        ]);

        $response = $controller(new Request(), $model, 'main');

        $this->assertSame('<rendered wildcard>', $response->getContent());
    }

    public function provideScope(): \Generator
    {
        yield 'frontend' => [false];
        yield 'backend' => [true];
    }

    public function testAddsTheCacheTags(): void
    {
        $model = $this->mockClassWithProperties(ModuleModel::class);
        $model->id = 42;

        $entityCacheTags = $this->createMock(EntityCacheTags::class);
        $entityCacheTags
            ->expects($this->once())
            ->method('tagWith')
            ->with($model)
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->method('createInstance')
            ->willReturnCallback(
                function (string $class, array $params): FragmentTemplate {
                    $this->assertSame(FragmentTemplate::class, $class);
                    $this->assertSame('mod_test', $params[0]);

                    return new FragmentTemplate(...$params);
                },
            )
        ;

        $this->container->set('contao.framework', $framework);
        $this->container->set('contao.routing.scope_matcher', $this->mockScopeMatcher());
        $this->container->set('contao.cache.entity_tags', $entityCacheTags);

        $controller = new TestController();
        $controller->setContainer($this->container);

        $controller(new Request(), $model, 'main');
    }

    private function getTestController(array $fragmentOptions = []): TestController
    {
        $controller = new TestController();

        $controller->setContainer($this->container);
        $controller->setFragmentOptions($fragmentOptions);

        return $controller;
    }
}
