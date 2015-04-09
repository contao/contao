<?php
/**
 * Created by PhpStorm.
 * User: aschempp
 * Date: 27.03.15
 * Time: 22:46
 */

namespace Contao\CoreBundle\Test\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\OrderProfilerPass;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Tests the OrderProfilerPass class.
 *
 * @author Andreas Schempp <http://github.com/aschempp>
 */
class OrderProfilerPassTest extends TestCase
{
    /**
     * @var OrderProfilerPass
     */
    private $pass;

    /**
     * @var ContainerBuilder
     */
    private $container;

    protected function setUp()
    {
        $this->pass      = new OrderProfilerPass();
        $this->container = new ContainerBuilder();
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf(
            'Contao\CoreBundle\DependencyInjection\Compiler\OrderProfilerPass',
            $this->pass
        );
    }

    public function testOrder()
    {
        $templates  = ['data_collector.test' => true, 'contao.data_collector' => true];
        $definition = new Definition(null, ['', '', '', $templates, '']);

        $this->container->setDefinition('web_profiler.controller.profiler', $definition);
        $this->container->setParameter('data_collector.templates', $templates);

        $this->pass->process($this->container);

        $this->assertTrue($this->container->hasDefinition('web_profiler.controller.profiler'));
        $this->assertTrue($this->container->hasParameter('data_collector.templates'));

        $definition = $this->container->getDefinition('web_profiler.controller.profiler');
        $templates  = $this->container->getParameter('data_collector.templates');

        $this->assertEquals(
            ['contao.data_collector' => true, 'data_collector.test' => true],
            $templates
        );
        $this->assertEquals($templates, $definition->getArgument(3));
    }

    public function testWithoutDefinition()
    {
        $templates = ['contao.data_collector' => true];
        $this->container->setParameter('data_collector.templates', $templates);

        $this->pass->process($this->container);

        $this->assertFalse($this->container->hasDefinition('web_profiler.controller.profiler'));
        $this->assertTrue($this->container->hasParameter('data_collector.templates'));
        $this->assertEquals($templates, $this->container->getParameter('data_collector.templates'));
    }

    public function testWithoutParameter()
    {
        $definition = new Definition();
        $this->container->setDefinition('web_profiler.controller.profiler', $definition);

        $this->pass->process($this->container);

        $this->assertFalse($this->container->hasParameter('data_collector.templates'));
        $this->assertTrue($this->container->hasDefinition('web_profiler.controller.profiler'));
        $this->assertEquals($definition, $this->container->getDefinition('web_profiler.controller.profiler'));
    }

    public function testWithoutContaoDataCollector()
    {
        $definition = new Definition();
        $templates  = ['data_collector.test' => true];
        $this->container->setParameter('data_collector.templates', $templates);
        $this->container->setDefinition('web_profiler.controller.profiler', $definition);

        $this->pass->process($this->container);

        $this->assertTrue($this->container->hasDefinition('web_profiler.controller.profiler'));
        $this->assertTrue($this->container->hasParameter('data_collector.templates'));
        $this->assertEquals($definition, $this->container->getDefinition('web_profiler.controller.profiler'));
        $this->assertEquals($templates, $this->container->getParameter('data_collector.templates'));
    }
}
