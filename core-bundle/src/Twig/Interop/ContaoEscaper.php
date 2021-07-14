<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Interop;

use Twig\Environment;
use Twig\Error\RuntimeError;

/**
 * The ContaoEscaper mimics Twig's default html escape filter but prevents
 * double encoding. It must therefore ONLY be applied to templates with already
 * encoded context (input encoding)!
 *
 * This strategy will get dropped once we move to output encoding.
 *
 * @experimental
 */
final class ContaoEscaper
{
    /**
     * This implementation is a clone of Twig's html escape strategy but calls
     * htmlspecialchars with the double_encode parameter set to false.
     *
     * @see twig_escape_filter
     */
    public function __invoke(Environment $environment, $string, ?string $charset): string
    {
        $string = (string) $string;

        // Handle uppercase entities
        $string = str_replace(
            ['&AMP;', '&QUOT;', '&LT;', '&GT;'],
            ['&amp;', '&quot;', '&lt;', '&gt;'],
            $string
        );

        if (null !== $charset && 'UTF-8' !== strtoupper($charset)) {
            throw new RuntimeError(sprintf('The "contao_html" escape filter does not support the %s charset, use UTF-8 instead.', $charset));
        }

        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }
}
