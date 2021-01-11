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

use Contao\CoreBundle\Twig\Compat\SafeHTMLValueHolderInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\EscaperExtension;

class ContaoCompatExtension extends AbstractExtension
{
    /**
     * @internal
     */
    public function __construct(Environment $twig)
    {
        /** @var EscaperExtension $extension */
        $extension = $twig->getExtension(EscaperExtension::class);
        $extension->addSafeClass(SafeHTMLValueHolderInterface::class, ['html']);
    }
}
