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
 * PhpFileLoader reads PHP files and returns the content without opening and closing PHP tags
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PhpFileLoader extends Loader
{
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
        $strCode = rtrim(file_get_contents($file));

        // Opening tag
        if (strncmp($strCode, '<?php', 5) === 0) {
            $strCode = substr($strCode, 5);
        }

        // die() statement
        $strCode = str_replace(
            array(
                " if (!defined('TL_ROOT')) die('You cannot access this file directly!');",
                " if (!defined('TL_ROOT')) die('You can not access this file directly!');"
            ),
            '',
            $strCode
        );

        // Closing tag
        if (substr($strCode, -2) == '?>') {
            $strCode = substr($strCode, 0, -2);
        }

        return rtrim($strCode);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'php' === pathinfo($resource, PATHINFO_EXTENSION);
    }
}
