<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Session;

use Contao\CoreBundle\Session\LazySessionAccess;
use Contao\CoreBundle\Session\MockNativeSessionStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;

class LazySessionAccessTest extends TestCase
{
    /**
     * @group legacy
     *
     * @expectedDeprecation Using $_SESSION has been deprecated %s.
     */
    public function testStartsSessionOnOffsetExists(): void
    {
        $beBag = new AttributeBag();
        $beBag->setName('contao_backend');

        $feBag = new AttributeBag();
        $feBag->setName('contao_frontend');

        $session = new Session(new MockNativeSessionStorage());
        $session->registerBag($beBag);
        $session->registerBag($feBag);

        $_SESSION = new LazySessionAccess($session);

        $this->assertFalse($session->isStarted());
        $this->assertFalse(isset($_SESSION['foobar']['nested']));
        $this->assertTrue($session->isStarted());
        $this->assertSame($beBag, $_SESSION['BE_DATA']);
        $this->assertSame($feBag, $_SESSION['FE_DATA']);
    }

    /**
     * @group legacy
     */
    public function testDoesNotStartSessionOnOffsetExistsWithoutPreviousSession(): void
    {
        $beBag = new AttributeBag();
        $beBag->setName('contao_backend');

        $feBag = new AttributeBag();
        $feBag->setName('contao_frontend');

        $session = new Session(new MockNativeSessionStorage());
        $session->registerBag($beBag);
        $session->registerBag($feBag);

        $_SESSION = new LazySessionAccess($session, false);

        $this->assertFalse($session->isStarted());
        $this->assertFalse(isset($_SESSION['foobar']['nested']));
        $this->assertFalse($session->isStarted());
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using $_SESSION has been deprecated %s.
     */
    public function testStartsSessionOnOffsetGet(): void
    {
        $beBag = new AttributeBag();
        $beBag->setName('contao_backend');

        $feBag = new AttributeBag();
        $feBag->setName('contao_frontend');

        $session = new Session(new MockNativeSessionStorage());
        $session->registerBag($beBag);
        $session->registerBag($feBag);

        $_SESSION = new LazySessionAccess($session);

        $this->assertFalse($session->isStarted());
        $this->assertNull($_SESSION['foobar']);
        $this->assertTrue($session->isStarted());
        $this->assertSame($beBag, $_SESSION['BE_DATA']);
        $this->assertSame($feBag, $_SESSION['FE_DATA']);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using $_SESSION has been deprecated %s.
     */
    public function testStartsSessionOnOffsetSet(): void
    {
        $beBag = new AttributeBag();
        $beBag->setName('contao_backend');

        $feBag = new AttributeBag();
        $feBag->setName('contao_frontend');

        $session = new Session(new MockNativeSessionStorage());
        $session->registerBag($beBag);
        $session->registerBag($feBag);

        $_SESSION = new LazySessionAccess($session);

        $this->assertFalse($session->isStarted());

        $_SESSION['foobar']['nested'] = 'test';

        $this->assertTrue($session->isStarted());
        $this->assertSame($beBag, $_SESSION['BE_DATA']);
        $this->assertSame($feBag, $_SESSION['FE_DATA']);
        $this->assertSame(['nested' => 'test'], $_SESSION['foobar']);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using $_SESSION has been deprecated %s.
     */
    public function testStartsSessionOnOffsetUnset(): void
    {
        $beBag = new AttributeBag();
        $beBag->setName('contao_backend');

        $feBag = new AttributeBag();
        $feBag->setName('contao_frontend');

        $session = new Session(new MockNativeSessionStorage());
        $session->registerBag($beBag);
        $session->registerBag($feBag);

        $_SESSION = new LazySessionAccess($session);

        $this->assertFalse($session->isStarted());

        unset($_SESSION['foobar']['nested']);

        $this->assertTrue($session->isStarted());
        $this->assertSame($beBag, $_SESSION['BE_DATA']);
        $this->assertSame($feBag, $_SESSION['FE_DATA']);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using $_SESSION has been deprecated %s.
     */
    public function testStartsSessionOnCount(): void
    {
        $beBag = new AttributeBag();
        $beBag->setName('contao_backend');

        $feBag = new AttributeBag();
        $feBag->setName('contao_frontend');

        $session = new Session(new MockNativeSessionStorage());
        $session->registerBag($beBag);
        $session->registerBag($feBag);

        $_SESSION = new LazySessionAccess($session);

        $this->assertFalse($session->isStarted());

        \count($_SESSION);

        $this->assertTrue($session->isStarted());
        $this->assertSame($beBag, $_SESSION['BE_DATA']);
        $this->assertSame($feBag, $_SESSION['FE_DATA']);
    }

    /**
     * @group legacy
     */
    public function testDoesNotStartSessionOnCountWithoutPreviousSession(): void
    {
        $beBag = new AttributeBag();
        $beBag->setName('contao_backend');

        $feBag = new AttributeBag();
        $feBag->setName('contao_frontend');

        $session = new Session(new MockNativeSessionStorage());
        $session->registerBag($beBag);
        $session->registerBag($feBag);

        $_SESSION = new LazySessionAccess($session, false);

        $this->assertFalse($session->isStarted());
        $this->assertCount(0, $_SESSION);
        $this->assertFalse($session->isStarted());
    }
}
