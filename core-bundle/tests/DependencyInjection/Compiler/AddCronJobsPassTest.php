<?php

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\Cron\Cron;
use Contao\CoreBundle\DependencyInjection\Compiler\AddCronJobsPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
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
            ->with(Cron::class)
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

        $definition = $container->getDefinition(Cron::class);

        $this->assertEmpty($definition->getMethodCalls());
    }

    public function testRegistersTheCrons(): void
    {
        $definition = new Definition('Test\Cron');
        $definition->addTag('contao.cron', ['interval' => 'minutely']);

        $container = $this->getContainerBuilder();
        $container->setDefinition('Test\Cron', $definition);

        $pass = new AddCronJobsPass();
        $pass->process($container);

        $crons = $this->getCronsFromDefinition($container);

        $this->assertCount(1, $crons);
    }

    public function testFailsIfTheIntervalAttributeIsMissing(): void
    {
        $definition = new Definition('Test\Cron');
        $definition->addTag('contao.cron');

        $container = $this->getContainerBuilder();
        $container->setDefinition('Test\Cron', $definition);

        $pass = new AddCronJobsPass();

        $this->expectException(InvalidConfigurationException::class);

        $pass->process($container);
    }

    public function testGeneratesMethodNameIfNoneGiven(): void
    {
        $definition = new Definition('Test\Cron');
        $definition->addTag('contao.cron', ['interval' => 'minutely']);

        $container = $this->getContainerBuilder();
        $container->setDefinition('Test\Cron', $definition);

        $pass = new AddCronJobsPass();
        $pass->process($container);

        $crons = $this->getCronsFromDefinition($container);

        $this->assertSame('onMinutely', $crons[0][1]);
    }

    public function testUsesMethodNameIfMethodNameIsGiven(): void
    {
        $definition = new Definition('Test\Cron');
        $definition->addTag('contao.cron', [
            'interval' => 'minutely', 
            'method' => 'customMethod',
        ]);

        $container = $this->getContainerBuilder();
        $container->setDefinition('Test\Cron', $definition);

        $pass = new AddCronJobsPass();
        $pass->process($container);

        $crons = $this->getCronsFromDefinition($container);

        $this->assertSame('customMethod', $crons[0][1]);
    }

    public function testSetsTheDefaultPriorityIfNoPriorityGiven(): void
    {
        $definition = new Definition('Test\Cron');
        $definition->addTag('contao.cron', ['interval' => 'minutely']);

        $container = $this->getContainerBuilder();
        $container->setDefinition('Test\Cron', $definition);

        $pass = new AddCronJobsPass();
        $pass->process($container);

        $crons = $this->getCronsFromDefinition($container);

        $this->assertSame(0, $crons[0][3]);      
    }

    public function testSetsTheDefaultCliOnlyIfNoCliGiven(): void
    {
        $definition = new Definition('Test\Cron');
        $definition->addTag('contao.cron', ['interval' => 'minutely']);

        $container = $this->getContainerBuilder();
        $container->setDefinition('Test\Cron', $definition);

        $pass = new AddCronJobsPass();
        $pass->process($container);

        $crons = $this->getCronsFromDefinition($container);

        $this->assertSame(false, $crons[0][4]);
    }

    public function testSetsCliOnlyIfCliOnlyIsGiven():void
    {
        $definition = new Definition('Test\Cron');
        $definition->addTag('contao.cron', ['interval' => 'minutely', 'cli_only' => true]);

        $container = $this->getContainerBuilder();
        $container->setDefinition('Test\Cron', $definition);

        $pass = new AddCronJobsPass();
        $pass->process($container);

        $crons = $this->getCronsFromDefinition($container);

        $this->assertSame(true, $crons[0][4]);
    }

    public function testHandlesMultipleTags(): void
    {
        $definition = new Definition('Test\Cron');
        $definition->addTag('contao.cron', ['interval' => 'minutely']);
        $definition->addTag('contao.cron', ['interval' => 'hourly']);
        $definition->addTag('contao.cron', ['interval' => 'daily']);
        $definition->addTag('contao.cron', ['interval' => 'weekly']);
        $definition->addTag('contao.cron', ['interval' => 'monthly']);

        $container = $this->getContainerBuilder();
        $container->setDefinition('Test\Cron', $definition);

        $pass = new AddCronJobsPass();
        $pass->process($container);

        $crons = $this->getCronsFromDefinition($container);

        $this->assertCount(5, $crons);
        $this->assertSame('minutely', $crons[0][2]);
        $this->assertSame('hourly', $crons[1][2]);
        $this->assertSame('daily', $crons[2][2]);
        $this->assertSame('weekly', $crons[3][2]);
        $this->assertSame('monthly', $crons[4][2]);
    }

    /**
     * Returns the container builder with a dummy Cron definition.
     */
    private function getContainerBuilder(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition(Cron::class, new Definition(Cron::class, []));

        return $container;
    }

    /**
     * @return array<Reference,string,string,int,bool>
     */
    private function getCronsFromDefinition(ContainerBuilder $container): array
    {
        $this->assertTrue($container->hasDefinition(Cron::class));

        $definition = $container->getDefinition(Cron::class);
        $methodCalls = $definition->getMethodCalls();

        $this->assertIsArray($methodCalls);

        $crons = [];

        foreach ($methodCalls as $methodCall) {
            $this->assertSame('addCronJob', $methodCall[0]);
            $this->assertIsArray($methodCall[1]);
            $this->assertInstanceOf(Reference::class, $methodCall[1][0]);
            $this->assertIsString($methodCall[1][1]);
            $this->assertIsString($methodCall[1][2]);
            $this->assertIsInt($methodCall[1][3]);
            $this->assertIsBool($methodCall[1][4]);
            $crons[] = $methodCall[1];
        }

        return $crons;
    }
}
