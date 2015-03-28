<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Exception;

use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * To be thrown when the configuration has not been completed yet.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class IncompleteInstallationException extends ServiceUnavailableHttpException
{
    /**
     * {@inheritdoc}
     */
    public function __construct(
        $retryAfter = null,
        $message = 'The installation has not been completed. Please finish the configuration.',
        \Exception $previous = null,
        $code = 0
    ) {
        parent::__construct($retryAfter, $message, $previous, $code);
    }
}
