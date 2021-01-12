<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Extension;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\TextExtension;
use Contao\CoreBundle\Twig\Runtime\InsertTagRuntime;
use Twig\Node\Node;

class TextExtensionTest extends TestCase
{
    public function testAddsTheContaoInsertTagFunction(): void
    {
        $functions = (new TextExtension())->getFunctions();

        $this->assertCount(1, $functions);

        [$contaoInsertTagFn] = $functions;

        $node = $this->createMock(Node::class);

        $this->assertSame('insert_tag', $contaoInsertTagFn->getName());
        $this->assertSame([InsertTagRuntime::class, 'replace'], $contaoInsertTagFn->getCallable());
        $this->assertSame(['html'], $contaoInsertTagFn->getSafe($node));
    }
}
