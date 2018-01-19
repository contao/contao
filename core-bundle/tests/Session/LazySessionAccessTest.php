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
use Contao\CoreBundle\Session\MockNativeSessionStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class LazySessionAccessTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $request = new Request();
        $request->setSession(new Session(new MockNativeSessionStorage()));

        $this->assertInstanceOf('Contao\CoreBundle\Session\LazySessionAccess', new LazySessionAccess($request));
    }

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

        $_SESSION = new LazySessionAccess($this->mockRequest($session));

        $this->assertFalse($session->isStarted());
        $this->assertFalse(isset($_SESSION['foobar']['nested']));
        $this->assertTrue($session->isStarted());
        $this->assertSame($beBag, $_SESSION['BE_DATA']);
        $this->assertSame($feBag, $_SESSION['FE_DATA']);
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

        $_SESSION = new LazySessionAccess($this->mockRequest($session));

        $this->assertFalse($session->isStarted());
        $this->assertNull($_SESSION['foobar']['nested']);
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

        $_SESSION = new LazySessionAccess($this->mockRequest($session));

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

        $_SESSION = new LazySessionAccess($this->mockRequest($session));

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

        $_SESSION = new LazySessionAccess($this->mockRequest($session));

        $this->assertFalse($session->isStarted());

        \count($_SESSION);

        $this->assertTrue($session->isStarted());
        $this->assertSame($beBag, $_SESSION['BE_DATA']);
        $this->assertSame($feBag, $_SESSION['FE_DATA']);
    }

    public function testDoesNotStartTheSessionOnOffsetExistsIfThereIsNoPreviousSession(): void
    {
        $session = new Session(new MockNativeSessionStorage());
        $_SESSION = new LazySessionAccess($this->mockRequest($session, false));

        $this->assertFalse($session->isStarted());
        $this->assertFalse(isset($_SESSION['foobar']['nested']));
        $this->assertFalse($session->isStarted());
    }

    public function testDoesNotStartTheSessionOnOffsetGetIfThereIsNoPreviousSession(): void
    {
        $session = new Session(new MockNativeSessionStorage());
        $_SESSION = new LazySessionAccess($this->mockRequest($session, false));

        $this->assertFalse($session->isStarted());
        $this->assertNull($_SESSION['foobar']['nested']);
        $this->assertFalse($session->isStarted());
    }

    public function testDoesNotStartTheSessionOnOffsetUnsetIfThereIsNoPreviousSession(): void
    {
        $session = new Session(new MockNativeSessionStorage());
        $_SESSION = new LazySessionAccess($this->mockRequest($session, false));

        $this->assertFalse($session->isStarted());

        unset($_SESSION['foobar']['nested']);

        $this->assertFalse($session->isStarted());
    }

    public function testDoesNotStartTheSessionOnCountIfThereIsNoPreviousSession(): void
    {
        $session = new Session(new MockNativeSessionStorage());
        $_SESSION = new LazySessionAccess($this->mockRequest($session, false));

        $this->assertFalse($session->isStarted());
        $this->assertCount(0, $_SESSION);
        $this->assertFalse($session->isStarted());
    }

    /**
     * Mocks a request with session.
     *
     * @param SessionInterface $session
     * @param bool             $hasPreviousSession
     *
     * @return Request|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockRequest(SessionInterface $session, bool $hasPreviousSession = true): Request
    {
        $request = $this->createMock(Request::class);

        $request
            ->expects($this->once())
            ->method('hasSession')
            ->willReturn(true)
        ;

        $request
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($session)
        ;

        $request
            ->expects($this->once())
            ->method('hasPreviousSession')
            ->willReturn($hasPreviousSession)
        ;

        return $request;
    }
}
