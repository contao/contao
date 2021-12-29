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

use Contao\CoreBundle\Dca\Schema\ServiceSubscriberSchemaInterface;

interface SchemaProviderInterface
{
    /**
     * Provide all schema classes that have custom dependencies.
     * Each returned class must implement Contao\CoreBundle\Dca\Schema\ServiceSubscriberSchemaInterface.
     *
     * @return array<class-string<ServiceSubscriberSchemaInterface>>
     */
    public static function getServiceSubscribingSchemas(): array;
}
