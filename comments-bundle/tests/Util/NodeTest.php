<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CommentsBundle\Util\Node;
use PHPUnit\Framework\TestCase;

class NodeTest extends TestCase
{
    public function testSetsAndGetsValues(): void
    {
        $default = new Node();

        $this->assertSame(Node::TYPE_ROOT, $default->type);
        $this->assertNull($default->parent);
        $this->assertNull($default->tag);
        $this->assertNull($default->value);
        $this->assertEmpty($default->children);

        $node = (new Node($default, Node::TYPE_CODE))->setTag('tag')->setValue('value');

        $this->assertSame($default, $node->parent);
        $this->assertSame(Node::TYPE_CODE, $node->type);
        $this->assertSame('tag', $node->tag);
        $this->assertSame('value', $node->value);
    }

    public function testGetsFirstChildValue(): void
    {
        $node = new Node();

        $this->assertNull($node->getFirstChildValue());

        $node->children[] = (new Node())->setValue('v1');
        $node->children[] = (new Node())->setValue('v2');

        $this->assertSame('v1', $node->getFirstChildValue());
    }
}
