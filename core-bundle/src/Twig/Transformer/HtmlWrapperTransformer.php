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

class HtmlWrapperTransformer implements PostRenderTransformerInterface
{
    /**
     * @var array<string, string>
     */
    private array $attributes;

    /**
     * @param array<string, string> $attributes
     */
    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function supports(string $name): bool
    {
        return 1 === preg_match('/\.html\.twig|\.html5$/', $name);
    }

    public function transform(string $rendered): string
    {
        // todo: merge attributes into outer wrapper

        return $rendered;
    }
}
