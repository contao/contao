<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle;

use Symfony\Component\HttpFoundation\Response;

/**
 * A custom response to continue execution in custom entry point scripts (system/initialize.php).
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 *
 * @internal
 * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
 */
class InitializeControllerResponse extends Response
{
}
