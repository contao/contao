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
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

class FrontendModuleControllerTest extends TestCase
{
    use ExpectDeprecationTrait;

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

        $this->resetStaticProperties([System::class, Config::class]);

        parent::tearDown();
    }

    /**
     * @group legacy
     */
    public function testCreatesTheTemplateFromTheClassName(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $controller = $this->getTestController();

        $response = $controller(new Request([], [], ['_scope' => 'frontend']), $this->getModuleModel(), 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('mod_test', $template['templateName']);
    }

    /**
     * @group legacy
     */
    public function testCreatesTheTemplateFromTheTypeFragmentOption(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $controller = $this->getTestController(['type' => 'foo']);

        $response = $controller(new Request(), $this->getModuleModel(), 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('mod_foo', $template['templateName']);
    }

    /**
     * @group legacy
     */
    public function testCreatesTheTemplateFromTheTemplateFragmentOption(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $controller = $this->getTestController(['template' => 'mod_bar']);

        $response = $controller(new Request(), $this->getModuleModel(), 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('mod_bar', $template['templateName']);
    }

    /**
     * @group legacy
     */
    public function testCreatesTheTemplateFromACustomTpl(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $this->container->set('request_stack', new RequestStack());

        $controller = $this->getTestController();

        $model = $this->getModuleModel(['customTpl' => 'mod_bar']);

        $response = $controller(new Request(), $model, 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('mod_bar', $template['templateName']);
    }

    /**
     * @group legacy
     */
    public function testSetsTheClassFromTheType(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $controller = $this->getTestController();

        $response = $controller(new Request(), $this->getModuleModel(), 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('', $template['cssID']);
        $this->assertSame('mod_test', $template['class']);
    }

    /**
     * @group legacy
     */
    public function testSetsTheHeadlineFromTheModel(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $controller = $this->getTestController();

        $model = $this->getModuleModel(['headline' => serialize(['unit' => 'h6', 'value' => 'foobar'])]);

        $response = $controller(new Request(), $model, 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('foobar', $template['headline']);
        $this->assertSame('h6', $template['hl']);
    }

    /**
     * @group legacy
     */
    public function testSetsTheCssIdAndClassFromTheModel(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $controller = $this->getTestController();

        $model = $this->getModuleModel(['cssID' => serialize(['foo', 'bar'])]);

        $response = $controller(new Request(), $model, 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame(' id="foo"', $template['cssID']);
        $this->assertSame('mod_test bar', $template['class']);
    }

    /**
     * @group legacy
     */
    public function testSetsTheLayoutSection(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $controller = $this->getTestController();

        $response = $controller(new Request(), $this->getModuleModel(), 'left');
        $template = json_decode($response->getContent(), true);

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

        $model = $this->getModuleModel([
            'headline' => serialize(['value' => 'foo', 'unit' => 'h3']),
            'cssID' => serialize(['foo-id', 'foo-class']),
        ]);

        $response = $controller(new Request(), $model, 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('html', $template['type']);
        $this->assertSame('frontend_module/html', $template['template']);
        $this->assertFalse($template['as_overview']);
        $this->assertSame('main', $template['section']);
        $this->assertSame('main', $template['section']);
        $this->assertSame(['id' => 'foo-id', 'class' => 'foo-class'], $template['attributes']);
        $this->assertSame(['text' => 'foo', 'tagName' => 'h3'], $template['headline']);
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

        $model = $this->getModuleModel([
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

    /**
     * @group legacy
     */
    public function testAddsTheCacheTags(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $model = $this->getModuleModel();
        $model->id = 42;

        $entityCacheTags = $this->createMock(EntityCacheTags::class);
        $entityCacheTags
            ->expects($this->once())
            ->method('tagWith')
            ->with($model)
        ;

        $container = $this->mockContainerWithFrameworkTemplate('mod_test');
        $container->set('contao.cache.entity_tags', $entityCacheTags);

        $controller = new TestController();
        $controller->setContainer($container);

        $controller(new Request(), $model, 'main');
    }

    private function mockContainerWithFrameworkTemplate(string $templateName): ContainerBuilder
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->method('createInstance')
            ->willReturnCallback(
                function (string $class, array $params) use ($templateName): FragmentTemplate {
                    $this->assertSame(FragmentTemplate::class, $class);
                    $this->assertSame($templateName, $params[0]);

                    return new FragmentTemplate(...$params);
                }
            )
        ;

        $this->container->set('contao.framework', $framework);
        $this->container->set('contao.routing.scope_matcher', $this->mockScopeMatcher());

        return $this->container;
    }

    private function getModuleModel(array $data = []): ModuleModel
    {
        /** @var ModuleModel $model */
        $model = (new \ReflectionClass(ModuleModel::class))->newInstanceWithoutConstructor();
        $model->setRow($data);

        return $model;
    }

    private function getTestController(array $fragmentOptions = []): TestController
    {
        $controller = new TestController();

        $controller->setContainer($this->container);
        $controller->setFragmentOptions($fragmentOptions);

        return $controller;
    }
}
