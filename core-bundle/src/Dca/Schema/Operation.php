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
 * Object representation of an operation.
 */
class Operation extends Schema
{
    protected array $schemaClasses = [
        'button_callback' => Callback::class,
    ];

    /**
     * @return Callback<mixed, string>
     */
    public function buttonCallback(): Callback
    {
        return $this->getSchema('button_callback', Callback::class);
    }
}
