<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Defer;

/**
 * @internal
 */
class DeferredStringable implements \Stringable
{
    /**
     * @param \Closure():string $content
     */
    public function __construct(private readonly \Closure $content)
    {
    }

    public function __toString(): string
    {
        return (string) ($this->content)();
    }
}
