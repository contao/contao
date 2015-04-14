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
 * No active pages found exception.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class NoPagesFoundHttpException extends NotFoundHttpException
{
}
