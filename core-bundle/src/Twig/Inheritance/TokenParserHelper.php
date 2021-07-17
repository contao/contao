<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Inheritance;

use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;

/**
 * @experimental
 */
final class TokenParserHelper
{
    public static function traverseConstantExpressions(Node $node, \Closure $onEnter): void
    {
        if ($node instanceof ConstantExpression) {
            $onEnter($node);

            return;
        }

        foreach ($node as $child) {
            self::traverseConstantExpressions($child, $onEnter);
        }
    }
}
