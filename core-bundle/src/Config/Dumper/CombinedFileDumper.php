<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Config\Dumper;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * Combines multiple files into one PHP file.
 */
class CombinedFileDumper implements DumperInterface
{
    private string $header = "<?php\n"; // add a line-break to prevent the "unexpected $end" error

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly LoaderInterface $loader,
        private readonly string $cacheDir,
    ) {
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function setHeader(string $header): void
    {
        if (!str_starts_with($header, '<?php')) {
            throw new \InvalidArgumentException('The file header must start with an opening PHP tag.');
        }

        $this->header = $header;
    }

    public function dump(array|string $files, string $cacheFile, array $options = []): void
    {
        $buffer = '';
        $sources = [];
        $line = 1;
        $type = $options['type'] ?? null;

        foreach ((array) $files as $file) {
            $code = (string) $this->loader->load($file, $type);

            if ('' === $code) {
                continue;
            }

            if (!str_ends_with($code, "\n")) {
                $code .= "\n";
            }

            $lineCount = substr_count($code, "\n");
            $sources[] = [(string) $file, $line, $line + $lineCount - 1];
            $line += $lineCount;
            $buffer .= $code;
        }

        $this->filesystem->dumpFile(Path::join($this->cacheDir, $cacheFile), $this->generateHeader($sources).$buffer);
    }

    /**
     * @param list<array{string, int, int}> $sources
     */
    private function generateHeader(array $sources): string
    {
        if ([] === $sources) {
            return $this->header;
        }

        $header = $this->header;

        if (!str_ends_with($header, "\n")) {
            $header .= "\n";
        }

        // Account for the header, the source entries and the three comment framing lines.
        $offset = substr_count($header, "\n") + \count($sources) + 3;
        $header .= "/*\n * Source files (line ranges in this cache file):\n";

        foreach ($sources as [$file, $start, $end]) {
            $file = str_replace(["\r", "\n", '*/'], ['\\r', '\\n', '* /'], $file);
            $header .= \sprintf(" * %d-%d: %s\n", $start + $offset, $end + $offset, $file);
        }

        return $header." */\n";
    }
}
