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
 * This exception is thrown when maintenance mode is active.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class MaintenanceModeActiveHttpException extends ServiceUnavailableHttpException implements TemplateHttpExceptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct(
        // FIXME: should we initialize this to a sane default?
        $retryAfter = null,
        $message = 'This site is currently down for maintenance. Please come back later.',
        \Exception $previous = null,
        $code = 0
    ) {
        parent::__construct($retryAfter, $message, $previous, $code);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultTemplate() {
        return 'be_unavailable';
    }
}
