<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Exception;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException as BaseAccessDeniedHttpException;

/**
 * This exception is thrown when the access to a resource has been denied.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class AccessDeniedHttpException extends BaseAccessDeniedHttpException
{
    /**
     * {@inheritdoc}
     */
    public function __construct($message = 'Forbidden', \Exception $previous = null, $code = 0)
    {
        parent::__construct($message, $previous, $code);
    }
}
