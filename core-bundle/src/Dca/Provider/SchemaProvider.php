<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Provider;

use Contao\CoreBundle\Dca\Schema\Field;

class SchemaProvider implements SchemaProviderInterface
{
    public static function getServiceSubscribingSchemas(): array
    {
        return [
            Field::class,
        ];
    }
}
