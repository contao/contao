<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CommentsBundle\Util;

/**
 * @internal
 */
final class Node
{
    public const TYPE_ROOT = 0;
    public const TYPE_TEXT = 1;
    public const TYPE_BLOCK = 2;
    public const TYPE_CODE = 3;

    /**
     * @var Node|null
     */
    public $parent;

    /**
     * @var int
     */
    public $type;

    /**
     * @var string|null
     */
    public $tag;

    /**
     * @var string|null
     */
    public $value;

    /**
     * @var array<Node>
     */
    public $children = [];

    public function __construct(self $parent = null, int $type = self::TYPE_ROOT)
    {
        $this->parent = $parent;
        $this->type = $type;
    }

    public function setTag(string $tag): self
    {
        $this->tag = $tag;

        return $this;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getFirstChildValue(): ?string
    {
        if (0 === \count($this->children)) {
            return null;
        }

        return $this->children[0]->value;
    }
}
