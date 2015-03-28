<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Exception;

/**
 * This exception is thrown when no active pages can be found.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class NoPagesFoundHttpException extends NotFoundHttpException
{
    /**
     * {@inheritdoc}
     */
    public function __construct($message = 'No active pages found', \Exception $previous = null, $code = 0 )
    {
        parent::__construct($message, $previous, $code);
    }
}
