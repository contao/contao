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

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\NodeInterface;

/**
 * @internal
 */
final class DcaTreeBuilder extends TreeBuilder
{
    public const FLAG_ALLOW_FAILABLE = 'dca.allow_failable';

    private bool $allowFailableNodes = false;

    public function __construct(string $name, NodeBuilder $builder)
    {
        parent::__construct($name, 'dca', $builder);
    }

    public function buildTree(): NodeInterface
    {
        $this->root->attribute(self::FLAG_ALLOW_FAILABLE, $this->allowFailableNodes);

        return parent::buildTree();
    }

    /**
     * @phpstan-return static
     */
    public function allowFailingNodes(bool $allow): self
    {
        $this->allowFailableNodes = $allow;

        return $this;
    }
}
