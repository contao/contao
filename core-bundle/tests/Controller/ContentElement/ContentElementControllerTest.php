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
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ContentElementControllerTest extends TestCase
{
    use ExpectDeprecationTrait;

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

    /**
     * @group legacy
     */
    public function testCreatesTheTemplateFromTheClassName(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $controller = $this->getTestController();

        $response = $controller(new Request(), $this->getContentModel(), 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('ce_test', $template['templateName']);
    }

    /**
     * @group legacy
     */
    public function testCreatesTheTemplateFromTheTypeFragmentOptions(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $controller = $this->getTestController(['type' => 'foo']);

        $response = $controller(new Request(), $this->getContentModel(), 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('ce_foo', $template['templateName']);
    }

    /**
     * @group legacy
     */
    public function testCreatesTheTemplateFromTheTemplateFragmentOption(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $controller = $this->getTestController(['template' => 'ce_bar']);

        $response = $controller(new Request(), $this->getContentModel(), 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('ce_bar', $template['templateName']);
    }

    /**
     * @group legacy
     */
    public function testCreatesTheTemplateFromACustomTpl(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $this->container->set('request_stack', new RequestStack());

        $controller = $this->getTestController(['template' => 'ce_bar']);

        $model = $this->getContentModel(['customTpl' => 'ce_bar']);

        $response = $controller(new Request(), $model, 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('ce_bar', $template['templateName']);
    }

    /**
     * @group legacy
     */
    public function testDoesNotCreateTheTemplateFromACustomTplInTheBackend(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $request = new Request([], [], ['_scope' => 'backend']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $this->container->set('request_stack', $requestStack);
        $this->container->set('contao.routing.scope_matcher', $this->mockScopeMatcher());

        $controller = $this->getTestController();

        $model = $this->getContentModel(['customTpl' => 'ce_bar']);

        $response = $controller($request, $model, 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('ce_test', $template['templateName']);
    }

    /**
     * @group legacy
     */
    public function testSetsTheClassFromTheType(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $controller = $this->getTestController();

        $response = $controller(new Request(), $this->getContentModel(), 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('', $template['cssID']);
        $this->assertSame('ce_test', $template['class']);
    }

    /**
     * @group legacy
     */
    public function testSetsTheHeadlineFromTheModel(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $controller = $this->getTestController();

        $model = $this->getContentModel(['headline' => serialize(['unit' => 'h6', 'value' => 'foobar'])]);

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

        $model = $this->getContentModel(['cssID' => serialize(['foo', 'bar'])]);

        $response = $controller(new Request(), $model, 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame(' id="foo"', $template['cssID']);
        $this->assertSame('ce_test bar', $template['class']);
    }

    /**
     * @group legacy
     */
    public function testSetsTheLayoutSection(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $controller = $this->getTestController();

        $response = $controller(new Request(), $this->getContentModel(), 'left');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('left', $template['inColumn']);
    }

    /**
     * @group legacy
     */
    public function testSetsTheClasses(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $controller = $this->getTestController();

        $response = $controller(new Request(), $this->getContentModel(), 'main', ['first', 'last']);
        $template = json_decode($response->getContent(), true);

        $this->assertSame('ce_test first last', $template['class']);
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

        $model = $this->getContentModel([
            'headline' => serialize(['value' => 'foo', 'unit' => 'h3']),
            'cssID' => serialize(['foo-id', 'foo-class']),
        ]);

        $response = $controller(new Request(), $model, 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('text', $template['type']);
        $this->assertSame('content_element/text', $template['template']);
        $this->assertSame($backendScope, $template['as_overview']);
        $this->assertSame('main', $template['section']);
        $this->assertSame(['id' => 'foo-id', 'class' => 'foo-class'], $template['attributes']);
        $this->assertSame(['text' => 'foo', 'tagName' => 'h3'], $template['headline']);
        $this->assertSame($model->row(), $template['data']);
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

        $model = $this->getContentModel(['id' => 42]);

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

    /**
     * @group legacy
     */
    public function testSetsTheSharedMaxAgeIfTheElementHasAStartDate(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        ClockMock::withClockMock(true);

        $time = time();
        $start = strtotime('+2 weeks', $time);
        $expires = $start - $time;

        $model = $this->getContentModel(['start' => (string) $start]);

        $controller = new TestSharedMaxAgeController();
        $controller->setContainer($this->container);

        $response = $controller(new Request(), $model, 'main');

        $this->assertSame($expires, $response->getMaxAge());

        ClockMock::withClockMock(false);
    }

    /**
     * @group legacy
     */
    public function testSetsTheSharedMaxAgeIfTheElementHasAStopDate(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        ClockMock::withClockMock(true);

        $time = time();
        $stop = strtotime('+2 weeks', $time);
        $expires = $stop - $time;

        $model = $this->getContentModel(['stop' => (string) $stop]);

        $controller = new TestSharedMaxAgeController();
        $controller->setContainer($this->container);

        $response = $controller(new Request(), $model, 'main');

        $this->assertSame($expires, $response->getMaxAge());

        ClockMock::withClockMock(false);
    }

    /**
     * @group legacy
     */
    public function testDoesNotSetTheSharedMaxAgeIfTheElementHasNeitherAStartNorAStopDate(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $controller = new TestSharedMaxAgeController();
        $controller->setContainer($this->container);

        $response = $controller(new Request(), $this->getContentModel(), 'main');

        $this->assertNull($response->getMaxAge());
    }

    private function getContentModel(array $data = []): ContentModel
    {
        /** @var ContentModel $model */
        $model = (new \ReflectionClass(ContentModel::class))->newInstanceWithoutConstructor();
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
