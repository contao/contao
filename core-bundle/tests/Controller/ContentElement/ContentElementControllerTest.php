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

use Contao\ContentModel;
use Contao\CoreBundle\Fixtures\Controller\ContentElement\TestController;
use Contao\CoreBundle\Fixtures\Controller\ContentElement\TestSharedMaxAgeController;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FragmentTemplate;
use Contao\FrontendTemplate;
use Contao\System;
use FOS\HttpCache\ResponseTagger;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ContentElementControllerTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->getContainerWithContaoConfiguration();

        System::setContainer($this->container);
    }

    public function testCreatesTheTemplateFromTheClassName(): void
    {
        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('ce_test'));

        $controller(new Request(), $this->getContentModel(), 'main');
    }

    public function testCreatesTheTemplateFromTheTypeFragmentOptions(): void
    {
        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('ce_foo'));
        $controller->setFragmentOptions(['type' => 'foo']);

        $controller(new Request(), $this->getContentModel(), 'main');
    }

    public function testCreatesTheTemplateFromTheTemplateFragmentOption(): void
    {
        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('ce_bar'));
        $controller->setFragmentOptions(['template' => 'ce_bar']);

        $controller(new Request(), $this->getContentModel(), 'main');
    }

    public function testCreatesTheTemplateFromACustomTpl(): void
    {
        $model = $this->getContentModel();
        $model->customTpl = 'ce_bar';

        $container = $this->mockContainerWithFrameworkTemplate('ce_bar');
        $container->set('request_stack', new RequestStack());

        $controller = new TestController();
        $controller->setContainer($container);

        $response = $controller(new Request(), $model, 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('ce_bar', $template['templateName']);
    }

    public function testDoesNotCreateTheTemplateFromACustomTplInTheBackend(): void
    {
        $model = $this->getContentModel();
        $model->customTpl = 'ce_bar';

        $request = new Request([], [], ['_scope' => 'backend']);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = $this->mockContainerWithFrameworkTemplate('ce_test');
        $container->set('request_stack', $requestStack);
        $container->set('contao.routing.scope_matcher', $this->mockScopeMatcher());

        $controller = new TestController();
        $controller->setContainer($container);

        $response = $controller($request, $model, 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('ce_test', $template['templateName']);
    }

    public function testSetsTheClassFromTheType(): void
    {
        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('ce_test'));

        $response = $controller(new Request(), $this->getContentModel(), 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('', $template['cssID']);
        $this->assertSame('ce_test', $template['class']);
    }

    public function testSetsTheHeadlineFromTheModel(): void
    {
        $model = $this->getContentModel();
        $model->headline = serialize(['unit' => 'h6', 'value' => 'foobar']);

        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('ce_test'));

        $response = $controller(new Request(), $model, 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('foobar', $template['headline']);
        $this->assertSame('h6', $template['hl']);
    }

    public function testSetsTheCssIdAndClassFromTheModel(): void
    {
        $model = $this->getContentModel();
        $model->cssID = serialize(['foo', 'bar']);

        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('ce_test'));

        $response = $controller(new Request(), $model, 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame(' id="foo"', $template['cssID']);
        $this->assertSame('ce_test bar', $template['class']);
    }

    public function testSetsTheLayoutSection(): void
    {
        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('ce_test'));

        $response = $controller(new Request(), $this->getContentModel(), 'left');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('left', $template['inColumn']);
    }

    public function testSetsTheClasses(): void
    {
        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('ce_test'));

        $response = $controller(new Request(), $this->getContentModel(), 'main', ['first', 'last']);
        $template = json_decode($response->getContent(), true);

        $this->assertSame('ce_test first last', $template['class']);
    }

    public function testAddsTheCacheTags(): void
    {
        $model = $this->getContentModel();
        $model->id = 42;

        $responseTagger = $this->createMock(ResponseTagger::class);
        $responseTagger
            ->expects($this->once())
            ->method('addTags')
            ->with(['contao.db.tl_content.42'])
        ;

        $container = $this->mockContainerWithFrameworkTemplate('ce_test');
        $container->set('fos_http_cache.http.symfony_response_tagger', $responseTagger);

        $controller = new TestController();
        $controller->setContainer($container);

        $controller(new Request(), $model, 'main');
    }

    public function testSetsTheSharedMaxAgeIfTheElementHasAStartDate(): void
    {
        $time = time();
        $start = strtotime('+2 weeks', $time);
        $expires = $start - $time;

        $model = $this->getContentModel();
        $model->start = (string) $start;

        $container = $this->mockContainerWithFrameworkTemplate('ce_test_shared_max_age');

        $controller = new TestSharedMaxAgeController();
        $controller->setContainer($container);

        $response = $controller(new Request(), $model, 'main');

        $this->assertSame($expires, $response->getMaxAge());
    }

    public function testSetsTheSharedMaxAgeIfTheElementHasAStopDate(): void
    {
        $time = time();
        $stop = strtotime('+2 weeks', $time);
        $expires = $stop - $time;

        $model = $this->getContentModel();
        $model->stop = (string) $stop;

        $container = $this->mockContainerWithFrameworkTemplate('ce_test_shared_max_age');

        $controller = new TestSharedMaxAgeController();
        $controller->setContainer($container);

        $response = $controller(new Request(), $model, 'main');

        $this->assertSame($expires, $response->getMaxAge());
    }

    public function testDoesNotSetTheSharedMaxAgeIfTheElementHasNeitherAStartNorAStopDate(): void
    {
        $container = $this->mockContainerWithFrameworkTemplate('ce_test_shared_max_age');

        $controller = new TestSharedMaxAgeController();
        $controller->setContainer($container);

        $response = $controller(new Request(), $this->getContentModel(), 'main');

        $this->assertNull($response->getMaxAge());
    }

    public function testUsesFragmentTemplateForSubrequests(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(FragmentTemplate::class, ['ce_test'])
            ->willReturn(new FragmentTemplate('ce_test'))
        ;

        $this->container->set('contao.framework', $framework);
        $this->container->set('contao.routing.scope_matcher', $this->mockScopeMatcher());

        $currentRequest = new Request([], [], ['_scope' => 'frontend']);

        $requestStack = $this->container->get('request_stack');
        $requestStack->push(new Request()); // Main request
        $requestStack->push($currentRequest); // Sub request

        $controller = new TestController();
        $controller->setContainer($this->container);

        $controller($currentRequest, $this->getContentModel(), 'main');
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

        return $this->container;
    }

    /**
     * @return ContentModel&MockObject
     */
    private function getContentModel(): ContentModel
    {
        return $this->mockClassWithProperties(ContentModel::class);
    }
}
