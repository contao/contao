<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Helps testing that unknown HTTP exceptions are handled correctly.
 */
abstract class ExceptionListenerTestHelperHttpException extends \Exception implements HttpExceptionInterface
{
}
