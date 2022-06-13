<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Exception;

use Symfony\Component\HttpFoundation\Exception\BadRequestException as BaseBadRequestExceptionAlias;

class BadRequestException extends BaseBadRequestExceptionAlias
{
}
