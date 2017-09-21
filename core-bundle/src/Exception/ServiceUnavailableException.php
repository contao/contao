<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Exception;

@trigger_error('Using the ServiceUnavailableException class has been deprecated and will no longer work in Contao 5.0. Use the Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException class instead.', E_USER_DEPRECATED);

/**
 * @deprecated Deprecated since Contao 4.1, to be removed in Contao 5.0; use the
 *             Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException instead
 */
class ServiceUnavailableException extends \RuntimeException
{
}
