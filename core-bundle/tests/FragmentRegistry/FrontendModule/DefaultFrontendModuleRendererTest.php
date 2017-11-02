<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Fragment\FrontendModule;

use Contao\CoreBundle\Fragment\FragmentRegistry;
use Contao\CoreBundle\Fragment\FragmentRegistryInterface;
use Contao\CoreBundle\Fragment\FrontendModule\DefaultFrontendModuleRenderer;
use Contao\ModuleModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;

class DefaultFrontendModuleRendererTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $renderer = $this->mockRenderer();

        $this->assertInstanceOf('Contao\CoreBundle\Fragment\FrontendModule\DefaultFrontendModuleRenderer', $renderer);
    }

    public function testSupportsModuleModels(): void
    {
        $this->assertTrue($this->mockRenderer()->supports(new ModuleModel()));
    }

    public function testRendersModuleModels(): void
    {
        $expectedControllerReference = new ControllerReference(
            'test',
            [
                'moduleModel' => 42,
                'inColumn' => 'main',
                'scope' => 'scope',
            ]
        );

        $registry = new FragmentRegistry();

        $registry->addFragment(
            FragmentRegistryInterface::FRONTEND_MODULE_FRAGMENT.'.identifier',
            new \stdClass(),
            [
                'tag' => FragmentRegistryInterface::FRONTEND_MODULE_FRAGMENT,
                'type' => 'test',
                'controller' => 'test',
                'category' => 'navigationMod',
            ]
        );

        $handler = $this->createMock(FragmentHandler::class);

        $handler
            ->expects($this->once())
            ->method('render')
            ->with($this->equalTo($expectedControllerReference))
        ;

        $model = new ModuleModel();
        $model->setRow(['id' => 42, 'type' => 'identifier']);

        $renderer = $this->mockRenderer($registry, $handler);
        $renderer->render($model, 'main', 'scope');
    }

    /**
     * Mocks a default front end module renderer.
     *
     * @param FragmentRegistryInterface|null $registry
     * @param FragmentHandler|null           $handler
     *
     * @return DefaultFrontendModuleRenderer
     */
    private function mockRenderer(FragmentRegistryInterface $registry = null, FragmentHandler $handler = null): DefaultFrontendModuleRenderer
    {
        if (null === $registry) {
            $registry = new FragmentRegistry();
        }

        if (null === $handler) {
            $handler = $this->createMock(FragmentHandler::class);
        }

        return new DefaultFrontendModuleRenderer($registry, $handler, new RequestStack());
    }
}
