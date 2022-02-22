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
use Contao\CoreBundle\Fixtures\Controller\FrontendModule\TestController;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FragmentTemplate;
use Contao\FrontendTemplate;
use Contao\ModuleModel;
use Contao\System;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class FrontendModuleControllerTest extends TestCase
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

        $this->resetStaticProperties([System::class, Config::class]);

        parent::tearDown();
    }

    public function testCreatesTheTemplateFromTheClassName(): void
    {
        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_test'));

        $controller(new Request([], [], ['_scope' => 'frontend']), $this->getModuleModel(), 'main');
    }

    public function testCreatesTheTemplateFromTheTypeFragmentOption(): void
    {
        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_foo'));
        $controller->setFragmentOptions(['type' => 'foo']);

        $controller(new Request(), $this->getModuleModel(), 'main');
    }

    public function testCreatesTheTemplateFromTheTemplateFragmentOption(): void
    {
        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_bar'));
        $controller->setFragmentOptions(['template' => 'mod_bar']);

        $controller(new Request(), $this->getModuleModel(), 'main');
    }

    public function testCreatesTheTemplateFromACustomTpl(): void
    {
        $model = $this->getModuleModel();
        $model->customTpl = 'mod_bar';

        $container = $this->mockContainerWithFrameworkTemplate('mod_bar');
        $container->set('request_stack', new RequestStack());

        $controller = new TestController();
        $controller->setContainer($container);

        $response = $controller(new Request(), $model, 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('mod_bar', $template['templateName']);
    }

    public function testSetsTheClassFromTheType(): void
    {
        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_test'));

        $response = $controller(new Request(), $this->getModuleModel(), 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('', $template['cssID']);
        $this->assertSame('mod_test', $template['class']);
    }

    public function testSetsTheHeadlineFromTheModel(): void
    {
        $model = $this->getModuleModel();
        $model->headline = serialize(['unit' => 'h6', 'value' => 'foobar']);

        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_test'));

        $response = $controller(new Request(), $model, 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('foobar', $template['headline']);
        $this->assertSame('h6', $template['hl']);
    }

    public function testSetsTheCssIdAndClassFromTheModel(): void
    {
        $model = $this->getModuleModel();
        $model->cssID = serialize(['foo', 'bar']);

        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_test'));

        $response = $controller(new Request(), $model, 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame(' id="foo"', $template['cssID']);
        $this->assertSame('mod_test bar', $template['class']);
    }

    public function testSetsTheLayoutSection(): void
    {
        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_test'));

        $response = $controller(new Request(), $this->getModuleModel(), 'left');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('left', $template['inColumn']);
    }

    public function testAddsTheCacheTags(): void
    {
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

    public function testUsesFragmentTemplateForSubrequests(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(FragmentTemplate::class, ['mod_test'])
            ->willReturn(new FragmentTemplate('mod_test'))
        ;

        $this->container->set('contao.framework', $framework);
        $this->container->set('contao.routing.scope_matcher', $this->mockScopeMatcher());

        $currentRequest = new Request([], [], ['_scope' => 'frontend']);

        $requestStack = $this->container->get('request_stack');
        $requestStack->push(new Request()); // Main request
        $requestStack->push($currentRequest); // Sub request

        $controller = new TestController();
        $controller->setContainer($this->container);

        $controller($currentRequest, $this->getModuleModel(), 'main');
    }

    private function mockContainerWithFrameworkTemplate(string $templateName): ContainerBuilder
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(FrontendTemplate::class, [$templateName])
            ->willReturn(new FrontendTemplate($templateName))
        ;

        $this->container->set('contao.framework', $framework);
        $this->container->set('contao.routing.scope_matcher', $this->mockScopeMatcher());

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
