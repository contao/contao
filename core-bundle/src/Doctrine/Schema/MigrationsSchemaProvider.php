<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\Schema;

use Doctrine\DBAL\Migrations\Provider\SchemaProviderInterface;

/**
 * The migrations schema provider is only used if the Doctrine migrations bundle is
 * installed, because it implements the necessary interface.
 */
class MigrationsSchemaProvider extends DcaSchemaProvider implements SchemaProviderInterface
{
}
