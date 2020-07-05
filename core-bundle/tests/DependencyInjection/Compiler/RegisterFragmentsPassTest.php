<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\Controller\FrontendModule\TwoFactorController;
use Contao\CoreBundle\DependencyInjection\Compiler\RegisterFragmentsPass;
use Contao\CoreBundle\EventListener\DataContainer\CustomTemplateOptionsCallback;
use Contao\CoreBundle\Fragment\FragmentPreHandlerInterface;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\Compiler\ResolveClassPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class RegisterFragmentsPassTest extends TestCase
{
    public function testRegistersTheFragments(): void
    {
        $contentController = new Definition('App\Fragments\Text');
        $contentController->addTag('contao.content_element');

        $moduleController = new Definition('App\Fragments\LoginController');
        $moduleController->addTag('contao.frontend_module');

        $container = $this->getContainerWithContaoConfiguration();
        $container->setDefinition('app.fragments.content_controller', $contentController);
        $container->setDefinition('app.fragments.module_controller', $moduleController);

        (new ResolveClassPass())->process($container);

        $pass = new RegisterFragmentsPass(ContentElementReference::TAG_NAME);
        $pass->process($container);

        $pass = new RegisterFragmentsPass(FrontendModuleReference::TAG_NAME);
        $pass->process($container);

        $methodCalls = $container->getDefinition('contao.fragment.registry')->getMethodCalls();

        $this->assertSame('add', $methodCalls[0][0]);
        $this->assertSame('contao.content_element.text', $methodCalls[0][1][0]);
        $this->assertRegExp('/^contao.fragment._config_/', (string) $methodCalls[0][1][1]);

        $this->assertSame('add', $methodCalls[1][0]);
        $this->assertSame('contao.frontend_module.two_factor', $methodCalls[1][1][0]);
        $this->assertRegExp('/^contao.fragment._config_/', (string) $methodCalls[1][1][1]);

        $this->assertSame('add', $methodCalls[2][0]);
        $this->assertSame('contao.frontend_module.login', $methodCalls[2][1][0]);
        $this->assertRegExp('/^contao.fragment._config_/', (string) $methodCalls[2][1][1]);

        $arguments = $container->getDefinition((string) $methodCalls[1][1][1])->getArguments();

        $this->assertSame(TwoFactorController::class, $arguments[0]);
        $this->assertSame('forward', $arguments[1]);

        $arguments = $container->getDefinition((string) $methodCalls[2][1][1])->getArguments();

        $this->assertSame('app.fragments.module_controller', $arguments[0]);
        $this->assertSame('forward', $arguments[1]);
    }

    public function testUsesTheGivenAttributes(): void
    {
        $attributes = [
            'type' => 'foo',
            'renderer' => 'esi',
            'method' => 'bar',
        ];

        $contentController = new Definition('App\Fragments\Text');
        $contentController->addTag('contao.content_element', $attributes);

        $container = $this->getContainerWithContaoConfiguration();
        $container->setDefinition('app.fragments.content_controller', $contentController);

        (new ResolveClassPass())->process($container);
        $pass = new RegisterFragmentsPass(ContentElementReference::TAG_NAME);
        $pass->process($container);

        $methodCalls = $container->getDefinition('contao.fragment.registry')->getMethodCalls();

        $this->assertSame('add', $methodCalls[0][0]);
        $this->assertSame('contao.content_element.foo', $methodCalls[0][1][0]);
        $this->assertStringMatchesFormat('contao.fragment._config_%S', (string) $methodCalls[0][1][1]);

        $arguments = $container->getDefinition((string) $methodCalls[0][1][1])->getArguments();

        $this->assertSame('app.fragments.content_controller:bar', $arguments[0]);
        $this->assertSame('esi', $arguments[1]);
    }

    public function testMakesFragmentServicesPublic(): void
    {
        $contentController = new Definition('App\Fragments\Text');
        $contentController->setPublic(false);
        $contentController->addTag('contao.content_element');

        $container = $this->getContainerWithContaoConfiguration();
        $container->setDefinition('app.fragments.content_controller', $contentController);

        (new ResolveClassPass())->process($container);

        $this->assertFalse($container->findDefinition('app.fragments.content_controller')->isPublic());

        $pass = new RegisterFragmentsPass(ContentElementReference::TAG_NAME);
        $pass->process($container);

        $this->assertTrue($container->findDefinition('app.fragments.content_controller')->isPublic());
    }

    public function testRegistersThePreHandlers(): void
    {
        $contentController = new Definition(FragmentPreHandlerInterface::class);
        $contentController->addTag('contao.content_element');

        $container = $this->getContainerWithContaoConfiguration();
        $container->setDefinition('app.fragments.content_controller', $contentController);

        (new ResolveClassPass())->process($container);

        $pass = new RegisterFragmentsPass(ContentElementReference::TAG_NAME);
        $pass->process($container);

        $arguments = $container->getDefinition('contao.fragment.pre_handlers')->getArguments();

        $this->assertArrayHasKey('contao.content_element.fragment_pre_handler_interface', $arguments[0]);

        $this->assertSame(
            'app.fragments.content_controller',
            (string) $arguments[0]['contao.content_element.fragment_pre_handler_interface']
        );
    }

    public function testFailsIfThereIsNoPreHandlersDefinition(): void
    {
        $contentController = new Definition('App\Fragments\Text');
        $contentController->addTag('contao.content_element');

        $container = new ContainerBuilder();
        $container->setDefinition(CustomTemplateOptionsCallback::class, new Definition());
        $container->setDefinition('contao.fragment.registry', new Definition());
        $container->setDefinition('contao.command.debug_fragments', new Definition());
        $container->setDefinition('app.fragments.content_controller', $contentController);

        (new ResolveClassPass())->process($container);

        $pass = new RegisterFragmentsPass(ContentElementReference::TAG_NAME);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Missing service definition for "contao.fragment.pre_handlers"');

        $pass->process($container);
    }

    public function testDoesNothingIfThereIsNoFragmentRegistry(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects($this->never())
            ->method('findDefinition')
        ;

        $pass = new RegisterFragmentsPass(ContentElementReference::TAG_NAME);
        $pass->process($container);
    }

    public function testRegistersCustomTemplateOptions(): void
    {
        $contentController = new Definition('App\Fragments\Text');
        $contentController->addTag('contao.content_element', ['template' => 'ce_foo']);

        $moduleController = new Definition('App\Fragments\LoginController');
        $moduleController->addTag('contao.frontend_module', ['template' => 'mod_foo']);

        $container = $this->getContainerWithContaoConfiguration();
        $container->setDefinition('app.fragments.content_controller', $contentController);
        $container->setDefinition('app.fragments.module_controller', $moduleController);

        (new ResolveClassPass())->process($container);

        $pass = new RegisterFragmentsPass(ContentElementReference::TAG_NAME);
        $pass->process($container);

        $pass = new RegisterFragmentsPass(FrontendModuleReference::TAG_NAME);
        $pass->process($container);

        $methodCalls = $container->getDefinition(CustomTemplateOptionsCallback::class)->getMethodCalls();

        $this->assertSame([
            [
                'setFragmentTemplate',
                [
                    'tl_content',
                    'text',
                    'ce_foo',
                ],
            ],
            [
                'setFragmentTemplate',
                [
                    'tl_module',
                    'login',
                    'mod_foo',
                ],
            ],
        ], $methodCalls);
    }
}
