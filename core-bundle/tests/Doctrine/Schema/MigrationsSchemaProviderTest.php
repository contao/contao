<?php

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

/**
 * Tests the DcaSchemaProvider class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class MigrationsSchemaProviderTest extends DcaSchemaProviderTest
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $provider = new MigrationsSchemaProvider(
            $this->getMock(ContaoFrameworkInterface::class),
            $this->getMockBuilder(Registry::class)->disableOriginalConstructor()->getMock()
        );

        $this->assertInstanceOf('Contao\CoreBundle\Doctrine\Schema\MigrationsSchemaProvider', $provider);
    }
}
