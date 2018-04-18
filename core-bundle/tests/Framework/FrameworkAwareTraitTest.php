<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Framework;

use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\CoreBundle\Tests\TestCase;

class FrameworkAwareTraitTest extends TestCase
{
    use FrameworkAwareTrait;

    /**
     * @group legacy
     *
     * @expectedDeprecation Using FrameworkAwareTrait::getFramework() has been deprecated %s.
     */
    public function testDeprecatesTheGetFrameworkMethod(): void
    {
        $framework = $this->mockContaoFramework();
        $this->setFramework($framework);

        $this->assertSame($framework, $this->getFramework());
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using FrameworkAwareTrait::getFramework() has been deprecated %s.
     */
    public function testFailsIfTheFrameworkHasNotBeenSet(): void
    {
        $this->expectException('LogicException');
        $this->getFramework();
    }
}
