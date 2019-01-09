<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\DependencyInjection;

use Contao\CoreBundle\Tests\TestCase;
use Contao\ManagerBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testAddsTheManagerPath(): void
    {
        $configuration = (new Processor())->processConfiguration(new Configuration(), []);

        $this->assertNull($configuration['manager_path']);
    }
}
