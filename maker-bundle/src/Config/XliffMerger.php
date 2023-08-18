<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\MakerBundle\Config;

class XliffMerger
{
    public function merge(\DOMDocument $root, \DOMDocument $document): \DOMDocument
    {
        $body = $root->getElementsByTagName('body')->item(0);

        // If there is no body tag, return the unchanged node
        if (null === $body) {
            return $root;
        }

        $importNodes = $this->getImportNodes($document);

        foreach ($importNodes as $importNode) {
            $id = $importNode->getAttribute('id');

            $duplicatesPath = new \DOMXPath($root);
            $duplicates = $duplicatesPath->query("//trans-unit[@id='".$id."']");

            if (false === $duplicates || $duplicates->length > 0) {
                continue;
            }

            $importedNode = $root->importNode($importNode, true);
            $body->appendChild($importedNode);
        }

        // Properly format the output XML
        $toFormat = (string) $root->saveXML($root);

        $root->preserveWhiteSpace = false;
        $root->formatOutput = true;
        $root->encoding = 'UTF-8';

        $root->loadXML($toFormat);

        return $root;
    }

    /**
     * @return array<int, \DOMElement>
     */
    private function getImportNodes(\DOMDocument $document): array
    {
        $nodes = [];

        $xpath = new \DOMXPath($document);
        $elements = $xpath->query('//trans-unit[@id]');

        if (false === $elements) {
            return $nodes;
        }

        /** @var \DOMElement $element */
        foreach ($elements as $element) {
            $nodes[] = $element;
        }

        return $nodes;
    }
}
