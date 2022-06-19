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
use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\CoreBundle\Fixtures\Controller\ContentElement\TestController;
use Contao\CoreBundle\Fixtures\Controller\ContentElement\TestSharedMaxAgeController;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\System;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ContentElementControllerTest extends TestCase
{
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->getContainerWithContaoConfiguration();
        $this->container->set('contao.cache.entity_tags', $this->createMock(EntityCacheTags::class));
        $this->container->set('contao.twig.filesystem_loader', $this->createMock(ContaoFilesystemLoader::class));

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
        $controller = $this->getTestController();

        $response = $controller(new Request(), $this->mockClassWithProperties(ContentModel::class), 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('ce_test', $template['templateName']);
    }

    public function testCreatesTheTemplateFromTheTypeFragmentOptions(): void
    {
        $controller = $this->getTestController(['type' => 'foo']);

        $response = $controller(new Request(), $this->mockClassWithProperties(ContentModel::class), 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('ce_foo', $template['templateName']);
    }

    public function testCreatesTheTemplateFromTheTemplateFragmentOption(): void
    {
        $controller = $this->getTestController(['template' => 'ce_bar']);

        $response = $controller(new Request(), $this->mockClassWithProperties(ContentModel::class), 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('ce_bar', $template['templateName']);
    }

    public function testCreatesTheTemplateFromACustomTpl(): void
    {
        $this->container->set('request_stack', new RequestStack());

        $controller = $this->getTestController(['template' => 'ce_bar']);

        $model = $this->mockClassWithProperties(ContentModel::class, ['customTpl' => 'ce_bar']);

        $response = $controller(new Request(), $model, 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('ce_bar', $template['templateName']);
    }

    public function testDoesNotCreateTheTemplateFromACustomTplInTheBackend(): void
    {
        $request = new Request([], [], ['_scope' => 'backend']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $this->container->set('request_stack', $requestStack);
        $this->container->set('contao.routing.scope_matcher', $this->mockScopeMatcher());

        $controller = $this->getTestController();

        $model = $this->mockClassWithProperties(ContentModel::class, ['customTpl' => 'ce_bar']);

        $response = $controller($request, $model, 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('ce_test', $template['templateName']);
    }

    public function testSetsTheClassFromTheType(): void
    {
        $controller = $this->getTestController();

        $response = $controller(new Request(), $this->mockClassWithProperties(ContentModel::class), 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('', $template['cssID']);
        $this->assertSame('ce_test', $template['class']);
    }

    public function testSetsTheHeadlineFromTheModel(): void
    {
        $controller = $this->getTestController();

        $model = $this->mockClassWithProperties(ContentModel::class, ['headline' => serialize(['unit' => 'h6', 'value' => 'foobar'])]);

        $response = $controller(new Request(), $model, 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('foobar', $template['headline']);
        $this->assertSame('h6', $template['hl']);
    }

    public function testSetsTheCssIdAndClassFromTheModel(): void
    {
        $controller = $this->getTestController();

        $model = $this->mockClassWithProperties(ContentModel::class, ['cssID' => serialize(['foo', 'bar'])]);

        $response = $controller(new Request(), $model, 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame(' id="foo"', $template['cssID']);
        $this->assertSame('ce_test bar', $template['class']);
    }

    public function testSetsTheLayoutSection(): void
    {
        $controller = $this->getTestController();

        $response = $controller(new Request(), $this->mockClassWithProperties(ContentModel::class), 'left');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('left', $template['inColumn']);
    }

    public function testSetsTheClasses(): void
    {
        $controller = $this->getTestController();

        $response = $controller(new Request(), $this->mockClassWithProperties(ContentModel::class), 'main', ['foo', 'bar']);
        $template = json_decode($response->getContent(), true);

        $this->assertSame('ce_test foo bar', $template['class']);
    }

    /**
     * @dataProvider provideScope
     */
    public function testSetsTemplateContextForModernFragments(bool $backendScope): void
    {
        $filesystemLoader = $this->createMock(ContaoFilesystemLoader::class);
        $filesystemLoader
            ->expects($this->once())
            ->method('exists')
            ->with('@Contao/content_element/text.html.twig')
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
            ->willReturn($backendScope)
        ;

        $this->container->set('request_stack', $requestStack);
        $this->container->set('contao.twig.filesystem_loader', $filesystemLoader);
        $this->container->set('contao.routing.scope_matcher', $scopeMatcher);

        $controller = $this->getTestController(['type' => 'text', 'template' => 'content_element/text']);

        $model = $this->mockClassWithProperties(ContentModel::class, [
            'headline' => serialize(['value' => 'foo', 'unit' => 'h3']),
            'cssID' => serialize(['foo-id', 'foo-class']),
        ]);

        $response = $controller(new Request(), $model, 'main', ['bar-class', 'baz-class']);
        $template = json_decode($response->getContent(), true);

        $this->assertSame('text', $template['type']);
        $this->assertSame('content_element/text', $template['template']);
        $this->assertSame($backendScope, $template['as_editor_view']);
        $this->assertSame('main', $template['section']);
        $this->assertSame('foo-id', $template['element_html_id']);
        $this->assertSame('foo-class bar-class baz-class', $template['element_css_classes']);
        $this->assertSame(['text' => 'foo', 'tag_name' => 'h3'], $template['headline']);
        $this->assertSame($model->row(), $template['data']);
    }

    public function provideScope(): \Generator
    {
        yield 'frontend' => [false];
        yield 'backend' => [true];
    }

    public function testAddsTheCacheTags(): void
    {
        $model = $this->mockClassWithProperties(ContentModel::class, ['id' => 42]);

        $entityCacheTags = $this->createMock(EntityCacheTags::class);
        $entityCacheTags
            ->expects($this->once())
            ->method('tagWith')
            ->with($model)
        ;

        $this->container->set('contao.cache.entity_tags', $entityCacheTags);

        $controller = $this->getTestController();

        $controller(new Request(), $model, 'main');
    }

    public function testSetsTheSharedMaxAgeIfTheElementHasAStartDate(): void
    {
        ClockMock::withClockMock(true);

        $time = time();
        $start = strtotime('+2 weeks', $time);
        $expires = $start - $time;

        $model = $this->mockClassWithProperties(ContentModel::class, ['start' => (string) $start]);

        $controller = new TestSharedMaxAgeController();
        $controller->setContainer($this->container);

        $response = $controller(new Request(), $model, 'main');

        $this->assertSame($expires, $response->getMaxAge());

        ClockMock::withClockMock(false);
    }

    public function testSetsTheSharedMaxAgeIfTheElementHasAStopDate(): void
    {
        ClockMock::withClockMock(true);

        $time = time();
        $stop = strtotime('+2 weeks', $time);
        $expires = $stop - $time;

        $model = $this->mockClassWithProperties(ContentModel::class, ['stop' => (string) $stop]);

        $controller = new TestSharedMaxAgeController();
        $controller->setContainer($this->container);

        $response = $controller(new Request(), $model, 'main');

        $this->assertSame($expires, $response->getMaxAge());

        ClockMock::withClockMock(false);
    }

    public function testDoesNotSetTheSharedMaxAgeIfTheElementHasNeitherAStartNorAStopDate(): void
    {
        $controller = new TestSharedMaxAgeController();
        $controller->setContainer($this->container);

        $response = $controller(new Request(), $this->mockClassWithProperties(ContentModel::class), 'main');

        $this->assertNull($response->getMaxAge());
    }

    private function getTestController(array $fragmentOptions = []): TestController
    {
        $controller = new TestController();

        $controller->setContainer($this->container);
        $controller->setFragmentOptions($fragmentOptions);

        return $controller;
    }
}
