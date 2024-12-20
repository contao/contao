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

    public string|null $tag = null;

    public string|null $value = null;

    /**
     * @var array<Node>
     */
    public array $children = [];

    public function __construct(
        public self|null $parent = null,
        public int $type = self::TYPE_ROOT,
    ) {
    }

    public function setTag(string $tag): self
    {
        $this->tag = $tag;

        return $this;
    }

    public function setValue(string|null $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getFirstChildValue(): string|null
    {
        if ([] === $this->children) {
            return null;
        }

        return $this->children[0]->value;
    }
}
