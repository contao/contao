<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Doctrine\Schema;

use Doctrine\DBAL\Migrations\Provider\SchemaProviderInterface;

/**
 * MigrationsSchemaProvider is only used if DoctrineMigrationsBundle is installed
 * because it implements the necessary interface.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class MigrationsSchemaProvider extends DcaSchemaProvider implements SchemaProviderInterface
{
}
