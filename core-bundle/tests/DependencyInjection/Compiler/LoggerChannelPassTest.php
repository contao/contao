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

use Contao\CoreBundle\DependencyInjection\Compiler\LoggerChannelPass;
use Contao\CoreBundle\Monolog\SystemLogger;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class LoggerChannelPassTest extends TestCase
{
    public function testDecoratesServicesWithLoggersUsingContaoChannel(): void
    {
        $definition = new ChildDefinition('monolog.logger_prototype');
        $definition->setArgument(0, 'contao.foo');

        $container = new ContainerBuilder();
        $container->setDefinition('monolog.logger', new Definition());
        $container->setDefinition('contao.dummy_service', $definition);

        $pass = new LoggerChannelPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition('contao._logger.contao.dummy_service'));

        $decorator = $container->getDefinition('contao._logger.contao.dummy_service');

        $this->assertSame(SystemLogger::class, $decorator->getClass());
        $this->assertSame('contao.dummy_service', $decorator->getDecoratedService()[0]);
        $this->assertSame('foo', $decorator->getArgument(1));
    }

    public function testDoesNotChangeServicesWithLoggersNotUsingContaoChannel(): void
    {
        $definition = new ChildDefinition('monolog.logger_prototype');
        $definition->setArgument(0, 'foo.bar');

        $container = new ContainerBuilder();
        $container->setDefinition('monolog.logger', new Definition());
        $container->setDefinition('contao.dummy_service', $definition);

        $pass = new LoggerChannelPass();
        $pass->process($container);

        $this->assertFalse($container->hasDefinition('contao._logger.contao.dummy_service'));
    }

    public function testDoesNothingIfNoMonologLogger(): void
    {
        $definition = new ChildDefinition('monolog.logger_prototype');
        $definition->setArgument(0, 'contao.foo');

        $container = new ContainerBuilder();
        $container->setDefinition('contao.dummy_service', $definition);

        $pass = new LoggerChannelPass();
        $pass->process($container);

        $this->assertFalse($container->hasDefinition('contao._logger.contao.dummy_service'));
    }

    /**
     * @dataProvider legacyActionNamesProvider
     */
    public function testTransformsLegacyActionNamesForLoggersUsingContaoChannel(string $action, string $transformed): void
    {
        $definition = new ChildDefinition('monolog.logger_prototype');
        $definition->setArgument(0, 'contao.'.$action);

        $container = new ContainerBuilder();
        $container->setDefinition('monolog.logger', new Definition());
        $container->setDefinition('contao.dummy_service', $definition);

        $pass = new LoggerChannelPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition('contao._logger.contao.dummy_service'));

        $decorator = $container->getDefinition('contao._logger.contao.dummy_service');
        $logger = $container->getDefinition('contao.dummy_service');

        $this->assertSame($transformed, $decorator->getArgument(1), 'The action name should be transformed.');
        $this->assertSame('contao.'.$action, $logger->getArgument(0), 'The channel name should not be transformed.');
    }

    public function legacyActionNamesProvider(): array
    {
        return [
            ['error', 'ERROR'],
            ['access', 'ACCESS'],
            ['general', 'GENERAL'],
            ['files', 'FILES'],
            ['cron', 'CRON'],
            ['forms', 'FORMS'],
            ['email', 'EMAIL'],
            ['configuration', 'CONFIGURATION'],
        ];
    }
}
