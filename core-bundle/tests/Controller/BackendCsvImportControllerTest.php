<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Controller;

use Contao\CoreBundle\Controller\BackendCsvImportController;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the BackendControllerTest class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class BackendCsvImportControllerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $controller = new BackendCsvImportController(
            $this->mockContaoFramework(),
            $this->getMock('Doctrine\DBAL\Connection', [], [], '', false),
            new RequestStack(),
            $this->getRootDir()
        );

        $this->assertInstanceOf('Contao\CoreBundle\Controller\BackendCsvImportController', $controller);
    }
}
