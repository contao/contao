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
use Contao\CoreBundle\Controller\ContentElement\TemplateController;
use Contao\FrontendTemplate;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class TemplateControllerTest extends ContaoTestCase
{
    public function testWithDataInput(): void
    {
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

    public function testWithoutDataInput(): void
    {
        $container = $this->mockContainer([], 'ce_template');

        $contentModel = $this->mockClassWithProperties(ContentModel::class);
        $contentModel->data = null;

        $controller = new TemplateController();
        $controller->setContainer($container);

        $controller(new Request(), $contentModel, 'main');
    }

    public function testWithCustomTemplate(): void
    {
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

        return $container;
    }
}
