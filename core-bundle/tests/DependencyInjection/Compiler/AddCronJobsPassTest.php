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

use Contao\CoreBundle\Cron\Cron;
use Contao\CoreBundle\Cron\CronJob;
use Contao\CoreBundle\DependencyInjection\Compiler\AddCronJobsPass;
use Contao\CoreBundle\Fixtures\Cron\TestCronJob;
use Contao\CoreBundle\Fixtures\Cron\TestInvokableCronJob;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidDefinitionException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AddCronJobsPassTest extends TestCase
{
    public function testDoesNothingIfThereIsNoCron(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('hasDefinition')
            ->with('contao.cron')
            ->willReturn(false)
        ;

        $container
            ->expects($this->never())
            ->method('findTaggedServiceIds')
        ;

        $pass = new AddCronJobsPass();
        $pass->process($container);
    }

    public function testDoesNothingIfThereAreNoCrons(): void
    {
        $container = $this->getContainerBuilder();

        $pass = new AddCronJobsPass();
        $pass->process($container);

        $definition = $container->getDefinition('contao.cron');

        $this->assertEmpty($definition->getMethodCalls());
    }

    public function testRegistersTheCrons(): void
    {
        $definition = new Definition(TestCronJob::class);
        $definition->addTag('contao.cronjob', ['interval' => 'minutely']);

        $container = $this->getContainerBuilder();
        $container->setDefinition(TestCronJob::class, $definition);

        $pass = new AddCronJobsPass();
        $pass->process($container);

        $crons = $this->getCronsFromDefinition($container);

        $this->assertCount(1, $crons);
    }

    public function testFailsIfTheIntervalAttributeIsMissing(): void
    {
        $definition = new Definition(TestCronJob::class);
        $definition->addTag('contao.cronjob');

        $container = $this->getContainerBuilder();
        $container->setDefinition(TestCronJob::class, $definition);

        $pass = new AddCronJobsPass();

        $this->expectException(InvalidDefinitionException::class);

        $pass->process($container);
    }

    public function testFailsIfTheIntervalAttributeIsInvalid(): void
    {
        $definition = new Definition(TestCronJob::class);
        $definition->addTag('contao.cronjob', ['interval' => '* b * * *']);

        $container = $this->getContainerBuilder();
        $container->setDefinition(TestCronJob::class, $definition);

        $pass = new AddCronJobsPass();

        $this->expectException(InvalidDefinitionException::class);

        $pass->process($container);
    }

    public function testGeneratesMethodNameIfNoneGiven(): void
    {
        $definition = new Definition(TestCronJob::class);
        $definition->addTag('contao.cronjob', ['interval' => 'minutely']);

        $container = $this->getContainerBuilder();
        $container->setDefinition(TestCronJob::class, $definition);

        $pass = new AddCronJobsPass();
        $pass->process($container);

        $crons = $this->getCronsFromDefinition($container);

        /** @var Definition $definition */
        $definition = $crons[0][0];

        $this->assertSame('onMinutely', $definition->getArgument(2));
    }

    public function testUsesNoMethodIfNoneGiven(): void
    {
        $definition = new Definition(TestInvokableCronJob::class);
        $definition->addTag('contao.cronjob', ['interval' => 'minutely']);

        $container = $this->getContainerBuilder();
        $container->setDefinition(TestInvokableCronJob::class, $definition);

        $pass = new AddCronJobsPass();
        $pass->process($container);

        $crons = $this->getCronsFromDefinition($container);

        /** @var Definition $definition */
        $definition = $crons[0][0];

        $this->assertNull($definition->getArgument(2));
    }

    public function testUsesMethodNameIfMethodNameIsGiven(): void
    {
        $definition = new Definition(TestCronJob::class);

        $definition->addTag(
            'contao.cronjob',
            [
                'interval' => 'minutely',
                'method' => 'customMethod',
            ]
        );

        $container = $this->getContainerBuilder();
        $container->setDefinition(TestCronJob::class, $definition);

        $pass = new AddCronJobsPass();
        $pass->process($container);

        $crons = $this->getCronsFromDefinition($container);

        /** @var Definition $definition */
        $definition = $crons[0][0];

        $this->assertSame('customMethod', $definition->getArgument(2));
    }

    public function testHandlesMultipleTags(): void
    {
        $definition = new Definition(TestCronJob::class);
        $definition->addTag('contao.cronjob', ['interval' => 'minutely']);
        $definition->addTag('contao.cronjob', ['interval' => 'hourly']);
        $definition->addTag('contao.cronjob', ['interval' => 'daily']);
        $definition->addTag('contao.cronjob', ['interval' => 'weekly']);
        $definition->addTag('contao.cronjob', ['interval' => 'monthly']);

        $container = $this->getContainerBuilder();
        $container->setDefinition(TestCronJob::class, $definition);

        $pass = new AddCronJobsPass();
        $pass->process($container);

        $crons = $this->getCronsFromDefinition($container);

        /** @var array<Definition> $definitions */
        $definitions = array_column($crons, 0);

        $this->assertCount(5, $crons);
        $this->assertSame('* * * * *', $definitions[0]->getArgument(1));
        $this->assertSame('@hourly', $definitions[1]->getArgument(1));
        $this->assertSame('@daily', $definitions[2]->getArgument(1));
        $this->assertSame('@weekly', $definitions[3]->getArgument(1));
        $this->assertSame('@monthly', $definitions[4]->getArgument(1));
    }

    public function testAddingTheSameClassWithDifferentServiceIdsHasDifferentNames(): void
    {
        $definition = new Definition(TestCronJob::class);
        $definition->addTag('contao.cronjob', ['interval' => 'minutely']);

        $container = $this->getContainerBuilder();
        $container->setDefinition('app.cronjobs.configuration_1', $definition);
        $container->setDefinition('app.cronjobs.configuration_2', $definition);
        $container->setDefinition('app.cronjobs.configuration_3', $definition);

        $pass = new AddCronJobsPass();
        $pass->process($container);

        $crons = $this->getCronsFromDefinition($container);

        /** @var array<Definition> $definitions */
        $definitions = array_column($crons, 0);

        $this->assertCount(3, $crons);
        $this->assertSame('* * * * *', $definitions[0]->getArgument(1));
        $this->assertSame('* * * * *', $definitions[1]->getArgument(1));
        $this->assertSame('* * * * *', $definitions[2]->getArgument(1));
        $this->assertSame('app.cronjobs.configuration_1', $definitions[0]->getArgument(3));
        $this->assertSame('app.cronjobs.configuration_2', $definitions[1]->getArgument(3));
        $this->assertSame('app.cronjobs.configuration_3', $definitions[2]->getArgument(3));
    }

    /**
     * Returns the container builder with a dummy Cron definition.
     */
    private function getContainerBuilder(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition('contao.cron', new Definition(Cron::class, []));

        return $container;
    }

    /**
     * @return array<int,array<int,Reference|string>>
     */
    private function getCronsFromDefinition(ContainerBuilder $container): array
    {
        $this->assertTrue($container->hasDefinition('contao.cron'));

        $definition = $container->getDefinition('contao.cron');
        $methodCalls = $definition->getMethodCalls();

        $this->assertIsArray($methodCalls);

        $crons = [];

        foreach ($methodCalls as $methodCall) {
            $this->assertSame('addCronJob', $methodCall[0]);
            $this->assertIsArray($methodCall[1]);
            $this->assertInstanceOf(Definition::class, $methodCall[1][0]);

            /** @var Definition $definition */
            $definition = $methodCall[1][0];

            $this->assertSame(CronJob::class, $definition->getClass());
            $this->assertInstanceOf(Reference::class, $definition->getArgument(0));
            $this->assertIsString($definition->getArgument(1));

            if (null !== $definition->getArgument(2)) {
                $this->assertIsString($definition->getArgument(2));
            }

            $crons[] = $methodCall[1];
        }

        return $crons;
    }
}
