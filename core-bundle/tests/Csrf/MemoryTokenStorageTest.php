<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Csrf;

use Contao\CoreBundle\Csrf\MemoryTokenStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Csrf\Exception\TokenNotFoundException;

class MemoryTokenStorageTest extends TestCase
{
    public function testStoresAndRemovesTokens(): void
    {
        $memoryTokenStorage = new MemoryTokenStorage();
        $memoryTokenStorage->initialize(['foo' => 'bar']);

        $this->assertTrue($memoryTokenStorage->hasToken('foo'));
        $this->assertFalse($memoryTokenStorage->hasToken('bar'));

        $memoryTokenStorage->setToken('bar', 'foo');

        $this->assertTrue($memoryTokenStorage->hasToken('bar'));
        $this->assertSame(['bar' => 'foo'], $memoryTokenStorage->getUsedTokens());
        $this->assertSame('bar', $memoryTokenStorage->getToken('foo'));
        $this->assertSame(['foo' => 'bar', 'bar' => 'foo'], $memoryTokenStorage->getUsedTokens());

        $token = $memoryTokenStorage->removeToken('foo');

        $this->assertSame('bar', $token);
        $this->assertFalse($memoryTokenStorage->hasToken('foo'));
        $this->assertSame(['foo' => null, 'bar' => 'foo'], $memoryTokenStorage->getUsedTokens());

        $token = $memoryTokenStorage->removeToken('bar');

        $this->assertSame('foo', $token);
        $this->assertFalse($memoryTokenStorage->hasToken('bar'));
        $this->assertSame(['foo' => null, 'bar' => null], $memoryTokenStorage->getUsedTokens());
    }

    public function testDoesNotReturnUsedTokensIfNotInitialized(): void
    {
        $memoryTokenStorage = new MemoryTokenStorage();

        $this->assertSame([], $memoryTokenStorage->getUsedTokens());
    }

    public function testFailsIfATokenDoesNotExist(): void
    {
        $memoryTokenStorage = new MemoryTokenStorage();
        $memoryTokenStorage->initialize(['foo' => 'bar']);

        $this->expectException(TokenNotFoundException::class);

        $memoryTokenStorage->getToken('bar');
    }

    public function testFailsToReturnATokenIfNotInitialized(): void
    {
        $memoryTokenStorage = new MemoryTokenStorage();

        $this->expectException('LogicException');

        $memoryTokenStorage->getToken('foo');
    }

    public function testFailsToStoreATokenIfNotInitialized(): void
    {
        $memoryTokenStorage = new MemoryTokenStorage();

        $this->expectException('LogicException');

        $memoryTokenStorage->setToken('foo', 'bar');
    }

    public function testFailsToCheckForATokenIfNotInitialized(): void
    {
        $memoryTokenStorage = new MemoryTokenStorage();

        $this->expectException('LogicException');

        $memoryTokenStorage->hasToken('foo');
    }

    public function testFailsToRemoveATokenIfNotInitialized(): void
    {
        $memoryTokenStorage = new MemoryTokenStorage();

        $this->expectException('LogicException');

        $memoryTokenStorage->removeToken('foo');
    }
}
