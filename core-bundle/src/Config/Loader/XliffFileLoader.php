<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Config\Loader;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Filesystem\Path;

/**
 * Reads XLIFF files and converts them into Contao language arrays.
 */
class XliffFileLoader extends Loader
{
    public function __construct(
        private readonly string $projectDir,
        private readonly bool $addToGlobals = false,
    ) {
        parent::__construct();
    }

    public function load(mixed $resource, string|null $type = null): string
    {
        return $this->convertXlfToPhp((string) $resource, $type ?: 'en');
    }

    public function supports(mixed $resource, string|null $type = null): bool
    {
        return 'xlf' === Path::getExtension((string) $resource, true);
    }

    private function convertXlfToPhp(string $name, string $language): string
    {
        $xml = $this->getDomDocumentFromFile($name);

        $return = "\n// ".Path::makeRelative($name, $this->projectDir)."\n";
        $fileNodes = $xml->getElementsByTagName('file');
        $language = strtolower($language);

        /** @var \DOMElement $fileNode */
        foreach ($fileNodes as $fileNode) {
            $tagName = 'target';

            // Use the source tag if the source language matches
            if (strtolower($fileNode->getAttribute('source-language')) === $language) {
                $tagName = 'source';
            }

            $return .= $this->getPhpFromFileNode($fileNode, $tagName);
        }

        return $return;
    }

    private function getPhpFromFileNode(\DOMElement $fileNode, string $tagName): string
    {
        $return = '';
        $units = $fileNode->getElementsByTagName('trans-unit');

        /** @var \DOMElement $unit */
        foreach ($units as $unit) {
            $node = $unit->getElementsByTagName($tagName);

            if (!$node->item(0)) {
                continue;
            }

            $chunks = $this->getChunksFromUnit($unit);
            $value = $this->fixClosingTags($node->item(0));

            $return .= $this->getStringRepresentation($chunks, $value);

            $this->addGlobal($chunks, $value);
        }

        return $return;
    }

    private function getDomDocumentFromFile(string $name): \DOMDocument
    {
        $content = file_get_contents($name);

        if (false === $content) {
            throw new \InvalidArgumentException(sprintf('Cannot read file "%s".', $name));
        }

        $xml = new \DOMDocument();

        // Strip white space
        $xml->preserveWhiteSpace = false;

        // Use loadXML() instead of load() (see contao/core#7192)
        $xml->loadXML($content);

        return $xml;
    }

    /**
     * Removes extra spaces in closing tags.
     */
    private function fixClosingTags(\DOMNode $node): string
    {
        return str_replace('</ em>', '</em>', $node->nodeValue);
    }

    /**
     * Splits the ID attribute and returns the chunks.
     */
    private function getChunksFromUnit(\DOMElement $unit): array
    {
        $chunks = explode('.', $unit->getAttribute('id'));

        // Handle keys with dots
        if (preg_match('/tl_layout\.[a-z]+\.css\./', $unit->getAttribute('id'))) {
            $chunks = [$chunks[0], $chunks[1].'.'.$chunks[2], $chunks[3]];
        }

        return $chunks;
    }

    /**
     * Returns a string representation of the global PHP language array.
     */
    private function getStringRepresentation(array $chunks, string $value): string
    {
        return match (\count($chunks)) {
            2 => sprintf(
                "\$GLOBALS['TL_LANG']['%s'][%s] = %s;\n",
                $chunks[0],
                $this->quoteKey($chunks[1]),
                $this->quoteValue($value)
            ),
            3 => sprintf(
                "\$GLOBALS['TL_LANG']['%s'][%s][%s] = %s;\n",
                $chunks[0],
                $this->quoteKey($chunks[1]),
                $this->quoteKey($chunks[2]),
                $this->quoteValue($value)
            ),
            4 => sprintf(
                "\$GLOBALS['TL_LANG']['%s'][%s][%s][%s] = %s;\n",
                $chunks[0],
                $this->quoteKey($chunks[1]),
                $this->quoteKey($chunks[2]),
                $this->quoteKey($chunks[3]),
                $this->quoteValue($value)
            ),
            default => throw new \OutOfBoundsException('Cannot load less than 2 or more than 4 levels in XLIFF language files.'),
        };
    }

    /**
     * Adds the labels to the global PHP language array.
     */
    private function addGlobal(array $chunks, string $value): void
    {
        if (!$this->addToGlobals) {
            return;
        }

        $data = &$GLOBALS['TL_LANG'];

        foreach ($chunks as $key) {
            if (!\is_array($data)) {
                $data = [];
            }

            $data = &$data[$key];
        }

        $data = $value;
    }

    private function quoteKey(string $key): int|string
    {
        if ('0' === $key) {
            return 0;
        }

        if (is_numeric($key)) {
            return (int) $key;
        }

        return "'".str_replace("'", "\\'", $key)."'";
    }

    private function quoteValue(string $value): string
    {
        $value = str_replace("\n", '\n', $value);

        if (str_contains($value, '\n')) {
            return '"'.str_replace(['$', '"'], ['\\$', '\\"'], $value).'"';
        }

        return "'".str_replace("'", "\\'", $value)."'";
    }
}
