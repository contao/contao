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
 * Reads PHP files and returns the content without the opening and closing PHP tags.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PhpFileLoader extends Loader
{
    /**
     * Reads the contents of a PHP file stripping the opening and closing PHP tags.
     *
     * @param string      $file A PHP file path
     * @param string|null $type The resource type
     *
     * @return string The PHP code without the PHP tags
     */
    public function load($file, $type = null)
    {
        $code = rtrim(file_get_contents($file));

        // Opening tag
        if (strncmp($code, '<?php', 5) === 0) {
            $code = substr($code, 5);
        }

        // die() statement
        $code = str_replace(
            [
                " if (!defined('TL_ROOT')) die('You cannot access this file directly!');",
                " if (!defined('TL_ROOT')) die('You can not access this file directly!');",
            ],
            '',
            $code
        );

        // Closing tag
        if (substr($code, -2) == '?>') {
            $code = substr($code, 0, -2);
        }

        return rtrim($code) . "\n";
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'php' === pathinfo($resource, PATHINFO_EXTENSION);
    }
}
