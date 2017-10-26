<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\RegisterHooksPass;
use Contao\CoreBundle\Framework\ContaoFramework;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class RegisterHooksPassTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(
            'Contao\CoreBundle\DependencyInjection\Compiler\RegisterHooksPass',
            new RegisterHooksPass()
        );
    }

    public function testRegistersTheHookListeners(): void
    {
        $definition = new Definition('Test\HookListener\AfterListener');

        $definition->addTag(
            'contao.hook',
            [
                'hook' => 'initializeSystem',
                'method' => 'onInitializeSystem',
                'priority' => 0,
            ]
        );

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.hook_listener.after', $definition);

        $pass = new RegisterHooksPass();
        $pass->process($container);

        $argument = $this->assertHookListenersAreRegistered($container);

        $this->assertArrayHasKey('initializeSystem', $argument);
        $this->assertInternalType('array', $argument['initializeSystem']);
        $this->assertArrayHasKey(0, $argument['initializeSystem']);

        $this->assertSame(
            [
                ['test.hook_listener.after', 'onInitializeSystem'],
            ],
            $argument['initializeSystem'][0]
        );
    }

    public function testSetsTheDefaultPriorityIfNoPriorityGiven(): void
    {
        $definition = new Definition('Test\HookListener\AfterListener');

        $definition->addTag(
            'contao.hook',
            [
                'hook' => 'initializeSystem',
                'method' => 'onInitializeSystem',
            ]
        );

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.hook_listener.after', $definition);

        $pass = new RegisterHooksPass();
        $pass->process($container);

        $argument = $this->assertHookListenersAreRegistered($container);

        $this->assertArrayHasKey('initializeSystem', $argument);
        $this->assertInternalType('array', $argument['initializeSystem']);
        $this->assertArrayHasKey(0, $argument['initializeSystem']);

        $this->assertSame(
            [
                ['test.hook_listener.after', 'onInitializeSystem'],
            ],
            $argument['initializeSystem'][0]
        );
    }

    public function testHandlesMultipleTags(): void
    {
        $definition = new Definition('Test\HookListener');

        $definition->addTag(
            'contao.hook',
            [
                'hook' => 'initializeSystem',
                'method' => 'onInitializeSystemFirst',
            ]
        );

        $definition->addTag(
            'contao.hook',
            [
                'hook' => 'generatePage',
                'method' => 'onGeneratePage',
            ]
        );

        $definition->addTag(
            'contao.hook',
            [
                'hook' => 'initializeSystem',
                'method' => 'onInitializeSystemSecond',
            ]
        );

        $definition->addTag(
            'contao.hook',
            [
                'hook' => 'parseTemplate',
                'method' => 'onParseTemplate',
            ]
        );

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.hook_listener', $definition);

        $pass = new RegisterHooksPass();
        $pass->process($container);

        $argument = $this->assertHookListenersAreRegistered($container);

        $this->assertArrayHasKey('initializeSystem', $argument);
        $this->assertInternalType('array', $argument['initializeSystem']);
        $this->assertArrayHasKey(0, $argument['initializeSystem']);

        $this->assertArrayHasKey('generatePage', $argument);
        $this->assertInternalType('array', $argument['generatePage']);
        $this->assertArrayHasKey(0, $argument['generatePage']);

        $this->assertSame(
            [
                ['test.hook_listener', 'onInitializeSystemFirst'],
                ['test.hook_listener', 'onInitializeSystemSecond'],
            ],
            $argument['initializeSystem'][0]
        );

        $this->assertSame(
            [
                ['test.hook_listener', 'onGeneratePage'],
            ],
            $argument['generatePage'][0]
        );
    }

    public function testSortsTheHooksByPriority(): void
    {
        $definitionA = new Definition('Test\HookListenerA');

        $definitionA->addTag(
            'contao.hook',
            [
                'hook' => 'initializeSystem',
                'method' => 'onInitializeSystem',
                'priority' => 10,
            ]
        );

        $definitionB = new Definition('Test\HookListenerB');

        $definitionB->addTag(
            'contao.hook',
            [
                'hook' => 'initializeSystem',
                'method' => 'onInitializeSystemLow',
                'priority' => 10,
            ]
        );

        $definitionB->addTag(
            'contao.hook',
            [
                'hook' => 'initializeSystem',
                'method' => 'onInitializeSystemHigh',
                'priority' => 100,
            ]
        );

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.hook_listener.a', $definitionA);
        $container->setDefinition('test.hook_listener.b', $definitionB);

        $pass = new RegisterHooksPass();
        $pass->process($container);

        $argument = $this->assertHookListenersAreRegistered($container);

        $this->assertArrayHasKey('initializeSystem', $argument);
        $this->assertInternalType('array', $argument['initializeSystem']);

        $this->assertSame(
            [
                100 => [
                    ['test.hook_listener.b', 'onInitializeSystemHigh'],
                ],
                10 => [
                    ['test.hook_listener.a', 'onInitializeSystem'],
                    ['test.hook_listener.b', 'onInitializeSystemLow'],
                ],
            ],
            $argument['initializeSystem']
        );
    }

    public function testFailsIfTheHookAttributeIsMissing(): void
    {
        $definition = new Definition('Test\HookListener');

        $definition->addTag(
            'contao.hook',
            [
                'method' => 'onInitializeSystemAfter',
            ]
        );

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.hook_listener', $definition);

        $pass = new RegisterHooksPass();

        $this->expectException(InvalidConfigurationException::class);

        $pass->process($container);
    }

    public function testFailsIfTheMethodAttributeIsMissing(): void
    {
        $definition = new Definition('Test\HookListener');

        $definition->addTag(
            'contao.hook',
            [
                'hook' => 'initializeSystem',
            ]
        );

        $container = $this->getContainerBuilder();
        $container->setDefinition('test.hook_listener', $definition);

        $pass = new RegisterHooksPass();

        $this->expectException(InvalidConfigurationException::class);

        $pass->process($container);
    }

    /**
     * Asserts that the hook listeners are registered and returns them as array.
     *
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function assertHookListenersAreRegistered(ContainerBuilder $container): array
    {
        $this->assertTrue($container->hasDefinition('contao.framework'));

        $definition = $container->getDefinition('contao.framework');
        $argument = $definition->getArgument(6);

        $this->assertInternalType('array', $argument);
        $this->assertTrue(\count($argument) > 0);

        return $argument;
    }

    /**
     * Returns the container builder with a dummy contao.framework definition.
     *
     * @return ContainerBuilder
     */
    private function getContainerBuilder(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition('contao.framework', new Definition(ContaoFramework::class, []));

        return $container;
    }
}
