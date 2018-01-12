<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Session\Attribute;

use Contao\CoreBundle\Session\LazySessionAccess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class LazySessionAccessTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $accessor = new LazySessionAccess($session);

        $this->assertInstanceOf('Contao\CoreBundle\Session\LazySessionAccess', $accessor);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using $_SESSION has been deprecated %s.
     */
    public function testStartsSessionOnAccess(): void
    {
        $beBag = new AttributeBag();
        $beBag->setName('contao_backend');

        $feBag = new AttributeBag();
        $feBag->setName('contao_frontend');

        $session = new Session(new MockArraySessionStorage());
        $session->registerBag($beBag);
        $session->registerBag($feBag);

        $SESSION = new LazySessionAccess($session);

        $this->assertFalse($session->isStarted());

        $SESSION['foobar'] = 'test';

        $this->assertTrue($session->isStarted());
        $this->assertSame($beBag, $_SESSION['BE_DATA']);
        $this->assertSame($feBag, $_SESSION['FE_DATA']);
        $this->assertSame('test', $session->get('foobar'));
    }
}
