<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Transformer;

class PrependTransformer implements PostRenderTransformerInterface
{
    private string $prefix;

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    public function supports(string $name): bool
    {
        return true;
    }

    public function transform(string $rendered): string
    {
        return $this->prefix.$rendered;
    }
}
