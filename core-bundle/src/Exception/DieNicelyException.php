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
 * Exception to trigger the "beautiful" error screen.
 *
 * NOTE: This intentionally does NOT implement the ResponseExceptionInterface as it is a special exception to be
 *       handled by the Contao event listener only in a special way.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class DieNicelyException extends HttpException
{
    /**
     * The template to use.
     *
     * @var string
     */
    private $template;

    /**
     * Construct the exception. Note: The message is NOT binary safe.
     *
     * @param string     $template [optional] The backend template to be shown for this exception.
     * @param string     $message  [optional] The Exception message to throw.
     * @param int        $code     [optional] The Exception code.
     * @param \Exception $previous [optional] The previous exception used for the exception chaining.
     */
    public function __construct(
        $template = 'be_error',
        $message = 'An error occurred while executing this script!',
        $statusCode = 500,
        array $headers = array(),
        $code = 0,
        \Exception $previous = null
    ) {
        parent::__construct($statusCode, $message, $previous, $headers, $code);

        $this->template = $template;
    }

    /**
     * Retrieve the template to display instead of the exception message.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
