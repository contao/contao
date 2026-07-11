<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\HtmlSanitizer;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Input;
use Contao\InputEncodingMode;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

class ContaoHtmlSanitizer implements HtmlSanitizerInterface
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function sanitize(string $input): string
    {
        $this->framework->initialize();

        return Input::encodeInput($input, InputEncodingMode::sanitizeHtml, false);
    }

    public function sanitizeFor(string $element, string $input): string
    {
        return $this->sanitize($input);
    }
}
