<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException as BaseNotFoundHttpException;

/**
 * This exception is thrown when no active pages can be found.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class NotFoundHttpException extends BaseNotFoundHttpException implements TemplateHttpExceptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct($message = 'Page not found', \Exception $previous = null, $code = 0 )
    {
        parent::__construct($message, $previous, $code);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultTemplate() {
        return 'be_no_page';
    }
}
