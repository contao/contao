<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Definition\Builder;

use Symfony\Component\Config\Definition\BaseNode;

/**
 * @internal
 */
trait RootAwareTrait
{
    private function getRootNode(): BaseNode
    {
        $root = $this->parent;

        while ($root instanceof BaseNode && ($parent = $root->getParent()) && $parent instanceof BaseNode) {
            $root = $parent;
        }

        return $root;
    }
}
