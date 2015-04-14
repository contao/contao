<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Helps testing that unknown HTTP exceptions are handled correctly.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
abstract class ExceptionListenerTestHelperHttpException extends \Exception implements HttpExceptionInterface
{
}
