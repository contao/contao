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

interface PostRenderTransformerInterface
{
    public function supports(string $name): bool;

    public function transform(string $rendered): string;
}
