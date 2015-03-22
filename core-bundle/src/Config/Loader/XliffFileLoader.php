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
    private $addToGlobals;

    /**
     * Constructor.
     *
     * @param bool $addToGlobals Defines if language labels should be added to $GLOBALS['TL_LANG']
     */
    public function __construct($addToGlobals = false)
    {
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

        return $this->convertXlfToPhp($file, $language, $this->addToGlobals);
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
     * @param boolean $blnLoad     Add the labels to the global language array
     *
     * @return string The PHP code
     */
    public static function convertXlfToPhp($strName, $strLanguage, $blnLoad=false)
    {
        // Read the .xlf file
        $xml = new \DOMDocument();
        $xml->preserveWhiteSpace = false;

        // Use loadXML() instead of load() (see contao/core#7192)
        $xml->loadXML(file_get_contents($strName));

        $return = "\n// " . str_replace(TL_ROOT . '/', '', $strName) . "\n";
        $units = $xml->getElementsByTagName('trans-unit');

        // Set up the quotekey function
        $quotekey = function($key) {
            if ($key === '0') {
                return 0;
            } elseif (is_numeric($key)) {
                return intval($key);
            } else {
                return "'$key'";
            }
        };

        // Set up the quotevalue function
        $quotevalue = function($value) {
            $value = str_replace("\n", '\n', $value);

            if (strpos($value, '\n') !== false) {
                return '"' . str_replace(array('$', '"'), array('\\$', '\\"'), $value) . '"';
            } else {
                return "'" . str_replace("'", "\\'", $value) . "'";
            }
        };

        /** @var \DOMElement[] $units */
        foreach ($units as $unit) {
            $node = ($strLanguage == 'en') ? $unit->getElementsByTagName('source') : $unit->getElementsByTagName('target');

            if ($node === null || $node->item(0) === null) {
                continue;
            }

            $value = $node->item(0)->nodeValue;

            // Some closing </em> tags oddly have an extra space in
            if (strpos($value, '</ em>') !== false) {
                $value = str_replace('</ em>', '</em>', $value);
            }

            $chunks = explode('.', $unit->getAttribute('id'));

            // Handle keys with dots
            if (preg_match('/tl_layout\.[a-z]+\.css\./', $unit->getAttribute('id'))) {
                $chunks = array($chunks[0], $chunks[1] . '.' . $chunks[2], $chunks[3]);
            }

            // Create the array entries
            switch (count($chunks)) {
                case 2:
                    $return .= "\$GLOBALS['TL_LANG']['" . $chunks[0] . "'][" . $quotekey($chunks[1]) . "] = " . $quotevalue($value) . ";\n";

                    if ($blnLoad) {
                        $GLOBALS['TL_LANG'][$chunks[0]][$chunks[1]] = $value;
                    }
                    break;

                case 3:
                    $return .= "\$GLOBALS['TL_LANG']['" . $chunks[0] . "'][" . $quotekey($chunks[1]) . "][" . $quotekey($chunks[2]) . "] = " . $quotevalue($value) . ";\n";

                    if ($blnLoad) {
                        $GLOBALS['TL_LANG'][$chunks[0]][$chunks[1]][$chunks[2]] = $value;
                    }
                    break;

                case 4:
                    $return .= "\$GLOBALS['TL_LANG']['" . $chunks[0] . "'][" . $quotekey($chunks[1]) . "][" . $quotekey($chunks[2]) . "][" . $quotekey($chunks[3]) . "] = " . $quotevalue($value) . ";\n";

                    if ($blnLoad) {
                        $GLOBALS['TL_LANG'][$chunks[0]][$chunks[1]][$chunks[2]][$chunks[3]] = $value;
                    }
                    break;
            }
        }

        return rtrim($return);
    }
}
