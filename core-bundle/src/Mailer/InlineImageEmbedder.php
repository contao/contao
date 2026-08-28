<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Mailer;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\File;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Mime\Email;

final class InlineImageEmbedder
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly string $projectDir,
    ) {
    }

    public function embedImages(Email $email, string $baseUrl = '', string|null $imageDir = null): void
    {
        $html = $email->getHtmlBody();

        if (!\is_string($html) || '' === $html) {
            return;
        }

        // Thanks to @ofriedrich and @aschempp (see #4562)
        if (!preg_match_all('/<[a-z][a-z0-9]*\b[^>]*((src=|background=|url\()["\']??)(.+\.(jpe?g|png|gif|bmp|tiff?|swf|svg))(["\' ]??(\)??))[^>]*>/Ui', $html, $matches, PREG_SET_ORDER)) {
            return;
        }

        $imageDir = Path::canonicalize($imageDir ?? $this->projectDir);
        $resolved = [];
        $replacements = [];

        foreach ($matches as [, $prefix, , $url, , $suffix]) {
            $src = str_replace($baseUrl, '', $url);
            $src = rawurldecode($src); // see #3713

            // Skip absolute URLs as well as "data:" and "cid:" references
            if (preg_match('@^(?:[a-z][a-z0-9+.-]*:|//)@i', $src)) {
                continue;
            }

            if (!isset($resolved[$src])) {
                $path = Path::canonicalize(Path::join($imageDir, $src));

                // Prevent directory traversal
                $resolved[$src] = Path::isBasePath($imageDir, $path) && $this->fileExists($path);

                if ($resolved[$src]) {
                    // See https://symfony.com/doc/current/mailer.html#embedding-images
                    $email->embedFromPath($path, $src);
                }
            }

            if (!$resolved[$src]) {
                continue;
            }

            $replacements[$prefix.$url.$suffix] = $prefix.'cid:'.$src.$suffix;
        }

        if ($replacements) {
            $email->html(strtr($html, $replacements), $email->getHtmlCharset() ?? 'utf-8');
        }
    }

    private function fileExists(string $path): bool
    {
        if (is_file($path)) {
            return true;
        }

        try {
            $file = $this->framework->createInstance(File::class, [Path::makeRelative($path, $this->projectDir)]);

            return $file->exists() || $file->createIfDeferred();
        } catch (\Throwable) {
            return false;
        }
    }
}
