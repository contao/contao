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

interface ValidatingSchemaInterface
{
    /**
     * Validate the schema data.
     *
     * @throws \Exception
     */
    public function validate(): void;
}
