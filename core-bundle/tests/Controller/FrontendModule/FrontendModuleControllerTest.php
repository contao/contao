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

use Contao\CoreBundle\Fixtures\Controller\FrontendModule\TestController;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendTemplate;
use Contao\ModuleModel;
use Contao\System;
use FOS\HttpCache\ResponseTagger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;

class FrontendModuleControllerTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        System::setContainer($this->mockContainer());
    }

    public function testCanBeInstantiated(): void
    {
        $controller = new TestController();

        $this->assertInstanceOf('Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController', $controller);
        $this->assertInstanceOf('Contao\CoreBundle\Controller\AbstractFragmentController', $controller);
    }

    public function testCreatesTheTemplateFromTheClassName(): void
    {
        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_test'));

        $controller(new Request([], [], ['_scope' => 'frontend']), new ModuleModel(), 'main');
    }

    public function testCreatesTheTemplateFromTheTypeFragmentOption(): void
    {
        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_foo'));
        $controller->setFragmentOptions(['type' => 'foo']);

        $controller(new Request(), new ModuleModel(), 'main');
    }

    public function testCreatesTheTemplateFromTheTemplateFragmentOption(): void
    {
        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_bar'));
        $controller->setFragmentOptions(['template' => 'mod_bar']);

        $controller(new Request(), new ModuleModel(), 'main');
    }

    public function testCreatesTheTemplateFromCustomTpl(): void
    {
        $model = new ModuleModel();
        $model->customTpl = 'mod_bar';

        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_bar'));

        $controller(new Request(), $model, 'main');
    }

    public function testSetsTheClassFromTheType(): void
    {
        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_test'));

        $response = $controller(new Request(), new ModuleModel(), 'main');
        $template = json_decode($response->getContent());

        $this->assertSame('', $template->cssID);
        $this->assertSame('mod_test', $template->class);
    }

    public function testSetsTheHeadlineFromTheModel(): void
    {
        $model = new ModuleModel();
        $model->headline = serialize(['unit' => 'h6', 'value' => 'foobar']);

        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_test'));

        $response = $controller(new Request(), $model, 'main');
        $template = json_decode($response->getContent());

        $this->assertSame('foobar', $template->headline);
        $this->assertSame('h6', $template->hl);
    }

    public function testSetsTheCssIdAndClassFromTheModel(): void
    {
        $model = new ModuleModel();
        $model->cssID = serialize(['foo', 'bar']);

        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_test'));

        $response = $controller(new Request(), $model, 'main');
        $template = json_decode($response->getContent());

        $this->assertSame(' id="foo"', $template->cssID);
        $this->assertSame('mod_test bar', $template->class);
    }

    public function testSetsTheLayoutSection(): void
    {
        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_test'));

        $response = $controller(new Request(), new ModuleModel(), 'left');
        $template = json_decode($response->getContent());

        $this->assertSame('left', $template->inColumn);
    }

    public function testAddsTheCacheTags(): void
    {
        $model = new ModuleModel();
        $model->id = 42;

        $responseTagger = $this->createMock(ResponseTagger::class);
        $responseTagger
            ->expects($this->once())
            ->method('addTags')
            ->with(['contao.db.tl_module.42'])
        ;

        $container = $this->mockContainerWithFrameworkTemplate('mod_test');
        $container->set('fos_http_cache.http.symfony_response_tagger', $responseTagger);

        $controller = new TestController();
        $controller->setContainer($container);

        $controller(new Request(), $model, 'main');
    }

    private function mockContainerWithFrameworkTemplate(string $templateName): ContainerBuilder
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(FrontendTemplate::class, [$templateName])
            ->willReturn(new FrontendTemplate())
        ;

        $container = new ContainerBuilder();
        $container->set('contao.framework', $framework);
        $container->set('contao.routing.scope_matcher', $this->mockScopeMatcher());

        return $container;
    }
}
