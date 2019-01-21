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

use Symfony\Component\Security\Core\Exception\AccessDeniedException as BaseAccessDeniedException;

class AccessDeniedException extends BaseAccessDeniedException
{
}
