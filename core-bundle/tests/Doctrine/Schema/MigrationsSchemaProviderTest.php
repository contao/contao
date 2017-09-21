<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Doctrine\Schema;

use Contao\CoreBundle\Doctrine\Schema\MigrationsSchemaProvider;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Doctrine\Bundle\DoctrineBundle\Registry;

class MigrationsSchemaProviderTest extends DcaSchemaProviderTest
{
    public function testCanBeInstantiated(): void
    {
        $provider = new MigrationsSchemaProvider(
            $this->createMock(ContaoFrameworkInterface::class),
            $this->createMock(Registry::class)
        );

        $this->assertInstanceOf('Contao\CoreBundle\Doctrine\Schema\MigrationsSchemaProvider', $provider);
    }
}
