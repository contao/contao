<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Helps testing that unknown HTTP exceptions are handled correctly.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
abstract class ExceptionListenerTestHelperHttpException extends \Exception implements HttpExceptionInterface
{
}
