<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Runtime;

use Contao\Controller;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Runtime\InsertTagRuntime;

class InsertTagRuntimeTest extends TestCase
{
    /**
     * @dataProvider provideCacheConfigurations
     */
    public function testReplacesInsertTag(?bool $cacheArgument, bool $expectedCall): void
    {
        $controller = $this->mockAdapter(['replaceInsertTags']);
        $controller
            ->expects($this->once())
            ->method('replaceInsertTags')
            ->with('{{tag}}', $expectedCall)
            ->willReturn('replaced-tag')
        ;

        $framework = $this->mockContaoFramework([Controller::class => $controller]);
        $runtime = new InsertTagRuntime($framework);

        if (null !== $cacheArgument) {
            $result = $runtime->replace('tag', $cacheArgument);
        } else {
            $result = $runtime->replace('tag');
        }

        $this->assertSame('replaced-tag', $result);
    }

    public function provideCacheConfigurations(): \Generator
    {
        yield 'do cache if set to true' => [true, true];
        yield 'do not cache if set to false' => [false, false];
        yield 'do cache if nothing was set' => [null, true];
    }
}
