<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Helper exception class to test that unknown HttpExceptionInterface exceptions are correctly handled.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
abstract class ExceptionListenerTestHelperHttpException extends \Exception implements HttpExceptionInterface {}
