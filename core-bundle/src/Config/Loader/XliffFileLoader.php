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

use Contao\CoreBundle\Translation\LegacyGlobalsProcessor;
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

            $chunks = LegacyGlobalsProcessor::getPartsFromKey($unit->getAttribute('id'));
            $value = $this->fixClosingTags($node->item(0));

            $return .= LegacyGlobalsProcessor::getStringRepresentation($chunks, $value);

            if ($this->addToGlobals) {
                LegacyGlobalsProcessor::addGlobal($chunks, $value);
            }
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
}
