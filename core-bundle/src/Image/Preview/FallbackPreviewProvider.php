<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Image\Preview;

use Contao\ImagineSvg\Imagine;
use Imagine\Image\Box;

class FallbackPreviewProvider implements PreviewProviderInterface
{
    public function getFileHeaderSize(): int
    {
        return 0;
    }

    public function supports(string $path, string $fileHeader = '', array $options = []): bool
    {
        return true;
    }

    public function generatePreviews(string $sourcePath, int $size, \Closure $targetPathCallback, int $lastPage = PHP_INT_MAX, int $firstPage = 1, array $options = []): iterable
    {
        if (1 !== $firstPage) {
            throw new UnableToGeneratePreviewException(\sprintf('There is no page %s in "%s"', $firstPage, $sourcePath));
        }

        $svgCode = '<?xml version="1.0"?>'."\n";
        $svgCode .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-6 -6 30 30">';
        $svgCode .= '<rect x="-6" y="-6" width="30" height="30" fill="#f3f3f5"/>';
        $svgCode .= '<path d="M14.76 5.58v10.26H3.24V2.16h8.21z" fill="#fff"/>';
        $svgCode .= '<path d="M14.76 5.58v10.26H3.24V2.16h8.21z" fill="#f9fafa"/>';
        $svgCode .= '<path d="M11.23 2.34l.29 3.6 3.24-.36z" fill="#fff"/>';
        $svgCode .= '<path d="M11.61 1.79H2.7V16.2h12.6V5.51zm2.69 3.72h-2.67V2.82zm.28 10H3.42v-13h7.38v3.86h3.78z" fill="#ccc"/>';
        $svgCode .= '<path d="M14.25 11.88a1.83 1.83 0 0 1-1.83 1.83H2.83A1.83 1.83 0 0 1 1 11.89v-2.3a1.83 1.83 0 0 1 1.83-1.83h9.59a1.83 1.83 0 0 1 1.83 1.83z" fill="#c9473d"/>';
        $svgCode .= '<text fill="#fff" text-anchor="middle" x="7.7" y="12.2" style="font: bold 4px -apple-system, BlinkMacSystemFont, avenir next, avenir, segoe ui, helvetica neue, helvetica, Ubuntu, roboto, noto, arial, sans-serif;">';
        $svgCode .= htmlspecialchars(strtoupper(pathinfo($sourcePath, PATHINFO_EXTENSION)), ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1);
        $svgCode .= '</text>';
        $svgCode .= '</svg>';

        $targetPath = $targetPathCallback(1).'.svg';

        (new Imagine())
            ->load($svgCode)
            ->resize(new Box($size, $size))
            ->save($targetPath, ['format' => 'svg'])
        ;

        return [$targetPath];
    }
}
