<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Doctrine\Schema;

use Contao\CoreBundle\Doctrine\Schema\MigrationsSchemaProvider;

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
            $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface')
        );

        $this->assertInstanceOf('Contao\CoreBundle\Doctrine\Schema\MigrationsSchemaProvider', $provider);
    }
}
