<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Definition\Builder;

/**
 * @method DcaNodeBuilder children()
 */
class DcaArrayNodeDefinition extends ArrayNodeDefinition
{
    /**
     * @var bool
     */
    protected $ignoreExtraKeys = true;

    /**
     * @var bool
     */
    protected $removeExtraKeys = false;
}
