<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Interop;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Interop\PhpTemplateParentReferenceNode;
use Twig\Compiler;
use Twig\Environment;

class PhpTemplateParentReferenceNodeTest extends TestCase
{
    public function testCompilesParentReferenceCode(): void
    {
        $compiler = new Compiler($this->createMock(Environment::class));

        (new PhpTemplateParentReferenceNode())->compile($compiler);

        $expectedSource = <<<'SOURCE'
            yield sprintf('[[TL_PARENT_%s]]', \Contao\CoreBundle\Framework\ContaoFramework::getNonce());

            SOURCE;

        $this->assertSame($expectedSource, $compiler->getSource());
    }
}
