<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\FragmentRegistry\FrontendModule;

use Contao\CoreBundle\DependencyInjection\Compiler\FragmentRegistryPass;
use Contao\CoreBundle\FragmentRegistry\FragmentRegistry;
use Contao\CoreBundle\FragmentRegistry\FragmentRegistryInterface;
use Contao\CoreBundle\FragmentRegistry\FrontendModule\DefaultFrontendModuleRenderer;
use Contao\CoreBundle\Tests\TestCase;
use Contao\ModuleModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;

class DefaultFrontendModuleRendererTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(
            'Contao\CoreBundle\FragmentRegistry\FrontendModule\DefaultFrontendModuleRenderer',
            $this->mockRenderer()
        );
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
            FragmentRegistryPass::TAG_FRAGMENT_FRONTEND_MODULE.'.identifier',
            new \stdClass(),
            [
                'tag' => FragmentRegistryPass::TAG_FRAGMENT_FRONTEND_MODULE,
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
