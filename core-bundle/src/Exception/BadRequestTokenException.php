<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * This exception is thrown when there is an issue with the request token.
 *
 * FIXME: obsolete when using symfony security?
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class BadRequestTokenException extends BadRequestHttpException
{
    /**
     * {@inheritdoc}
     */
    public function __construct(
        $message = 'Invalid request token. Please <a href="javascript:window.location.href=window.location.href">go back</a> and try again.',
        \Exception $previous = null,
        $code = 0
    ) {
        parent::__construct($message, $previous, $code);
    }
}
