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
 * Reads XLIFF files and converts them into Contao language arrays.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class XliffFileLoader extends Loader
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var bool
     */
    private $addToGlobals;

    /**
     * Constructor.
     *
     * @param string $rootDir      The kernel root directory
     * @param bool   $addToGlobals True to add the labels to $GLOBALS['TL_LANG']
     */
    public function __construct($rootDir, $addToGlobals = false)
    {
        $this->rootDir      = dirname($rootDir);
        $this->addToGlobals = $addToGlobals;
    }

    /**
     * Reads the contents of a XLIFF file and returns the PHP code.
     *
     * @param string      $file A PHP file path
     * @param string|null $type The resource type
     *
     * @return string The PHP code without the PHP tags
     */
    public function load($file, $type = null)
    {
        return $this->convertXlfToPhp($file, ($type ?: 'en'));
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'xlf' === pathinfo($resource, PATHINFO_EXTENSION);
    }

    /**
     * Converts an XLIFF file into a PHP language file.
     *
     * @param string  $name     The name of the XLIFF file
     * @param string  $language The language code
     *
     * @return string The PHP code
     */
    private function convertXlfToPhp($name, $language)
    {
        $xml = new \DOMDocument();
        $xml->preserveWhiteSpace = false;

        // Use loadXML() instead of load() (see contao/core#7192)
        $xml->loadXML(file_get_contents($name));

        $return = "\n\n// " . str_replace($this->rootDir . '/', '', $name) . "\n";
        $units = $xml->getElementsByTagName('trans-unit');

        // FIXME: refactor
        /** @var \DOMElement[] $units */
        foreach ($units as $unit) {
            $node = ('en' === $language)
                ? $unit->getElementsByTagName('source')
                : $unit->getElementsByTagName('target')
            ;

            if ($node === null || $node->item(0) === null) {
                continue;
            }

            $value = $node->item(0)->nodeValue;

            // Some closing </em> tags oddly have an extra space in
            $value = str_replace('</ em>', '</em>', $value);

            $chunks = explode('.', $unit->getAttribute('id'));

            // Handle keys with dots
            if (preg_match('/tl_layout\.[a-z]+\.css\./', $unit->getAttribute('id'))) {
                $chunks = [$chunks[0], $chunks[1] . '.' . $chunks[2], $chunks[3]];
            }

            $return .= $this->getStringRepresentation($chunks, $value);

            $this->addGlobal($chunks, $value);
        }

        return rtrim($return);
    }

    /**
     * Returns a string representation of the global PHP language array.
     *
     * @param array $chunks The path fragments
     * @param mixed $value  The label
     *
     * @return string The string representation of the array
     *
     * @throws \OutOfBoundsException If less than 2 or more than 4 chunks are given
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
     * Adds the labels to the global PHP language array.
     *
     * @param array $chunks The path fragments
     * @param mixed $value  The label
     */
    private function addGlobal(array $chunks, $value)
    {
        if (false === $this->addToGlobals) {
            return;
        }

        $data = &$GLOBALS['TL_LANG'];

        foreach ($chunks as $key) {
            $data = &$data[$key];
        }

        $data = $value;
    }

    /**
     * Quotes an array key to be used as PHP string.
     *
     * @param string $key The key
     *
     * @return int|string The quoted string
     */
    private function quoteKey($key)
    {
        if ($key === '0') {
            return 0;
        }

        if (is_numeric($key)) {
            return intval($key);
        }

        return "'$key'";
    }

    /**
     * Quotes a value to be used as PHP string.
     *
     * @param string $value The value
     *
     * @return string The quoted string
     */
    private function quoteValue($value)
    {
        $value = str_replace("\n", '\n', $value);

        if (strpos($value, '\n') !== false) {
            return '"' . str_replace(array('$', '"'), array('\\$', '\\"'), $value) . '"';
        }

        return "'" . str_replace("'", "\\'", $value) . "'";
    }
}
