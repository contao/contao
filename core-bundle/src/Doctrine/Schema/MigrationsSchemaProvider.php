<?php

/*
 * This file is part of Contao.
 *
 *  Copyright (c) 2005-2016 Leo Feyer
 *
 *  @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Doctrine\Schema;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Provider\SchemaProviderInterface;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

/**
 * MigrationsSchemaProvider is only used if DoctrineMigrationsBundle is installed
 * because it implements the necessary interface.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class MigrationsSchemaProvider extends DcaSchemaProvider implements SchemaProviderInterface
{
}
