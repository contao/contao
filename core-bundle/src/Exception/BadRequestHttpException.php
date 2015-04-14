<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException as BaseBadRequestHttpException;

/**
 * Bad request exception.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
// FIXME: why don't we use the parent class?
class BadRequestHttpException extends BaseBadRequestHttpException
{
}
