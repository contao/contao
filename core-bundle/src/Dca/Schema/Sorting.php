<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Schema;

/**
 * Object representation of the sorting part of a data container array.
 */
class Sorting extends Schema
{
    /**
     * @return Callback<mixed, string>
     */
    public function childRecordCallback(): Callback
    {
        return $this->getSchema('child_record_callback', Callback::class);
    }
}
