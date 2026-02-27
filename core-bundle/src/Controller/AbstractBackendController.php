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

use Contao\CoreBundle\Controller\Backend\AbstractBackendController as BaseController;

trigger_deprecation('contao/core-bundle', '5.7', 'Using "Contao\CoreBundle\Controller\AbstractBackendController" is deprecated and will no longer work in Contao 7. Use "Contao\CoreBundle\Controller\Backend\AbstractBackendController" instead.');

/**
 * @deprecated Deprecated since Contao 5.7, to be removed in Contao 7;
 *             use Contao\CoreBundle\Controller\Backend\AbstractBackendController instead.
 */
class AbstractBackendController extends BaseController
{
}
