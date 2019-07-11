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
use Contao\FrontendTemplate;
use Contao\System;
use FOS\HttpCache\ResponseTagger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;

class ContentElementControllerTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        System::setContainer($this->getContainerWithContaoConfiguration());
    }

    public function testCreatesTheTemplateFromTheClassName(): void
    {
        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('ce_test'));

        $controller(new Request(), new ContentModel(), 'main');
    }

    public function testCreatesTheTemplateFromTheTypeFragmentOptions(): void
    {
        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('ce_foo'));
        $controller->setFragmentOptions(['type' => 'foo']);

        $controller(new Request(), new ContentModel(), 'main');
    }

    public function testCreatesTheTemplateFromTheTemplateFragmentOption(): void
    {
        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('ce_bar'));
        $controller->setFragmentOptions(['template' => 'ce_bar']);

        $controller(new Request(), new ContentModel(), 'main');
    }

    public function testCreatesTheTemplateFromCustomTpl(): void
    {
        $model = new ContentModel();
        $model->customTpl = 'ce_bar';

        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('ce_bar'));

        $controller(new Request(), $model, 'main');
    }

    public function testSetsTheClassFromTheType(): void
    {
        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('ce_test'));

        $response = $controller(new Request(), new ContentModel(), 'main');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('', $template['cssID']);
        $this->assertSame('ce_test', $template['class']);
    }

    public function testSetsTheHeadlineFromTheModel(): void
    {
        $model = new ContentModel();
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
        $model = new ContentModel();
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

        $response = $controller(new Request(), new ContentModel(), 'left');
        $template = json_decode($response->getContent(), true);

        $this->assertSame('left', $template['inColumn']);
    }

    public function testSetsTheClasses(): void
    {
        $controller = new TestController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('ce_test'));

        $response = $controller(new Request(), new ContentModel(), 'main', ['first', 'last']);
        $template = json_decode($response->getContent(), true);

        $this->assertSame('ce_test first last', $template['class']);
    }

    public function testAddsTheCacheTags(): void
    {
        $model = new ContentModel();
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

        $model = new ContentModel();
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

        $model = new ContentModel();
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

        $response = $controller(new Request(), new ContentModel(), 'main');

        $this->assertNull($response->getMaxAge());
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

        return $container;
    }
}
