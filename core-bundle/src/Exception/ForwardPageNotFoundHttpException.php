<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * This exception is thrown when there can no applicable forward page found.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class ForwardPageNotFoundHttpException extends NotFoundHttpException implements TemplateHttpExceptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct($message = 'Forward page not found', \Exception $previous = null, $code = 0 )
    {
        parent::__construct($message, $previous, $code);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultTemplate() {
        return 'be_no_forward';
    }
}
