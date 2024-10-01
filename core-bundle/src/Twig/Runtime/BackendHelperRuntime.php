<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Runtime;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\Image;
use Twig\Extension\RuntimeExtensionInterface;

class BackendHelperRuntime implements RuntimeExtensionInterface
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function icon(string $src, string $alt = '', HtmlAttributes|null $attributes = null): string
    {
        $image = $this->framework->getAdapter(Image::class);

        return $image->getHtml($src, $alt, $attributes ? $attributes->toString(false) : '');
    }
}
