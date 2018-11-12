<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Config;

use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Tests\TestCase;

class ResourceFinderTest extends TestCase
{
    public function testFailsIfTheSubpathIsInvalid(): void
    {
        $finder = new ResourceFinder([
            $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao',
            $this->getFixturesDir().'/system/modules/foobar',
        ]);

        $this->expectException('InvalidArgumentException');

        $finder->findIn('foo');
    }
}
