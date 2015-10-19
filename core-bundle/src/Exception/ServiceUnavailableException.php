<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Exception;

/**
 * Serivce unavailable exception.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 *
 * @deprecated Deprecated since Contao 4.1, to be removed in Contao 5.0.
 *             Use the Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException instead.
 */
class ServiceUnavailableException extends \RuntimeException
{
    /**
     * Constructor.
     *
     * @param string     $message  The exception message
     * @param int        $code     The exception code
     * @param \Exception $previous The previous exception
     */
    public function __construct($message = "", $code = 0, \Exception $previous = null)
    {
        @trigger_error('Using the ServiceUnavailableException has been deprecated and will no longer work in Contao 5.0. Use the Lexik\\Bundle\\MaintenanceBundle\\Exception\\ServiceUnavailableException instead.', E_USER_DEPRECATED);
    }
}
