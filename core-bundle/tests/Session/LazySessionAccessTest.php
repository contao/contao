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
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;

class LazySessionAccessTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function tearDown(): void
    {
        unset($_SESSION);

        parent::tearDown();
    }

    /**
     * @group legacy
     */
    public function testStartsSessionOnOffsetExists(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.5: Using "$_SESSION" has been deprecated %s.');

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
     */
    public function testStartsSessionOnOffsetGet(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.5: Using "$_SESSION" has been deprecated %s.');

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
     */
    public function testStartsSessionOnOffsetSet(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.5: Using "$_SESSION" has been deprecated %s.');

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
     */
    public function testStartsSessionOnOffsetUnset(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.5: Using "$_SESSION" has been deprecated %s.');

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
     */
    public function testStartsSessionOnCount(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.5: Using "$_SESSION" has been deprecated %s.');

        $beBag = new AttributeBag();
        $beBag->setName('contao_backend');

        $feBag = new AttributeBag();
        $feBag->setName('contao_frontend');

        $session = new Session(new MockNativeSessionStorage());
        $session->registerBag($beBag);
        $session->registerBag($feBag);

        $_SESSION = new LazySessionAccess($session);

        $this->assertFalse($session->isStarted());

        $this->assertCount(5, $_SESSION);
        $this->assertTrue($session->isStarted());
        $this->assertSame($beBag, $_SESSION['BE_DATA']);
        $this->assertSame($feBag, $_SESSION['FE_DATA']);
    }

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
