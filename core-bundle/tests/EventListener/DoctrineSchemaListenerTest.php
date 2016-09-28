<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Doctrine\Schema;

use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Contao\CoreBundle\EventListener\DoctrineSchemaListener;
use Contao\CoreBundle\Test\TestCase;

/**
 * Tests the DoctrineSchemaListener class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class DoctrineSchemaListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $provider = new DcaSchemaProvider($this->mockContainerWithContaoScopes());
        $listener = new DoctrineSchemaListener($provider);

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\DoctrineSchemaListener', $listener);
    }
}
