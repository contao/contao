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
    public function testReplacesInsertTag(): void
    {
        $controller = $this->mockAdapter(['replaceInsertTags']);
        $controller
            ->expects($this->once())
            ->method('replaceInsertTags')
            ->with('{{tag}}', false)
            ->willReturn('replaced-tag')
        ;

        $framework = $this->mockContaoFramework([Controller::class => $controller]);
        $runtime = new InsertTagRuntime($framework);

        $this->assertSame('replaced-tag', $runtime->replace('tag'));
    }
}
