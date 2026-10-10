<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext;

/**
 * @implements \IteratorAggregate<array-key, HtmlTag|string>
 */
final class HtmlBodyBag implements \IteratorAggregate
{
    /**
     * @var array<array-key, HtmlTag|string>
     */
    private array $content = [];

    /**
     * Adds trusted markup to the end of the HTML body.
     */
    public function add(HtmlTag|string $content, string|null $identifier = null): self
    {
        if (null === $identifier) {
            $this->content[] = $content;
        } else {
            $this->content[$identifier] = $content;
        }

        return $this;
    }

    /**
     * @return array<array-key, HtmlTag|string>
     */
    public function all(): array
    {
        return $this->content;
    }

    /**
     * @return \ArrayIterator<array-key, HtmlTag|string>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->content);
    }
}
