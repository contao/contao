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
    private string $projectDir;
    private bool $addToGlobals;

    public function __construct(string $projectDir, bool $addToGlobals = false)
    {
        $this->projectDir = $projectDir;
        $this->addToGlobals = $addToGlobals;
    }

    public function load($resource, string $type = null): string
    {
        return $this->convertXlfToPhp((string) $resource, $type ?: 'en');
    }

    public function supports($resource, string $type = null): bool
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

            if (null === $node->item(0)) {
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
        $xml = new \DOMDocument();

        // Strip white space
        $xml->preserveWhiteSpace = false;

        // Use loadXML() instead of load() (see contao/core#7192)
        $xml->loadXML(file_get_contents($name));

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
        if (!$chunks) {
            return '';
        }

        $string = "\$GLOBALS['TL_LANG']";

        foreach ($chunks as $part) {
            $string .= '['.$this->quoteKey($part).']';
        }

        return $string.' = '.$this->quoteValue($value).";\n";
    }

    /**
     * Adds the labels to the global PHP language array.
     */
    private function addGlobal(array $chunks, string $value): void
    {
        if (false === $this->addToGlobals) {
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

    /**
     * @return int|string
     */
    private function quoteKey(string $key)
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

        if (false !== strpos($value, '\n')) {
            return '"'.str_replace(['$', '"'], ['\\$', '\\"'], $value).'"';
        }

        return "'".str_replace("'", "\\'", $value)."'";
    }
}
