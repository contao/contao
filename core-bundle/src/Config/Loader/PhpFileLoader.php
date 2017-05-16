<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
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
 * @author Yanick Witschi <https://github.com/Toflar>
 */
class PhpFileLoader extends Loader
{
    /**
     * Reads the contents of a PHP file stripping the opening and closing PHP tags.
     *
     * @param string      $file
     * @param string|null $type
     *
     * @return string
     */
    public function load($file, $type = null)
    {
        list($code, $namespace) = $this->parseFile($file);

        $code = $this->stripLegacyCheck($code);

        if (false !== $namespace && 'namespaced' === $type) {
            $code = sprintf("\nnamespace %s {%s}\n", $namespace, $code);
        }

        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'php' === pathinfo($resource, PATHINFO_EXTENSION);
    }

    /**
     * Parses a file and returns the code and namespace.
     *
     * @param string $file
     *
     * @return array
     */
    private function parseFile($file)
    {
        $code = '';
        $namespace = '';
        $buffer = false;
        $stream = new \PHP_Token_Stream($file);

        foreach ($stream as $token) {
            switch (true) {
                case $token instanceof \PHP_Token_OPEN_TAG:
                case $token instanceof \PHP_Token_CLOSE_TAG:
                    // remove
                    break;

                case false !== $buffer:
                    $buffer .= $token;

                    if (';' === (string) $token) {
                        $code .= $this->handleDeclare($buffer);
                        $buffer = false;
                    }
                    break;

                case $token instanceof \PHP_Token_NAMESPACE:
                    if ('{' === $token->getName()) {
                        $namespace = false;
                        $code .= $token;
                    } else {
                        $namespace = $token->getName();
                        $stream->seek($token->getEndTokenId());
                    }
                    break;

                case $token instanceof \PHP_Token_DECLARE:
                    $buffer = (string) $token;
                    break;

                default:
                    $code .= $token;
            }
        }

        return [$code, $namespace];
    }

    /**
     * Handles the declare() statement.
     *
     * @param string $code
     *
     * @return string
     */
    private function handleDeclare($code)
    {
        $code = preg_replace('/(,\s*)?strict_types\s*=\s*1(\s*,)?/', '', $code);

        if (preg_match('/declare\(\s*\)/', $code)) {
            return '';
        }

        return str_replace(' ', '', $code);
    }

    /**
     * Strips the legacy check from the code.
     *
     * @param string $code
     *
     * @return string
     */
    private function stripLegacyCheck($code)
    {
        $code = str_replace(
            [
                "if (!defined('TL_ROOT')) die('You cannot access this file directly!');",
                "if (!defined('TL_ROOT')) die('You can not access this file directly!');",
            ],
            '',
            $code
        );

        return "\n".trim($code)."\n";
    }
}
