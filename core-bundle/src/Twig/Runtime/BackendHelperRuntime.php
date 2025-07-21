<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Runtime;

use Contao\CoreBundle\Filesystem\FilesystemItem;
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

    /**
     * Returns a file icon based on the mapping in the TL_MIME superglobal.
     */
    public function file_icon(FilesystemItem $item, string $alt = '', HtmlAttributes|null $attributes = null): string
    {
        if (!$mimeType = $item->getMimeType()) {
            return $this->icon('plain.svg', $alt, $attributes);
        }

        $this->framework->initialize();

        foreach ($GLOBALS['TL_MIME'] ?? [] as $mimeTypes) {
            if ($mimeType === $mimeTypes[0]) {
                return $this->icon($mimeTypes[1], $alt, $attributes);
            }
        }

        return $this->icon('plain.svg', $alt, $attributes);
    }
}
