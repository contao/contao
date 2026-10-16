<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\ManagerBundle\Runtime\ApplicationFactory;
use Symfony\Component\HttpFoundation\Request;

require_once dirname(__DIR__).'/contao-bootstrap.php';

return static fn (Request $request) => new ApplicationFactory(dirname(__DIR__))->createHttpKernel($request, true);
