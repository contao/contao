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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\StringUtil;
use Twig\Extension\RuntimeExtensionInterface;

final class StringRuntime implements RuntimeExtensionInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function encodeEmail(string $html): string
    {
        $this->framework->initialize();

        return $this->framework->getAdapter(StringUtil::class)->encodeEmail($html);
    }
}
