<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\File;

use Contao\Model\MetadataTrait;

trigger_deprecation('contao/core-bundle', '5.3', 'Using "Contao\CoreBundle\File\ModelMetadataTrait" has been deprecated and will no longer work in Contao 6. Use "Contao\Model\MetadataTrait" instead.');

/**
 * @deprecated Deprecated since Contao 5.3, to be removed in Contao 6;
 *             use Contao\Model\MetadataTrait instead.
 */
trait ModelMetadataTrait
{
    use MetadataTrait;
}
