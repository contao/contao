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
 * @extends SchemaCollection<Callback>
 */
class CallbackCollection extends SchemaCollection
{
    public function call(mixed ...$arguments): void
    {
        foreach ($this->children() as $callback) {
            $callback->call(...$arguments);
        }
    }

    /**
     * @return class-string<Callback<mixed, mixed>>
     */
    protected function getChildSchema(): string
    {
        return Callback::class;
    }
}
