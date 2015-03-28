<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException as BaseBadRequestHttpException;

/**
 * This exception is thrown when there is an issue with the request.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class BadRequestHttpException extends BaseBadRequestHttpException
{
    /**
     * {@inheritdoc}
     */
    public function __construct($message = 'Bad request', \Exception $previous = null, $code = 0) {
        parent::__construct($message, $previous, $code);
    }
}
