<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Backend;

use Contao\CoreBundle\Search\Backend\Hit;
use PHPUnit\Framework\TestCase;

class HitTest extends TestCase
{
    public function testHit(): void
    {
        $hit = (new Hit('title', 'https://example.com'))
            ->withContext('context')
            ->withImage('image')
            ->withEditUrl('https://example.com?edit=true')
        ;

        $this->assertSame('title', $hit->getTitle());
        $this->assertSame('https://example.com', $hit->getViewUrl());
        $this->assertSame('context', $hit->getContext());
        $this->assertSame('image', $hit->getImage());
        $this->assertSame('https://example.com?edit=true', $hit->getEditUrl());
    }
}
