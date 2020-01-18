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

@trigger_error('Using the "Contao\CoreBundle\Exception\ServiceUnavailableException" class has been deprecated and will no longer work in Contao 5.0. Use the "Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException" class instead.', E_USER_DEPRECATED);

/**
 * @deprecated Deprecated since Contao 4.1, to be removed in Contao 5.0; use the
 *             Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException instead
 */
class ServiceUnavailableException extends \RuntimeException
{
}
