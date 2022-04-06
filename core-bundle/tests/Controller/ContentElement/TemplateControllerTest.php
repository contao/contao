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
use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\CoreBundle\Controller\ContentElement\TemplateController;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendTemplate;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class TemplateControllerTest extends TestCase
{
    use ExpectDeprecationTrait;

    /**
     * @group legacy
     */
    public function testWithDataInput(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $data = [
            ['key' => 'Key 1', 'value' => 'Value 1'],
            ['key' => 'Key 1', 'value' => 'Value 1'],
        ];

        $container = $this->mockContainer($data, 'ce_template');

        $contentModel = $this->mockClassWithProperties(ContentModel::class);
        $contentModel->data = serialize($data);

        $controller = new TemplateController();
        $controller->setContainer($container);

        $controller(new Request(), $contentModel, 'main');
    }

    /**
     * @group legacy
     */
    public function testWithoutDataInput(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $container = $this->mockContainer([], 'ce_template');

        $contentModel = $this->mockClassWithProperties(ContentModel::class);
        $contentModel->data = null;

        $controller = new TemplateController();
        $controller->setContainer($container);

        $controller(new Request(), $contentModel, 'main');
    }

    /**
     * @group legacy
     */
    public function testWithCustomTemplate(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 5.0: Creating fragments with legacy templates is deprecated and will not work anymore in Contao 6.');

        $data = [
            ['key' => 'Key 1', 'value' => 'Value 1'],
            ['key' => 'Key 1', 'value' => 'Value 1'],
        ];

        $container = $this->mockContainer($data, 'ce_template_custom1');

        $contentModel = $this->mockClassWithProperties(ContentModel::class);
        $contentModel->data = serialize($data);
        $contentModel->customTpl = 'ce_template_custom1';

        $controller = new TemplateController();
        $controller->setContainer($container);

        $controller(new Request(), $contentModel, 'main');
    }

    private function mockContainer(array $expectedData, string $expectedTemplate): Container
    {
        $template = $this->createMock(FrontendTemplate::class);
        $template
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn(new Response())
        ;

        $template
            ->method('__set')
            ->withConsecutive(
                [$this->equalTo('headline'), $this->isNull()],
                [$this->equalTo('hl'), $this->equalTo('h1')],
                [$this->equalTo('class'), $this->equalTo('ce_template')],
                [$this->equalTo('cssID'), $this->equalTo('')],
                [$this->equalTo('inColumn'), $this->equalTo('main')],
                [$this->equalTo('data'), $this->equalTo($expectedData)],
            )
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(FrontendTemplate::class, [$expectedTemplate])
            ->willReturn($template)
        ;

        $container = new Container();
        $container->set('contao.framework', $framework);
        $container->set('request_stack', $this->createMock(RequestStack::class));
        $container->set('contao.cache.entity_tags', $this->createMock(EntityCacheTags::class));
        $container->set('contao.routing.scope_matcher', $this->createMock(ScopeMatcher::class));

        return $container;
    }
}
