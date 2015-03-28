<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * This exception is thrown when no layout has been specified.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class NoLayoutException extends HttpException
{
    /**
     * {@inheritdoc}
     */
    public function __construct($message = 'No layout specified', \Exception $previous = null, $code = 0)
    {
        parent::__construct(501, $message, $previous, array(), $code);
    }
}
