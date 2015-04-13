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
 * This exception is thrown when no applicable page can be found.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class NotFoundHttpException extends BaseNotFoundHttpException
{
}
