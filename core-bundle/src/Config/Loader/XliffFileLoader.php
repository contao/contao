<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Config\Loader;

use Symfony\Component\Config\Loader\Loader;

/**
 * XliffFileLoader reads XLIFF files and converts them to Contao language array
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class XliffFileLoader extends Loader
{
    private $rootDir;
    private $addToGlobals;

    /**
     * Constructor.
     *
     * @param string $rootDir      The kernel root directory
     * @param bool   $addToGlobals Defines if language labels should be added to $GLOBALS['TL_LANG']
     */
    public function __construct($rootDir, $addToGlobals = false)
    {
        $this->rootDir      = dirname($rootDir);
        $this->addToGlobals = $addToGlobals;
    }

    /**
     * Read the contents of a PHP file, stripping the opening and closing PHP tags
     *
     * @param string      $file A PHP file path
     * @param string|null $type The resource type
     *
     * @return string The PHP code without the PHP tags
     */
    public function load($file, $type = null)
    {
        $language = $type ?: 'en';

        return $this->convertXlfToPhp($file, $language);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'xlf' === pathinfo($resource, PATHINFO_EXTENSION);
    }

    /**
     * Convert an .xlf file into a PHP language file
     *
     * @param string  $strName     The name of the .xlf file
     * @param string  $strLanguage The language code
     *
     * @return string The PHP code
     */
    private function convertXlfToPhp($strName, $strLanguage)
    {
        // Read the .xlf file
        $xml = new \DOMDocument();
        $xml->preserveWhiteSpace = false;

        // Use loadXML() instead of load() (see contao/core#7192)
        $xml->loadXML(file_get_contents($strName));

        $return = "\n// " . str_replace($this->rootDir . '/', '', $strName) . "\n";
        $units = $xml->getElementsByTagName('trans-unit');

        /** @var \DOMElement[] $units */
        foreach ($units as $unit) {
            $node = ($strLanguage == 'en') ? $unit->getElementsByTagName('source') : $unit->getElementsByTagName('target');

            if ($node === null || $node->item(0) === null) {
                continue;
            }

            $value = $node->item(0)->nodeValue;

            // Some closing </em> tags oddly have an extra space in
            $value = str_replace('</ em>', '</em>', $value);

            $chunks = explode('.', $unit->getAttribute('id'));

            // Handle keys with dots
            if (preg_match('/tl_layout\.[a-z]+\.css\./', $unit->getAttribute('id'))) {
                $chunks = array($chunks[0], $chunks[1] . '.' . $chunks[2], $chunks[3]);
            }

            $return .= $this->getStringRepresentation($chunks, $value);
            $this->addGlobal($chunks, $value);
        }

        return rtrim($return);
    }

    /**
     * Returns a string representation of the global PHP language array
     *
     * @param array $chunks
     * @param mixed $value
     *
     * @return string
     *
     * @throws \OutOfBoundsException If less than 2 or more than 4 chunks are given.
     */
    private function getStringRepresentation(array $chunks, $value)
    {
        switch (count($chunks)) {
            case 2:
                return "\$GLOBALS['TL_LANG']['" . $chunks[0] . "'][" . $this->quoteKey($chunks[1]) . "] = " . $this->quoteValue($value) . ";\n";

            case 3:
                return "\$GLOBALS['TL_LANG']['" . $chunks[0] . "'][" . $this->quoteKey($chunks[1]) . "][" . $this->quoteKey($chunks[2]) . "] = " . $this->quoteValue($value) . ";\n";

            case 4:
                return "\$GLOBALS['TL_LANG']['" . $chunks[0] . "'][" . $this->quoteKey($chunks[1]) . "][" . $this->quoteKey($chunks[2]) . "][" . $this->quoteKey($chunks[3]) . "] = " . $this->quoteValue($value) . ";\n";
        }

        throw new \OutOfBoundsException('Cannot load less than 2 or more than 4 levels in XLIFF language files.');
    }

    /**
     * Adds labels to the global PHP language array if enabled
     *
     * @param array $chunks
     * @param mixed $value
     */
    private function addGlobal(array $chunks, $value)
    {
        if ($this->addToGlobals)
        {
            $data = &$GLOBALS['TL_LANG'];

            foreach ($chunks as $key) {
                $data = &$data[$key];
            }

            $data = $value;
        }
    }

    /**
     * Quote array key for PHP string
     *
     * @param string $key
     *
     * @return int|string
     */
    private function quoteKey($key)
    {
        if ($key === '0') {
            return 0;
        } elseif (is_numeric($key)) {
            return intval($key);
        } else {
            return "'$key'";
        }
    }

    /**
     * Quote value for PHP string
     *
     * @param string $value
     *
     * @return string
     */
    private function quoteValue($value)
    {
        $value = str_replace("\n", '\n', $value);

        if (strpos($value, '\n') !== false) {
            return '"' . str_replace(array('$', '"'), array('\\$', '\\"'), $value) . '"';
        } else {
            return "'" . str_replace("'", "\\'", $value) . "'";
        }
    }
}
