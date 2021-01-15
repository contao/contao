<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Extension;

use Contao\CoreBundle\Twig\Runtime\InsertTagRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TextExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'insert_tag',
                [InsertTagRuntime::class, 'replace'],
                ['is_safe' => ['html']]
            ),
        ];
    }
}
