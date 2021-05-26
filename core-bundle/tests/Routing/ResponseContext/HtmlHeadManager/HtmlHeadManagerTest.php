<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\ResponseContext\HtmlHeadManager;

use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadManager\HtmlHeadManager;
use PHPUnit\Framework\TestCase;

class HtmlHeadManagerTest extends TestCase
{
    public function testHeadManagerBasics(): void
    {
        $manager = new HtmlHeadManager();

        $manager->setTitle('foobar title');
        $manager->setMetaDescription('foobar description');

        $this->assertSame('index,follow', $manager->getMetaRobots()); // Test default

        $manager->setMetaRobots('noindex,nofollow');

        $this->assertSame('foobar title', $manager->getTitle());
        $this->assertSame('foobar description', $manager->getMetaDescription());
        $this->assertSame('noindex,nofollow', $manager->getMetaRobots());
    }
}
