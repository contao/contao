<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Util;

use Psr\Cache\CacheItemPoolInterface;

/**
 * @deprecated Deprecated since Contao 4.13, to be removed in Contao 5.0; use
 *             the Contao\CoreBundle\String\SimpleTokenExpressionLanguage class instead
 */
class SimpleTokenExpressionLanguage extends \Contao\CoreBundle\String\SimpleTokenExpressionLanguage
{
    public function __construct(?CacheItemPoolInterface $cache = null, ?\IteratorAggregate $taggedProviders = null)
    {
        trigger_deprecation('contao/core-bundle', '4.13', 'Using the "Contao\CoreBundle\Util\SimpleTokenExpressionLanguage" class has been deprecated and will no longer work in Contao 5.0. Use the "Contao\CoreBundle\String\SimpleTokenExpressionLanguage" class instead.');

        parent::__construct($cache, $taggedProviders);
    }
}
