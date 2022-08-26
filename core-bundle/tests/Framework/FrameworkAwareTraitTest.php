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
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

class FrameworkAwareTraitTest extends TestCase
{
    use ExpectDeprecationTrait;
    use FrameworkAwareTrait;

    /**
     * @group legacy
     */
    public function testDeprecatesTheGetFrameworkMethod(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.3: Using "Contao\CoreBundle\Framework\FrameworkAwareTrait::getFramework()" has been deprecated %s.');

        $framework = $this->mockContaoFramework();
        $this->setFramework($framework);

        $this->assertSame($framework, $this->getFramework());
    }

    /**
     * @group legacy
     */
    public function testFailsIfTheFrameworkHasNotBeenSet(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.3: Using "Contao\CoreBundle\Framework\FrameworkAwareTrait::getFramework()" has been deprecated %s.');
        $this->expectException('LogicException');

        $this->getFramework();
    }
}
