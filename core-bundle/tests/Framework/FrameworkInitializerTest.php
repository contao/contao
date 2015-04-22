<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Framework;

use Contao\CoreBundle\Framework\FrameworkInitializer;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\ImmutableEventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests for Initializer
 *
 * @author Dominik Tomasi <https://github.com/dtomasi>
 * @todo The Tests for FrameworkInitializer must be completely rewritten
 */
class FrameworkInitializerTest extends TestCase
{
    /**
     * Test proper instance
     */
    public function testInstantiate()
    {
        $initializer = new FrameworkInitializer();
        $this->assertInstanceOf('Contao\CoreBundle\Framework\FrameworkInitializer', $initializer);
    }
}
