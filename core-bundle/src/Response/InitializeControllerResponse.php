<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * Custom response class to support legacy entry points.
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0
 */
class InitializeControllerResponse extends Response
{
}
