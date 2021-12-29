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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class DcaTreeBuilder extends TreeBuilder
{
    public function __construct(string $name)
    {
        parent::__construct($name, 'array', new DcaNodeBuilder());
    }
}
