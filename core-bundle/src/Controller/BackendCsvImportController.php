<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Controller\Backend\CsvImportController as BaseController;

trigger_deprecation('contao/core-bundle', '5.7', 'Using "Contao\CoreBundle\Controller\CsvImportController" is deprecated and will no longer work in Contao 6. Use "Contao\CoreBundle\Controller\Backend\CsvImportController" instead.');

/**
 * @deprecated Deprecated since Contao 5.7, to be removed in Contao 6;
 *             use Contao\CoreBundle\Controller\Backend\CsvImportController instead.
 */
class BackendCsvImportController extends BaseController
{
}
