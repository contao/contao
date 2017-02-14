<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Internal server error HTTP exception.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InternalServerErrorHttpException extends HttpException
{
    /**
     * Constructor.
     *
     * @param string|null     $message
     * @param \Exception|null $previous
     * @param int             $code
     */
    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct(500, $message, $previous, [], $code);
    }
}
