<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Runtime;

use Contao\Input;
use Contao\InputEncodingMode;
use Symfony\Bridge\Twig\Extension\HtmlSanitizerExtension;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

// Backwards compatibility: To be removed in Contao 6
final class SanitizerRuntime implements RuntimeExtensionInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public function sanitizeHtml(string $html, string|null $sanitizer = null): string
    {
        $html = $this->twig->getExtension(HtmlSanitizerExtension::class)->sanitize($html, $sanitizer);

        if ('contao' === $sanitizer) {
            return $html;
        }

        // Encode Contao-specific special characters like insert tags
        return Input::encodeInput($html, InputEncodingMode::encodeNone);
    }
}
