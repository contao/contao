<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Tools\Rector;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeVisitorAbstract;

final class ReturnTypeVisitor extends NodeVisitorAbstract
{
    public const ATTRIBUTE_NAME = 'returnType';

    /**
     * Adds the "returnType" attribute to nodes within a return statement.
     */
    public function enterNode(Node $node): Node|null
    {
        if (!$node instanceof Return_) {
            return null;
        }

        if ($node->expr instanceof Expr) {
            $node->expr->setAttribute(self::ATTRIBUTE_NAME, true);
        }

        return null;
    }
}
