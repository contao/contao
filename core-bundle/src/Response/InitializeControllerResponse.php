<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Response;

use Symfony\Component\HttpFoundation\Response;

/**
 * Custom response class to support legacy entry point scripts.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 *
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0
 */
class InitializeControllerResponse extends Response
{
}
