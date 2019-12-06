<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\DependencyInjection\Compiler;

use Contao\NewsBundle\DependencyInjection\Compiler\StartStopValidatorPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class StartStopValidatorPassTest extends TestCase
{
    public function testAddsTheStartStopFieldTags(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('contao.data_container.start_stop_validator', new Definition());

        $pass = new StartStopValidatorPass();
        $pass->process($container);

        $tags = $container->findDefinition('contao.data_container.start_stop_validator')->getTags();

        $this->assertCount(1, $tags);
        $this->assertArrayHasKey('contao.callback', $tags);

        $this->assertSame('tl_news', $tags['contao.callback'][0]['table']);
        $this->assertSame('fields.start.save', $tags['contao.callback'][0]['target']);
        $this->assertSame('validateStartDate', $tags['contao.callback'][0]['method']);

        $this->assertSame('tl_news', $tags['contao.callback'][1]['table']);
        $this->assertSame('fields.stop.save', $tags['contao.callback'][1]['target']);
        $this->assertSame('validateStopDate', $tags['contao.callback'][1]['method']);
    }
}
