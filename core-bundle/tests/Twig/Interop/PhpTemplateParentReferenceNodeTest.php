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
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Environment;

class PhpTemplateParentReferenceNodeTest extends TestCase
{
    public function testCompilesParentReferenceCode(): void
    {
        $compiler = new Compiler($this->createMock(Environment::class));

        (new PhpTemplateParentReferenceNode())->compile($compiler);

        $expectedSource = <<<'SOURCE'
            echo sprintf('[[TL_PARENT_%s]]', \Contao\CoreBundle\Framework\ContaoFramework::getNonce());

            SOURCE;

        // Forward compatibility with twig/twig >=3.9.0
        if (class_exists(YieldReady::class)) {
            $expectedSource = str_replace('echo', 'yield', $expectedSource);
        }

        $this->assertSame($expectedSource, $compiler->getSource());
    }
}
