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

trigger_deprecation('contao/core-bundle', '4.7', 'Using the "Contao\CoreBundle\Exception\PaletteNotFoundException" class has been deprecated and will no longer work in Contao 5.0. Use the "Contao\CoreBundle\DataContainer\PaletteNotFoundException" class instead.');

/**
 * @deprecated Deprecated since Contao 4.7, to be removed in Contao 5.0; use the
 *             Contao\CoreBundle\DataContainer\PaletteNotFoundException instead
 */
class PaletteNotFoundException extends \InvalidArgumentException
{
}
