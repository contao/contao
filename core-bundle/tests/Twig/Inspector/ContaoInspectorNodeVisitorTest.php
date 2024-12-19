<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Inspector;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Inspector\InspectorNodeVisitor;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Twig\Environment;

class ContaoInspectorNodeVisitorTest extends TestCase
{
    public function testHasLowPriority(): void
    {
        $inspectorNodeVisitor = new InspectorNodeVisitor(
            new NullAdapter(),
            $this->createMock(Environment::class),
        );

        $this->assertSame(128, $inspectorNodeVisitor->getPriority());
    }
}
